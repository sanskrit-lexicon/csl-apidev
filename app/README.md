# `app/` — unified Cologne Sanskrit Lexicon interface

_Created: 05-07-2026 · Last updated: 05-07-2026_

The `app/` directory is the redesigned, unified front end for the Cologne
Digital Sanskrit Dictionaries — the implementation of **Proposal A (Research
Workbench)** ruled on 03-07-2026. It replaces the public split between the
old Basic / List / Advanced / Mobile / Simple pages with one responsive
interface, and adds a catalogue homepage and a dictionary-detail route.

Design direction, product rationale and the locked R1–R8 rulings live in
[`doc/ux-redesign/`](https://github.com/sanskrit-lexicon/csl-apidev/tree/main/doc/ux-redesign):
[proposal-brief.md](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/doc/ux-redesign/proposal-brief.md),
[ui-spec-app-v1.md](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/doc/ux-redesign/ui-spec-app-v1.md),
[cologne-redesign-prototype.html](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/doc/ux-redesign/cologne-redesign-prototype.html).

## Pages

| Page | File | Replaces |
|---|---|---|
| Search (results + entry reader) | [index.php](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/index.php) | Basic · List · Advanced · Mobile · Simple |
| Catalogue homepage | [home.php](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/home.php) | Homepage |
| Dictionary detail (`dict.php?dict=CODE`) | [dict.php](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/dict.php) | MW-style midpage |

## Files

| File | Role |
|---|---|
| [index.php](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/index.php) | Search shell + GET-prefill (`?key=&input=&output=&dict=&mode=`) |
| [app.js](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/app.js) | Search client logic — fuzzy/exact/prefix modes, badges, reader, permalinks, rate discipline |
| [home.js](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/home.js) | Catalogue rendering — all 45 dictionaries, name filter, language facets |
| [dict.js](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/dict.js) | Dictionary-detail rendering from `?dict=CODE` |
| [app.css](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/app.css) | Design-token system (type scale, spacing grid, depth) + light/dark theme; styles all three pages |
| [theme.js](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/theme.js) | Light/dark theme toggle, persisted to `localStorage` (`cologne-theme`), applied pre-paint |
| [fixtures/](https://github.com/sanskrit-lexicon/csl-apidev/tree/main/app/fixtures) | Captured endpoint JSON for offline dev (`?fixtures=1`) |
| [vendor/sanskrit-util.global.js](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/vendor/sanskrit-util.global.js) | Vendored transcoder for the IAST⇄Devanagari display toggle |

No framework and no build step — plain classic scripts. `home.php` and
`dict.php` render entirely from the static
[`../lookup/dictmeta.js`](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/lookup/dictmeta.js)
table, so they work with **no server call**; only `index.php` search talks to
the live endpoints.

## Conventions

- **Additive layer, no production URL changes.** The existing
  Basic/List/Advanced/Mobile/Simple pages are untouched; `app/` sits alongside
  them.
- **Endpoints unchanged.** Search reuses the existing root endpoints
  (`getsuggest.php`, `simple-search/v1.1/getword_list_1.0.php`, `dalglob.php`,
  `getword_batch.php` → `getword.php` fallback, `servepdf.php`). No server-side
  changes are required to render these pages.
- **Rate discipline.** One in-flight request chain per user action; typing
  debounced ≥ 300 ms; every response cached per session; no prefetching. The
  Cologne host rate-limits bursts.
- **Input schemes** (order R8): Default (forgiving) · IAST · HK · Devanagari ·
  SLP1 · Velthuis · ITRANS. Devanagari and IAST are auto-detected; the ASCII
  schemes are explicit-select only.
- **Display script** (R5): IAST by default, one-click toggle to Devanagari.

## Run locally

The pages are PHP. With PHP (e.g. XAMPP) on the machine, serve the **repo root**
so `../css`, `../lookup` and the sibling endpoints resolve:

```
php -S 127.0.0.1:8099 -t /path/to/csl-apidev
```

Then open:

- [http://127.0.0.1:8099/app/home.php](http://127.0.0.1:8099/app/home.php) — catalogue homepage
- [http://127.0.0.1:8099/app/index.php?fixtures=1](http://127.0.0.1:8099/app/index.php?fixtures=1) — search, offline against fixtures
- [http://127.0.0.1:8099/app/dict.php?dict=mw](http://127.0.0.1:8099/app/dict.php?dict=mw) — a dictionary-detail page

`?fixtures=1` serves all search traffic from
[fixtures/fixtures.json](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/fixtures/fixtures.json)
so the search works with no live server.

## Deployment note

Everything in `app/` is inert on the live site until the server checkout is
pulled (same as the `lookup/` Wave 1 rollout,
[PR #63](https://github.com/sanskrit-lexicon/csl-apidev/pull/63)) — "not visible
live" does not mean "not done". Live re-verification of search is gated on the
Cologne server outage
([SERVER_OUTAGES.md](https://github.com/gasyoun/Uprava/blob/main/SERVER_OUTAGES.md)).

_Dr. Mārcis Gasūns_
