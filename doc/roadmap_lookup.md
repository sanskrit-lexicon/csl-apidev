# Roadmap — `lookup/` (dalglob v2): modern global dictionary lookup

_Created: 03-07-2026 · Last updated: 03-07-2026_

Plan to drastically improve the global citation-lookup experience currently
served by
[sample/dalglob1.php](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/sample/dalglob1.php)
(live at
[/scans/csl-apidev/sample/dalglob1.php](https://sanskrit-lexicon.uni-koeln.de/scans/csl-apidev/sample/dalglob1.php)).
Authored from a repo audit + MG interview (4 rulings, 03-07-2026, recorded
below). Wave 1 is agent-executable via handoff
[H088](https://github.com/gasyoun/Uprava/blob/main/handoffs/H088_apidev_lookup_wave1.md).

---

## 1. Why — audit findings (03-07-2026)

- `dalglob1.php` is a 2016-era dev sample promoted into de-facto use: jQuery
  2.1.4 + jQuery-UI 1.11.4 + jquery.cookie off CDNs (cf.
  [#23](https://github.com/sanskrit-lexicon/csl-apidev/issues/23)), deprecated
  `escape()`, no autocomplete, no input validation, no permalinks, raw tab UI,
  desktop-only layout.
- Its call pattern is the flakiness driver: after one
  [dalglob.php](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/dalglob.php)
  lookup it fires **one `getword.php` fetch per homonym key in parallel** —
  exactly the burst shape that trips the server's rapid-request throttling
  (HTTP 429 / TLS failures, measured in
  [Uprava/FINDINGS.md](https://github.com/gasyoun/Uprava/blob/main/FINDINGS.md)
  §2: "dalglob too flaky for UI").
- The repo's newer
  [simple-search](https://github.com/sanskrit-lexicon/csl-apidev/tree/main/simple-search)
  answers a *different* question ("what headwords might this spelling be?");
  dalglob answers "show this citation's entry in **every** dictionary". Both
  are needed; neither replaces the other. Related open issues:
  [#16 refactor dalGlob](https://github.com/sanskrit-lexicon/csl-apidev/issues/16),
  [#47 simple-search master list](https://github.com/sanskrit-lexicon/csl-apidev/issues/47),
  [#29 preferred citation formats](https://github.com/sanskrit-lexicon/csl-apidev/issues/29),
  [#14 browser-side transliteration](https://github.com/sanskrit-lexicon/csl-apidev/issues/14).

## 2. Decisions taken (MG rulings, 03-07-2026)

| # | Fork | Ruling | Rationale |
|---|------|--------|-----------|
| D1 | Where the improved page lives | **New page in csl-apidev** — a new top-level `lookup/` directory beside the old sample; `dalglob1.php` stays untouched until Jim blesses a banner/redirect | Full freedom, zero breakage of existing links, lands where the API lives; closes the spirit of [#16](https://github.com/sanskrit-lexicon/csl-apidev/issues/16) |
| D2 | Depth of the rewrite | **Frontend + call pattern** — modern page AND fix the N-parallel-requests burst (additive batch endpoint + client backoff); existing endpoint contracts untouched | Removes the 429 flakiness without redesigning Jim's API surface |
| D3 | Feature set | **All four**: autocomplete + forgiving input · dictionary metadata & grouping · permalinks + history · scan links + citation copy | Each has an existing backend affordance (`getsuggest.php`, `dictinfo.php`, pushState pattern from [PR #61](https://github.com/sanskrit-lexicon/csl-apidev/pull/61), `servepdf.php`) |
| D4 | Delivery mode | **PR + heads-up issue** — short proposal comment on [#16](https://github.com/sanskrit-lexicon/csl-apidev/issues/16), then PR the same week without blocking on a reply | Matches how the Salt work ([PR #46](https://github.com/sanskrit-lexicon/csl-apidev/pull/46)) landed; Jim stays informed, nothing stalls |

Derived engineering choices (within the rulings, not separate forks): vanilla
ES6 JavaScript with **zero** CDN/framework/build-step dependencies (retires
the jQuery/jquery.cookie stack, cf.
[#23](https://github.com/sanskrit-lexicon/csl-apidev/issues/23));
`localStorage` replaces cookies; the batch endpoint is **additive**
(`getword_batch.php`) so `dalglob.php` / `getword.php` behavior is
byte-identical for existing consumers.

## 3. Waves

### Wave 1 — Core page + safe call pattern (agent-doable now → H088)

Deliverables, all in a new `lookup/` directory unless noted:

1. `lookup/index.php` + `lookup/lookup.css` + `lookup/lookup.js` — vanilla
   ES6, no external resources, responsive layout, accessible (keyboard-navigable
   homonym tabs, ARIA roles), graceful error states (not-found / network /
   throttle-retry).
2. **One round-trip per dictionary**: new additive `getword_batch.php`
   (parameters `dict`, `keys` comma-separated, `input`, `output`, `accent` →
   JSON array of rendered entries) built on the existing
   [getwordClass.php](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/getwordClass.php).
   Client falls back to *sequential* `getword.php` fetches with spacing +
   exponential backoff on 429 whenever the batch endpoint is absent — so the
   page works even before the server checkout is updated.
3. Autocomplete via
   [getsuggest.php](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/getsuggest.php)
   (debounced), script auto-detect (Devanagari/IAST heuristic, explicit
   select for ambiguous ASCII schemes — the simple-search lesson that
   HK/SLP1/Velthuis cannot be auto-told-apart), live transliteration preview
   (closes the spirit of [#14](https://github.com/sanskrit-lexicon/csl-apidev/issues/14)).
4. Heads-up comment on [#16](https://github.com/sanskrit-lexicon/csl-apidev/issues/16)
   + PR per D4.

*Unblocked by:* nothing — data and endpoints exist.

### Wave 2 — Metadata, grouping, permalinks

1. Dictionary results as cards with full title, year, and language from
   [dictinfo.php](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/dictinfo.php)
   — no more bare `pwg`-style codes; group + filter by language (EN/DE/FR/LA/RU)
   and era; per-user preferred dictionary order persisted in `localStorage`.
2. `?key=` permalinks with `history.pushState` (shareable, citable,
   back-button-correct — extends the pattern of
   [PR #61](https://github.com/sanskrit-lexicon/csl-apidev/pull/61)), aligned
   with the clean-URL scheme in
   [doc/cleanurl.md](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/doc/cleanurl.md)
   (COLOGNE#249) so the two permalink families stay one.
3. Copy-link button.

*Unblocked by:* Wave 1 merge.

### Wave 3 — Scholarly affordances + deprecation ask

1. Per-entry scan click-through via
   [servepdf.php](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/servepdf.php)
   surfaced as a first-class button (not only inline links inside the entry
   HTML).
2. Copy-citation button emitting the preferred quotation formats of
   [#29](https://github.com/sanskrit-lexicon/csl-apidev/issues/29).
3. Accent toggle (`accent=yes/no` already supported server-side), print
   stylesheet, dark mode via `prefers-color-scheme`.
4. **Deprecation ask to Jim**: banner on `dalglob1.php` pointing to
   `lookup/`, eventual redirect — his call, prepared as a one-line PR.

*Unblocked by:* Wave 2 merge + live-server verification of Wave 1 latency.

## 4. Non-goals (considered and ruled out)

- **No framework, no build step** — plain PHP + ES6, same as the rest of the
  repo; the server runs from a git checkout.
- **No changes to existing endpoint contracts** — `dalglob.php`,
  `getword.php`, `getsuggest.php` stay byte-identical for current consumers;
  the batch endpoint is additive only (D2).
- **Not folded into simple-search v1.2** — Jim's M1–M5 queue
  ([simple-search/roadmap_v1.2.md](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/roadmap_v1.2.md))
  stays untangled from this work; we reuse its input-handling lessons only (D1).
- **Not relocated to kosha** — [kosha](https://github.com/gasyoun/kosha)'s P5
  UI phase is a separate consumer of the same data; Cologne's live page gets
  fixed where Cologne users are (D1).
- **No in-place rewrite of `dalglob1.php`** and no removal without Jim's
  explicit blessing (D1/Wave 3).
- **No server config / `.htaccess` changes.**

## 5. Dependencies & risks

- **Server deploy is Jim's pull** — new PHP files only go live when the
  server checkout updates; the Wave-1 client fallback (sequential + backoff)
  keeps the page functional against today's endpoints regardless.
- **429 throttling can only be tuned live** —
  [Uprava/FINDINGS.md](https://github.com/gasyoun/Uprava/blob/main/FINDINGS.md)
  §2; local testing uses the repo's XAMPP pattern (cf.
  `sample/*_xampp.html`) or fixtures.
- **Host egress varies** — some dev hosts cannot reach the live Cologne API
  or `api.github.com` (FINDINGS §1); verified working from this host
  03-07-2026, but handoff sessions must not assume it.

---

_Dr. Mārcis Gasūns_
