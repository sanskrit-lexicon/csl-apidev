# C-SALT MW Parity Report — 2026-07

_Created: 08-07-2026 · Last updated: 08-07-2026_

**SPEC:** [MWS SPEC-3](https://github.com/sanskrit-lexicon/MWS/blob/master/planning/specs/2026-07/SPEC-3-salt-parity.md).
**Scope:** MW only (Phase 1 pilot). **Executor:** Sonnet 5 (`claude-sonnet-5`).
**Environment:** local XAMPP PHP 8.2.12 + real `mw.sqlite` (194,084 headwords, 286,560+
records) fetched from the [`csl-sqlite`](https://github.com/sanskrit-lexicon/csl-sqlite)
release `2026-07-05-08-04-06` (`mw.zip`) — the first time this codebase has been
**run-verified against real data** (it shipped in [PR #46](https://github.com/sanskrit-lexicon/csl-apidev/pull/46)
with `salt_common.php` flagged `NOT RUN-VERIFIED: authored without a PHP runtime`).

## Executive summary

1. **A real, high-impact bug was found and fixed**: the shared `Parm` class defaults the
   `input` transliteration to `hk`, not `slp1` — silently breaking **46.6%** of a
   stratified 500-headword sample under the exact request shape the Salt docs advertise
   (no `input` param). Fixed with a Salt-scoped default (§1); re-measured miss rate
   after the fix is **0%**.
2. **A structural granularity gap** between the local Phase-1 entry model (one entry per
   Cologne `lnum` sub-record) and the live C-SALT entry model (one entry per `<hom>`-
   numbered headword, sub-records merged into `sense`) is confirmed on real data (§2).
   This is the single biggest reason `sense`/`re_headwords_slp1`/`xml`/`created` read
   empty locally — it is not just "Phase 5 not built yet", it is a structural decision
   the Phase-3 expansion memo needs to resolve.
3. **GraphQL casing gap**: the hand-rolled dispatcher returns snake_case field keys
   regardless of the camelCase fields requested, contradicting doc/salt_graphql.md §3.2.
   Logged, not fixed (API-shape change, Jim's call) (§3).
4. **`/MW/{ref}` clean-URL routing started**: `cleanurl.php` implements
   `parse_cleanurl()` + the §7 diagnostic `?format=json` contract, whitelist = `mw` only,
   verified against doc/cleanurl.md's own worked examples — which surfaced a
   **doc/data mismatch** in the worked example itself (§4).
5. **Live C-SALT re-verification is incomplete** beyond a handful of hand-checked words:
   `api.c-salt.uni-koeln.de` started rejecting this session's TLS handshakes after
   roughly 20 requests in a few minutes, and stayed blocked through a 6s/request retry
   and a ~5 minute cooldown (§5). The quantitative headline result (§1) is a **local**
   measurement (500/500 samples) and does not depend on live access; the **live**
   structural comparison (§2) rests on 2 words captured before the block plus the
   docs' own already-recorded 2026-06-11/2026-06-14 live captures.

---

## 1. Bug: `Parm` input default is `hk`, not the documented `slp1`

[`parm.php:55`](https://github.com/sanskrit-lexicon/csl-apidev/blob/master/parm.php#L55):

```php
}else {
 $this->filterin0 = 'hk';
}
```

[`doc/salt_entries.md`](https://github.com/sanskrit-lexicon/csl-apidev/blob/master/doc/salt_entries.md)
§1.6 documents the Salt default as `input — slp1`, and every example URL in that doc
(§1.4) omits `input` entirely, relying on that documented default. Because SLP1 and HK
agree on plain letters but diverge on capitalized retroflex/sibilant/nasal letters
(`D`,`Q`,`R`,`S`,`N`,`T`, …), any headword containing one of those — a large fraction of
real Sanskrit — gets silently mis-transcoded and returns **zero results** under the
documented default request shape.

**Measured impact** (500-headword sample, stratified by first SLP1 letter across 26
buckets proportional to their real frequency in `mw.sqlite`, seed = SPEC-3 mint date
`20260702` for reproducibility — `term` query, no `input` param, i.e. the exact shape
every doc example uses):

| | before fix | after fix |
|---|---|---|
| zero-result headwords | 233 / 500 (**46.6%**) | **0 / 500 (0.0%)** |
| total records returned | 491 | 851 |
| example failing key | `arbuDa` (real headword, `mw.sqlite` lnum 16368) → `[]` | → `lemma-arbuDa` ✓ |

**Fix** (this PR): a new `salt_apply_documented_defaults()` in
[`api1/salt_common.php`](https://github.com/sanskrit-lexicon/csl-apidev/blob/master/api1/salt_common.php),
called at the top of the three Salt controller constructors
([`salt_entriesClass.php`](https://github.com/sanskrit-lexicon/csl-apidev/blob/master/api1/salt_entriesClass.php),
[`salt_idsClass.php`](https://github.com/sanskrit-lexicon/csl-apidev/blob/master/api1/salt_idsClass.php),
[`salt_graphqlClass.php`](https://github.com/sanskrit-lexicon/csl-apidev/blob/master/api1/salt_graphqlClass.php)),
sets `$_REQUEST['input'] = 'slp1'` only when neither `input` nor `transLit` was supplied.
**`parm.php`'s own global default is untouched** — other, non-Salt consumers of `Parm`
(citation search, listview, …) may depend on the historical `hk` default, and changing
it globally was out of scope for a Salt-only pilot. An explicit `input=hk` request still
gets HK behavior (verified: `arbuDa&input=hk` → `[]`, as HK-transcoded `arbuDa` is not a
valid MW key).

This is not a "contract question" — it is a plain violation of the doc's own stated
default — so it is fixed here rather than merely logged, per the mission's normal
bug-fix latitude. It does not change any URL or JSON shape.

## 2. Structural gap: entry granularity (local = per-record, live = per-headword)

Real side-by-side capture, headword `ka` (a genuine MW homonym set):

| | **live** C-SALT (`api.c-salt.uni-koeln.de`, captured 08-07-2026) | **local** Phase 1 (after §1 fix) |
|---|---|---|
| entries returned | **4** — `lemma-ka-1`, `lemma-ka-2`, `lemma-ka-3`, `lemma-ka-4` | **31** — `lemma-ka-1..4` (same 4 ids) **+ 27** `lemma-ka-L{lnum}` records |
| what each entry is | one `<hom>`-numbered headword, all its Cologne sub-records merged into one `sense[]`/TEI `xml` body | one Cologne `lnum` row, verbatim |
| `sense` | populated (1 gloss string per hom, `ka-3` empty) | `[]` (Phase 5 TODO) |
| `xml` (TEI) | populated (`<entry xmlns="...tei-c.org...">…`) | `null` (Phase 5 TODO, and must not be confused with `csl.xmlCsl`, which **is** populated) |
| `csl.*` namespace | **absent** — not part of the C-SALT schema at all | present (our CSL extension: `lnum`,`page`,`scanUrl`,`html`,`text`,`xmlCsl`,`references`,`headwordDeva`,`headwordIast`,`accentedKey`) |
| `created` | populated (server-side ingestion timestamp) | `null` |

**The 4 `-{n}` hom ids match exactly between local and live** — our `<hom>`-tag-driven
numbering ([`salt_common.php`](https://github.com/sanskrit-lexicon/csl-apidev/blob/master/api1/salt_common.php)
`salt_entry_from_record()`) is correct where the source XML marks a `<hom>`. The
divergence is entirely the **27 un-numbered continuation sub-records**, which C-SALT's
model folds into the parent hom's `sense[]` array (one entry, multi-sense) and Phase 1
instead emits as 27 separate top-level entries with their own `-L{lnum}` id.

Same shape confirmed on `agni` (live: 1 entry, `sense` has 1 string, no `csl`; local: 10
`lnum`-level records after the §1 fix, up from 5 recorded in the doc's own 2026-06-14
capture — the doc's captured example predates the fix and undercounts for the same
`input`-default reason).

**This is the real content of "Phase 5 (sense split)"** already flagged in
doc/salt_entries.md §1.8: it is not additional parsing work on top of Phase 1's model,
it is a **re-grouping** of Phase 1's per-`lnum` records into per-`hom` entries with a
merged `sense[]`. Recommend the September Phase-3 memo treat this as the lead decision:
whether MW (and the ~40-dict expansion) ships the C-SALT-identical per-headword grouping,
or documents the current per-record granularity as a **sanctioned CSL divergence**
(csl-apidev already does this for the id scheme, doc/salt_entries.md §1.8) — reusing
`homCount`/`hom` already computed in `salt_entry_from_record()` either way.

## 3. GraphQL: casing gap confirmed, not fixed

[`doc/salt_graphql.md`](https://github.com/sanskrit-lexicon/csl-apidev/blob/master/doc/salt_graphql.md)
§3.2: "GraphQL uses camelCase (`queryType`, `headwordSlp1`, …)". Real local POST:

```
POST /salt_graphql.php?dict=mw
{"query":"{ entries(field: headword_slp1, query: \"agni\", queryType: term, size: 1) { id headwordSlp1 } }"}
```
returns `"headword_slp1"` (snake_case), not `"headwordSlp1"` — the hand-rolled dispatcher
in [`salt_graphqlClass.php`](https://github.com/sanskrit-lexicon/csl-apidev/blob/master/api1/salt_graphqlClass.php)
returns the raw `salt_common.php` entry array unmodified; it does no field-selection
projection or camelCase re-keying at all (both the requested-field list and the casing
are ignored — every field is always returned, snake_case, regardless of the query body).
Same casing gap therefore also applies to `sense`→same, `re_headwords_slp1`→same,
`headwordDeva`/`headwordIast`/`accentedKey` inside `csl` (already snake-ish in REST, so
no camelCase equivalent exists yet either).

**Not fixed here** — this is an API-shape change (response keys), squarely inside the
guardrail "Jim reviews API-shape changes". The commented-out `webonyx/graphql-php`
block already in that file (bottom of `salt_graphqlClass.php`) is the natural fix path:
a real GraphQL executor does field-selection projection and resolver-level camelCase
mapping for free. Recommend deciding at the same time as doc/salt_graphql.md §3.6 Q1
(webonyx vs. hand-rolled).

## 4. `/MW/{ref}` clean-URL — started, whitelist = mw only

New [`cleanurl.php`](https://github.com/sanskrit-lexicon/csl-apidev/blob/master/cleanurl.php)
implements [doc/cleanurl.md](https://github.com/sanskrit-lexicon/csl-apidev/blob/master/doc/cleanurl.md)'s
own **Build & test plan step 1**: `parse_cleanurl()` + the routing decision, served as
the §7 diagnostic `?format=json` envelope (content-negotiated HTML/listview rendering,
§5, is a separate, larger integration and is explicitly **not** done here). Dict
whitelist is hardcoded to `['mw']` per SPEC-3 scope — extending to the full code list in
`simple-search/v1.1/parse_uri.php` is Jim's call for the Phase-3 expansion.

Verified against real data (local XAMPP, a throwaway `router.php` test harness emulating
the §4 Apache rewrite — not part of the deliverable):

| URL | Result |
|---|---|
| `/MW/144239` (id route) | `key=bAQa, hom=1, lnum=144239` ✓ |
| `/MW/bAQa` (headword route) | `key=bAQa, hom=1, lnum=144239` ✓ |
| `/MW/144240.5` (decimal lnum) | `key=bAQavikrama, hom=1` ✓ |
| `/MW/xqzqxq` (unknown headword) | `lnum=null`, `note: "not found"` ✓ (matches doc §7 example 3) |
| `/MW/999999999` (unknown id) | `key=null`, `note: "not found"` ✓ |
| `/MW` (front page) | `matched: front` ✓ |
| `/PWG/agni` (non-whitelisted dict) | `404` ✓ |

**Doc/data mismatch found**: doc/cleanurl.md §2.1's own worked example claims
`/MW/bAQa/2` (2nd homonym of `bAQa`) resolves to `lnum=144239.1`. Real `mw.sqlite` data
says otherwise:

```
key=bAQa,  lnum=144239    <- the only record with key exactly "bAQa"
key=bA|a,  lnum=144239.1  <- a DIFFERENT key (spelling variant, cross-referenced
                              "bAQa/ or bA|a/" in both records' bodies)
```

So `144239.1` is not homonym #2 of `bAQa` — it is a separate headword that happens to
sit at the next decimal `lnum`. `cleanurl.php`'s output for `/MW/bAQa/2` is correctly
`lnum=null` ("not found") per its own §3 definition (homonym = same exact key, different
`lnum`) — the code is right, the doc's illustrative example is wrong. Recommend fixing
doc/cleanurl.md §2.1's worked example (pick a headword with a real same-key `<hom>` pair,
e.g. `ka`/`lemma-ka-1..4` from §2 above) rather than changing the code's definition.

## 5. `salt_ids` edge cases

All four from doc/salt_ids.md §2.5, verified locally:

| id form | request | result |
|---|---|---|
| bare (`lemma-{key}`) | `ids=lemma-agni` | 10 records (whole headword) ✓ |
| hom (`lemma-{key}-{n}`) | `ids=lemma-ka-1&ids=lemma-ka-2` | exactly those 2, in **request order** ✓ |
| L-fallback (`lemma-{key}-L{lnum}`) | `ids=lemma-agni-L890` | exactly 1 record ✓ |
| unknown id | `ids=lemma-xqzqxq` | `{"data":{"ids":[]}}`, no error ✓ |

**Resolves doc §2.9 Q2**: the doc worried Phase 1 "returns them grouped per resolved
headword" rather than request order. Verified false — `ids=lemma-ka-2&ids=lemma-ka-1`
(reversed) returns `[lemma-ka-2, lemma-ka-1]`, i.e. **request order is already
preserved**, matching C-SALT. No code change needed; recommend closing that Question.

## 6. `query_type` coverage (Phase 1, local)

| `query_type` | result |
|---|---|
| `term` | exact match, 0% miss after §1 fix (was 46.6%) |
| `prefix` | works; confirms the documented §1.10 "size caps records, not headwords" caveat (`agni&size=5` returns 5 `agni` records, not 5 distinct headwords) |
| `wildcard` | works (`agni*ra&query_type=wildcard` → `agnikumAra, agnikzetra, agnicakra, agnijAra, agninakzatra`) |
| `fuzzy` | works (approximated by prefix, as documented) |
| `regexp`, `match`, `match_phrase` | HTTP 400, "not available until Phase 4" — never silent-empty, as designed ✓ |
| `field` other than `headword_slp1` | HTTP 400, as designed ✓ |

## 7. Live re-verification — incomplete, rate-limited

`api.c-salt.uni-koeln.de` answered the first ~20 requests of this session normally
(the `agni`/`ka` captures in §2 are from these), then started closing every TLS
handshake (`SSL: UNEXPECTED_EOF_WHILE_READING`) for the rest of the session — including
after a ~5 minute cooldown and a retry at 6s/request spacing (20-word gentle batch, 0/5
succeeded before being aborted). TCP connects fine; the block is at the TLS/application
layer, consistent with a WAF/rate-limit rather than a network-level outage. Not
previously logged in [`Uprava/SERVER_OUTAGES.md`](https://github.com/gasyoun/Uprava/blob/main/SERVER_OUTAGES.md) —
flagged there this session with a note not to burst-query this host.

**What is NOT yet verified live** as a result: the full 500-headword sample's live-side
behavior (field-by-field, entry-by-entry), and whether the §1 fix changes live-vs-local
agreement beyond the 2 words hand-checked. The local-only measurement (§1, n=500) does
not depend on live access and stands on its own. Recommend a follow-up live pass at
≥10s/request spacing (matching the existing org convention for the other Cologne host)
once this is picked up again — not urgent, since §1's local fix and §2's structural
finding are the decision-relevant results for the Phase-3 memo either way.

## Definition-of-done checklist (SPEC-3)

- [x] Parity report exists; every field <99% match has its diff class named (§1 `input`
      default bug — root-caused and fixed; §2 entry-granularity — root-caused, logged
      as a Phase-3 decision, not silently fixed).
- [x] GraphQL run against the same data; gaps logged, not silently fixed (§3).
- [x] `/MW/{ref}` clean-URL content negotiation started, whitelist = mw only (§4).
- [x] No deployment attempted (Cologne server is Jim's; all verification local XAMPP).
- [ ] Full 500-sample **live** field diff — blocked on the rate-limit in §7; the local
      500-sample measurement (§1) substitutes for the headline quantitative result.

_Dr. Mārcis Gasūns_
