# Cologne `app/` UI Spec v1 — Proposal A (Research Workbench), Slice 1

_Created: 03-07-2026 · Last updated: 03-07-2026_

Implementation spec for the unified Cologne Sanskrit Lexicon interface. Direction and
scope were ruled by MG on 03-07-2026 (spec authored the same session, Fable 5
`claude-fable-5`). Companion documents:
[proposal-brief.md](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/doc/ux-redesign/proposal-brief.md)
(product direction) and
[cologne-redesign-prototype.html](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/doc/ux-redesign/cologne-redesign-prototype.html)
(visual reference — the "Proposal A: Research Workbench" state is the one being built).

## MG rulings (03-07-2026) — locked

| # | Question | Ruling |
|---|---|---|
| R1 | Direction | **Proposal A — Research Workbench** |
| R2 | Code home | **New `app/` directory** in csl-apidev (lookup/ stays a focused citation tool; its Wave 3 = [H140](https://github.com/gasyoun/Uprava/blob/main/handoffs/H140_lookup_wave3.md) proceeds independently) |
| R3 | Default input mode | **Auto-detect + fuzzy `default`**; explicit `<select>` for ASCII schemes (per the locked simple-search v1.2 rulings) |
| R4 | First slice | **Search + results + entry reader** (catalogue homepage and dictionary detail routes = slice 2) |
| R5 | Display script | **IAST default, one-click toggle to Devanagari** |
| R6 | Dictionary scope | **All dictionaries always** (cross-dict resolution must therefore be one round-trip — see §Endpoint bindings) |
| R7 | Results shape | **Grouped by headword, dictionary badges per row** |

## Files (slice 1)

```
app/
  index.php     — shell + GET-prefill (copy lookup/index.php's json_encode(htmlspecialchars(...)) pattern verbatim)
  app.js        — client logic (no framework, no build step; same conventions as lookup/lookup.js)
  app.css       — styles; palette/typography from the prototype's :root variables
  fixtures/     — captured live JSON responses for offline dev (?fixtures=1 flag)
```

Reuse, do not copy: [lookup/dictmeta.js](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/lookup/dictmeta.js)
(static dict-code → full title/edition-year table) via `<script src="../lookup/dictmeta.js">`.
Transliteration for the R5 toggle and input preview: the **sanskrit-util JS transcoder**
(IAST⇄SLP1⇄Devanagari — see root [SHARED_CODE.md](https://github.com/gasyoun/github-spine/blob/main/SHARED_CODE.md);
do not write transcoder #63). Only the slp1→deva/iast directions are needed, which avoids
the known `devanagari_to_slp1` ळ→x bug.

## Layout

Desktop (≥ 900px), top to bottom:

1. **Top bar** — Cologne seal + title (as prototype); right side: input-scheme `<select>` and IAST/Devanagari display toggle.
2. **Search row** — one full-width search box with `getsuggest.php` autocomplete; mode tabs directly beneath: **Fuzzy (default) · Exact · Prefix · Suffix** — always visible (must-preserve).
3. **Workbench** — two columns: results list left (~38%), entry reader right. Reader shows the selected entry's server-rendered HTML, with per-entry actions: scan link (`servepdf.php`), permalink copy.
4. **Advanced panel** — one collapsible panel under the search row (accent handling, per-dictionary filter chips). Collapsed by default; state not persisted in slice 1.

Mobile (< 900px): single column; results list first, reader opens beneath the tapped
row (accordion), toggle bar sticks to top. No separate mobile page — same URL contract.

## Endpoint bindings (verified against repo code 03-07-2026)

All endpoints already exist at csl-apidev root; **no server-side changes in slice 1.**

| UI action | Endpoint | Notes |
|---|---|---|
| Autocomplete while typing | [getsuggest.php](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/getsuggest.php) | debounced ≥ 300 ms, single-flight (cancel stale), as lookup.js does |
| Fuzzy candidates (default mode) | [simple-search/v1.1/getword_list_1.0.php](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/v1.1/getword_list_1.0.php)`?key=&input=&dict=` | per-dict engine — call once against MW to generate candidate headwords; non-default `input` returns only the exact word (`restrict_to_user_word`, [getword_list_1.0_main.php:167](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/v1.1/getword_list_1.0_main.php)) |
| Cross-dict presence per headword (R6) | [dalglob.php](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/dalglob.php)`?key=<slp1>` | ONE round-trip resolves which dictionaries/homonym dockeys contain the headword → powers the R7 badges; this is what makes "all dictionaries always" affordable |
| Entry HTML into the reader | [getword_batch.php](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/getword_batch.php) | batch per request; on HTTP 404 (not yet pulled on server) fall back to sequential [getword.php](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/getword.php) fetches ~250 ms apart with exponential backoff — copy lookup.js's fallback verbatim |
| Scan click-through | [servepdf.php](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/servepdf.php) | plain link action on the entry (must-preserve; never a default split pane) |
| Display toggle (R5) | `output=roman` ⇄ `output=deva` on entry fetches; headword list re-rendered client-side via sanskrit-util | matches lookup's existing `output` select values |

**Rate discipline (hard requirement).** The Cologne host rate-limits bursts
([Uprava FINDINGS](https://github.com/gasyoun/Uprava/blob/main/FINDINGS.md) §27): at most
one in-flight request chain per user action; debounce all typing; cache responses per
session keyed by SLP1 headword; **no prefetching** of entries the user hasn't clicked.

## Search modes

| Mode | Binding | Slice 1 status |
|---|---|---|
| Fuzzy (default) | `getword_list_1.0.php` with `input=default` | full |
| Exact | user's word only — non-default `input` value passes through `restrict_to_user_word`; then `dalglob.php` → badges | full |
| Prefix | `getsuggest.php` prefix matching rendered as a results list | full |
| Suffix | endpoint unconfirmed — the current Advanced page's mechanism must be identified before wiring | **tab present, disabled with "coming in slice 2" tooltip** (see Open items) |

Input scheme select: `Default (forgiving)` · `IAST` · `HK` · `SLP1` · `ITRANS`, plus
auto-detected Devanagari/Cyrillic input (badge shows the detection, as lookup does).
ASCII schemes are never auto-detected — explicit select only (locked v1.2 ruling in
[roadmap_v1.2.md](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/roadmap_v1.2.md)).

## Results list (R7)

One row per **headword**: IAST headword (toggleable to Devanagari) + dictionary badges
from the `dalglob.php` response, ordered by `dictmeta.js` canonical order. Clicking a
badge loads that dictionary's entry into the reader; clicking the row loads the first
badge. Homonyms (multiple dockeys in one dict) render as numbered sub-badges (MW¹ MW²),
same convention as lookup's homonym tabs. Word-frequency ordering of the headword list
comes free from the engine (`order_by_wf`).

## URL contract / permalinks

`app/index.php?key=X&input=Y&output=Z&dict=DICT` — GET prefill re-runs the search on
load; Copy-link button reflects current state (same pattern as lookup Wave 2 permalinks).
**No existing production URL changes.** The current Basic/List/Advanced/Mobile/Simple
pages are untouched; `app/` is additive (brief's must-preserve).

## States

- **Empty**: short hint + 3 example queries (one Devanagari, one IAST, one plain-ASCII).
- **Loading**: inline status via `aria-live="polite"` (no spinners over content).
- **No results**: offer the fuzzy candidates if the mode was exact/prefix.
- **Server down / timeout**: banner "The Cologne server is not responding — try again later"; no auto-retry loops (rate discipline).
- **Batch 404**: silent fallback to sequential getword.php (log to console only).

## Acceptance criteria (slice 1 done =)

1. Typing `agni` (default mode) → suggestion list; Enter → headword rows with correct dict badges; clicking MW badge renders the MW entry in the reader.
2. `input=iast`, key `manas` → exactly the exact-match headword (no fuzzy fan-out).
3. Devanagari input auto-detected (badge shown), same flow works.
4. Display toggle re-renders headwords and re-fetches the open entry with `output=deva`.
5. Permalink round-trip: copy link → open in fresh tab → identical state.
6. Mobile 375px: single column, reader accordion works, no horizontal scroll.
7. All of the above pass offline via `?fixtures=1`, and (post-outage) against the live server with ≥10s-spaced verification probes.
8. Every response cached: repeating a search issues zero new network requests.

## Open items

1. **Suffix-mode endpoint** — identify what the current Advanced page calls (likely a `listhier.php`/`listview.php` family query); wire in slice 2. Ask Jim if not discoverable from [csl-websanlexicon](https://github.com/sanskrit-lexicon/csl-websanlexicon).
2. **Deployed URL of simple-search v1.1** on the live server — repo path is known; confirm the public path with Jim before hard-coding.
3. **Overgeneration**: default-mode fan-out (manas → 20) is fixed server-side by v1.2 M2 (score + hard-drop, Jim's queue). The UI must not re-implement scoring; it displays what the engine returns.

## Risks

- Cologne server is currently down ([SERVER_OUTAGES.md](https://github.com/gasyoun/Uprava/blob/main/SERVER_OUTAGES.md)) — build against fixtures; live verification is a separate, gated step.
- `getword_batch.php` and everything in `app/` are inert on the live site until Jim pulls the server checkout (same as lookup Wave 1, [PR #63](https://github.com/sanskrit-lexicon/csl-apidev/pull/63)) — "not visible live" ≠ "not done".
- Burst-ban: any accidental request loop can get the client IP throttled; the single-flight + cache rules above are not optional.

_Dr. Mārcis Gasūns_
