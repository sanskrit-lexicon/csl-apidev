# Changelog

All notable changes to **simple-search** (and related `csl-apidev` work) are
recorded here. Format loosely follows [Keep a Changelog](https://keepachangelog.com).
Dates are UTC+3 (project local).

## [Unreleased]

### Added

- **`lookup/` Wave 3** (H140, `doc/roadmap_lookup.md` §Wave 3): scan click-through
  buttons surfacing the `servepdf.php` link already embedded in each entry's HTML,
  a copy-citation button per record (headword, dict + year, page/col, Cologne
  record ID, permalink — the pain point from
  [#29](https://github.com/sanskrit-lexicon/csl-apidev/issues/29)), an
  `accent=yes/no` display toggle that reloads only the active dictionary/homonym
  without a full re-search, a print stylesheet, and `prefers-color-scheme: dark`
  support. Plus a one-line "try the new lookup page" banner on `sample/dalglob1.php`
  (opt-in, not a redirect — no removal without Jim's sign-off, per D1/Wave 3
  non-goals).

## [0.3.0] - 2026-07-28

### Added

- **`docs/WEB_BACKEND_MANUAL.md` — operator manual for the whole web backend**
  ([H1784](https://github.com/gasyoun/Uprava/blob/main/handoffs/H1784-Fable_csl-apidev_web-backend-operator-manual_28.07.26.md)):
  cheat-sheet, anatomy, request data-flow, endpoint catalogue (indexing the canonical
  `doc/*` specs), local run + csl-sqlite data staging (incl. the `csl-apidev`-basename
  trap), Salt/C-SALT phase status, the csl-websanlexicon fork-sync runbook, scan/PDF
  path contract + 404 table, security posture (CI gates, JSONP whitelist idiom,
  CSP-Report-Only), Cologne deploy/outage reality, symptom→cause→cure, glossary,
  maintainer appendix. Plus `docs/WEB_BACKEND_MANUAL.meta.md` and a README docs-index
  link.
- **`app/rowSlp1.check.js` — a zero-dependency regression check** for the IAST case-folding below, wired into CI as a hard gate (`js-check`). The root cause of the `to_slp1` trap was not the bug but that *nothing pinned what a capital does* — the function is documented as "IAST → SLP1" with no word on case and is tested only on lowercase. 10 assertions, no runner, no npm: `node app/rowSlp1.check.js`.
- **Bengali script → SLP1 transcoder table** (`utilities/transcoder/bengali_slp1.xml`, H1497, [#128](https://github.com/sanskrit-lexicon/csl-apidev/pull/128)).
- **Crawlable SSR entry permalinks + JSON-LD + sitemap** (`app/entry.php`, H227, [#78](https://github.com/sanskrit-lexicon/csl-apidev/pull/78)), adversarially reviewed with a thin-content-gate fix (H233, [#80](https://github.com/sanskrit-lexicon/csl-apidev/pull/80)); **`app/` slice 2** — visual redesign + catalogue homepage + dictionary-detail routes, all 7 pages unified ([#74](https://github.com/sanskrit-lexicon/csl-apidev/pull/74)); Velthuis input scheme + finalized input-select order (R8, [#72](https://github.com/sanskrit-lexicon/csl-apidev/pull/72)).
- **SPEC-3 C-SALT MW parity report** ([reports/salt_parity_mw_2026-07.md](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/reports/salt_parity_mw_2026-07.md), [#81](https://github.com/sanskrit-lexicon/csl-apidev/pull/81)) — incl. the Salt `input`-default bugfix (`salt_apply_documented_defaults()`: `Parm`'s global `hk` default silently broke 46.6% of a 500-headword sample) and the start of `/MW/{ref}` clean-URL routing.

### Changed

- **simple-search default ranking now uses the DCS-2026 `wf1` frequencies** (Fix I, H1562, [#105](https://github.com/sanskrit-lexicon/csl-apidev/pull/105)) — `init_word_frequency()` pointed at `simple-search/wf1/wf.txt`.

### Fixed

- **`app.js` `rowSlp1()` now case-folds IAST before transcoding** ([SanskritLexicography#779](https://github.com/gasyoun/SanskritLexicography/issues/779), [H1695](https://github.com/gasyoun/Uprava/blob/main/handoffs/H1695-Opus_csl-apidev_rowslp1-iast-case-fold_26.07.26.md)): `sanskrit-util`'s `to_slp1` maps lowercase IAST and passes everything else through verbatim — into an output alphabet where case is **phonemic** (`R`=ṇ, `B`=bh, `E`=ai). A capitalised headword therefore transcoded to a different, plausible-looking word with no error path: `Rāma` → `RAma`, which **rendered in the results list as `ṇāma`** and looked up the wrong `dalglob|` key. `IAST_RE` deliberately auto-detects capitalised input, so the path was reachable by design, not by accident. `foldIast()` (NFC + lowercase, the same defence [csl-atlas](https://github.com/sanskrit-lexicon/csl-atlas) already applies in `lookup-normalize.js`) now runs first; SLP1 input is explicitly never folded, since there the case carries meaning. Found by the org-wide `to_slp1` caller audit that followed the same bug corrupting 60% of the keys in SanskritLexicography's ACC×NCC works crosswalk.
- **MW bare `&c.` tooltip** ([MWS#86](https://github.com/sanskrit-lexicon/MWS/issues/86), H1523): display-layer wrap of ~21k bare `&c.` occurrences so hover shows "et cetera; and so on" (same sense as already-marked `etc.`). Twin of csl-websanlexicon makotemplates fix. Optional bulk `<ab>&c.</ab>` in csl-orig remains a separate monthly-batch path.
- **RV/AV links never emit `rv00.*`** ([COLOGNE#370](https://github.com/sanskrit-lexicon/COLOGNE/issues/370)): `parse_rv_mandala()` + guards in `ls_callback_mw_href` / `rgveda_link` / GRA callback so a bad mandala token cannot become `rv00.147.html`. Twin of csl-websanlexicon makotemplates fix.
- **`servepdfClass.php` preface/title-page navigation** ([#45](https://github.com/sanskrit-lexicon/csl-apidev/issues/45), H1522): `getImagefiles()` used to strip *all* leading non-digits from the `page` parameter, so front-matter ids like `t36` became `36` and hit dictionary page `0036` instead of title-scan `t36`. Now strips only a leading `Page` prefix and preserves `tNN` title-page ids. Same fix landed in the csl-websanlexicon makotemplates twin.
- **MW `n=` continuations for Hariv./Megh./Hit.** ([#89](https://github.com/sanskrit-lexicon/csl-apidev/pull/89)), **bare MED. linked to medini app4 by headword** ([#94](https://github.com/sanskrit-lexicon/csl-apidev/pull/94) series), **STC split `s. v.` rejoined** (csl-orig#2821), **AP90 parenthetical-compound indentation** ([COLOGNE#254](https://github.com/sanskrit-lexicon/COLOGNE/issues/254)), **always-visible servepdf open-PDF link** ([COLOGNE#153](https://github.com/sanskrit-lexicon/COLOGNE/issues/153)), **MW XML key2 shown in the list pane** — the display-parity half of the H1523 sweep ([#85](https://github.com/sanskrit-lexicon/csl-apidev/pull/85)–[#98](https://github.com/sanskrit-lexicon/csl-apidev/pull/98)).

### Security

- **H1523 hardening sweep, ~40 PRs** ([#96](https://github.com/sanskrit-lexicon/csl-apidev/pull/96)–[#126](https://github.com/sanskrit-lexicon/csl-apidev/pull/126)): `security_headers.php` (baseline headers + CSP-Report-Only) required by every live HTML/JSON entry point incl. `api0/`, `sample/`, `pwkvn/`, app/lookup/simple-search UIs; systematic `htmlspecialchars`/`json_encode` escaping of echoed keys, hrefs, titles, XML element attrs, not-found pages and api0 pretty mode; `lnum`/key/term length + numeric validation; null-safe `json_decode` and hardened Salt GraphQL body parsing; jQuery 2.1.4 → 3.7.1 (CDN + local), `jquery.cookie` → `js-cookie` with `SameSite=Lax`; shell-arg escaping in `simpleslp` query3; `html.escape` in `build_sitemap.py` (CodeQL).

## [0.2.0] - 2026-07-04

### app/ — unified Cologne search UI, slice 1 (H147, spec [ui-spec-app-v1.md](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/doc/ux-redesign/ui-spec-app-v1.md))

#### Added
- **`app/` slice 1 — Proposal A "Research Workbench"** (MG rulings R1–R7 of
  03-07-2026): one search surface over all dictionaries at once. `app/index.php`
  (GET-prefill via the lookup/ `json_encode(htmlspecialchars(...))` pattern),
  `app/app.js` (vanilla ES6, no build step), `app/app.css` (prototype palette).
  Search modes Fuzzy (default, `getword_list_1.0.php` `input=default`) · Exact
  (`restrict_to_user_word` + client `user_key_flag` filter) · Prefix
  (`getsuggest.php` as results list) · Suffix (tab present, disabled — slice 2).
  Results grouped by headword with per-dictionary badges from one `dalglob.php`
  round-trip per headword (homonyms as numbered sub-badges MW¹ MW²); entry
  reader fetches via `getword_batch.php` with the lookup.js sequential
  `getword.php` + 429-backoff fallback on 404. IAST-default display with
  one-click Devanagari toggle (client-side re-render via vendored
  `sanskrit-util` global build in `app/vendor/`; slp1→iast/deva directions
  only). Auto-detect Devanagari/IAST input + explicit ASCII scheme select;
  permalinks `?key=&input=&output=&dict=`; mobile < 900px single-column with
  reader accordion. Rate discipline per spec: single-flight search token,
  ≥300 ms debounce, per-session response cache (repeat search = zero requests),
  no prefetch, 250 ms-spaced badge chain. — 2026-07-04
- **`app/fixtures/` offline mode (`?fixtures=1`)**: all endpoint traffic served
  from `fixtures.json` keyed by the client's cache keys; shapes verified against
  the repo PHP, fuzzy candidate lists real (2026-06-11 capture), dalglob/entry
  payloads synthetic + watermarked pending live recapture
  (see [app/fixtures/readme.md](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/fixtures/readme.md)).
  All 8 slice-1 acceptance criteria verified offline against these fixtures;
  live verification is gated on the server outage. — 2026-07-04

## [0.1.0] - 2026-07-03

### Salt API — Phase 1 (PR [#46](https://github.com/sanskrit-lexicon/csl-apidev/pull/46), `api1/`, MW pilot)

#### Added
- **C-SALT-compatible controller trio** wired to the real data layer (no new runtime):
  `api1/salt_entries.php`, `salt_ids.php`, `salt_graphql.php` (+ their `*Class.php`), sharing
  one search + envelope builder `api1/salt_common.php`. Search → `Dal`
  (`term`/`prefix`/`wildcard`/`fuzzy`; `regexp`/`match`/`match_phrase` → 400 until Phase 4);
  render → `Getword_data`; transliteration → `transcoder_processString`. — 2026-06-11
- **Run-verified end-to-end (2026-06-14)** against the real `mw.sqlite` (286,560 records,
  from the `csl-sqlite` release): all three faces (`entries` term+prefix, `ids`, `graphql`)
  return structurally-correct envelopes with populated `csl.{lnum,page,column,scanUrl,
  references,headwordDeva,headwordIast,accentedKey}` and working transliteration
  (`agni → अग्नि`). CLI smoke test: `php api1/salt_selftest.php mw agni indra ka`.
- **Docs**: `doc/salt_api_handoff.md` (run/deploy/verify/parity), deepened endpoint specs
  `doc/salt_entries.md` · `salt_ids.md` · `salt_graphql.md` (real verified responses,
  `query_type` matrix, error/JSONP semantics), and new `doc/salt_api_usecases.md`
  (10 copy-paste recipes). — 2026-06-14

#### Changed
- **Salt API docs alignment.** Updated endpoint docs, use cases, README pointers, and GraphQL
  examples so Phase 1 paths, unsupported-mode 400s, variable-based `ids`, and migration
  caveats match the deploy handoff. — 2026-06-20
#### Fixed
- **Entry `id` now unique per record.** Multi-record headwords previously collided on a
  single id, so the `ids` face could not address an individual record. `salt_entry_from_record`
  now disambiguates: `<hom>` present → `-{n}` (C-SALT), else `-L{lnum}` fallback;
  `salt_entries_for_id` parses both forms back. Verified `ka` → 5 unique ids,
  `ids=lemma-agni-L890,lemma-agni-L891` → exactly those 2 records. — 2026-06-14
- **Phase 1 `field` handling no longer pretends unsupported fields are headword
  searches.** REST and GraphQL now accept only `field=headword_slp1` in the MW pilot;
  C-SALT enum values that need later resolvers/indexes (`id`, `sense`,
  `re_headwords_slp1`, `created`, `xml`) return 400/error instead of silently running the
  headword path. — 2026-06-20
- **Missing SQLite no longer crashes prefix/wildcard search.** If the per-dictionary
  SQLite database is unavailable in a development checkout, shared Salt search returns an
  empty result envelope instead of calling `Dal` methods on a null PDO connection. —
  2026-06-20
- **GraphQL literal-arg parser** no longer truncates `query:"a*"` to `"a"` (wildcards /
  diacritics / spaces were dropped). — 2026-06-14

#### Security
- **JSONP-callback reflected-XSS hardened** in `api1/salt_entries.php` + `salt_ids.php`:
  the `callback` is whitelisted (`^[A-Za-z_$][A-Za-z0-9_$.]{0,127}$`, else `400 invalid
  callback`) and `htmlentities`-wrapped — clears the Semgrep `echoed-request` taint sink.
  The same class was swept across 10 pre-existing endpoints on `master` in PR
  [#52](https://github.com/sanskrit-lexicon/csl-apidev/pull/52) (merged). — 2026-06-14

### Added
- **Master handoff for Jim** (`simple-search/issue_jim_implementation.md`) — one
  ordered checklist (Phases 0–5 + DH Streams A–D), the locked decisions, and all
  open questions; **opened as [#47](https://github.com/sanskrit-lexicon/csl-apidev/issues/47)**. — 2026-06-11
- **DCS↔CDSL crosswalk** (`simple-search/dcs_xref/`) — `dcs_cdsl_xref.tsv` maps
  every DCS-2026 lemma to the CDSL normalized head-key the engine uses (the
  Stream B join key + a reusable LOD linkset). 15,902 lemmas, 12,946 (81.4%)
  linked to CDSL, 2,956 DCS-only (lemmatization targets). Built by
  `build_xref.py` (reuses the `wf1` transcoder+normalize). — 2026-06-11
- **DH-grade roadmap** (`simple-search/roadmap_dh.md`) — four-stream program
  (A lemmatization/Vidyut, B corpus-grounding/DCS, C FAIR/TEI-Lex-0/LOD aligned
  to `csl-standards`, D measured quality); search framed as the discovery layer
  over the `csl-standards` interoperability stack. — 2026-06-11
- **Evaluation harness** (`simple-search/eval/`) — `eval_search.py`
  (P@1, recall@K, MRR, mean #results), a **43-case gold set** (22 with offline
  fixtures; the rest scored `--live`), cached fixtures, readme. v1.1 baseline:
  recall@5=1.00 but default mean #results=5.22 vs precise 1.00 (overgeneration
  quantified); regression gate for Fixes A–I. — 2026-06-11
- **DCS-2026 frequency refresh** (`simple-search/wf1/`) — `wf.txt` drop-in for
  `wf0/wf.txt` rebuilt from the DCS-2026 lemma export; 12,096 keys refreshed,
  1,573 went 0→positive (tad 180→3734, ca 179→3385, kf 163→1083). Built by
  `build_wf_from_dcs.py`. Activation (point `init_word_frequency()` at `wf1`)
  left for Jim. — 2026-06-11
- **v1.2 improvement roadmap** (`simple-search/roadmap_v1.2.md`) + enriched
  `simple-search/readme.org` (mermaid data-flow, live 0/1/5/15+ result examples,
  overgeneration analysis, input-coverage gaps incl. capital-letter handling).
  Fixes A–I: tiered tables, score+hard-drop, phonotactic prune, dedup,
  NFC/wider-script detect, folk-ASCII, index-side rewrite, DCS frequency.
  — 2026-06-11

### Decisions
- **Repo boundary (2026-06-11):** `csl-standards` owns model + TEI/OntoLex + loss;
  `simple-search` owns retrieve + rank + address + corpus-ground.
- Interoperability target = **TEI Lex-0**; morphology engine = **Vidyut**; build the
  full evaluation harness.

### Notes
- Overgeneration verified as a **`default`-mode** phenomenon only:
  `restrict_to_user_word` already collapses precise input to the exact word.
