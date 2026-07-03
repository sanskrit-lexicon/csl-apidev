# Do-not-file suppression corpus — applicability verdict (H083)

_Created: 03-07-2026 · Last updated: 03-07-2026_

## Context

SanskritSpellCheck maintains a standing "do-not-file suppression" corpus —
2,297 headwords across 33 dictionaries that LOOK like misspellings but are
documented on purpose (w.r./v.l. apparatus readings, ṇopadeśa roots,
`{{Lbody}}` redirects, colophon spellings):
[`nochange/do_not_file_suppress.txt`](https://github.com/drdhaval2785/SanskritSpellCheck/blob/master/nochange/do_not_file_suppress.txt).

H083 asks whether csl-apidev has a search/normalization path that could
"correct" or fold one of these deliberately non-standard forms into a
different headword, and if so, to wire the same suppression check used in
csl-atlas ([csl-atlas#188](https://github.com/sanskrit-lexicon/csl-atlas/pull/188)).

## Verdict: split by code path

### Production API — NOT APPLICABLE

The live endpoints (`getword.php`, `getsuggest.php`/`getsuggestClass.php`,
`dal.php`) do exact-match (`Dal::get1`) or prefix-match (`Dal::get3a`, `key
LIKE '<prefix>%'`, [`dal.php`](dal.php) around line 239) lookups only. There is
no edit-distance, soundex, or "did you mean" logic — a search either finds the
literal or a literal-prefixed headword, or nothing. `transcoder_processString`
only converts between transliteration schemes (SLP1/IAST/HK/Devanagari); it
never changes spelling. There is no path here that could fold a deliberately
non-standard headword into a different canonical form, so the suppression
corpus has nothing to suppress.

### Experimental `simple-search/` engine — real folding logic exists, but is not live

`simple-search/v1.1/` is a **prototype**, not wired into the production
`getword.php`/`getsuggest.php`/Salt API endpoints, still under active roadmap
discussion ([`simple-search/roadmap_v1.2.md`](../simple-search/roadmap_v1.2.md),
csl-apidev#26). It does generate spelling variants and could, if shipped
as-is, fold a deliberately non-standard headword into a different one:

- [`simple-search/v1.1/simple_search.php`](../simple-search/v1.1/simple_search.php)
  `$transitionTable_default` (~line 26) defines phonetic equivalence classes
  (e.g. `["S","z","s","zh","sh"]`, `["b","v"]`, `["n","Y","N","m","R","M"]`)
  applied recursively by `doVariant()` (~line 269).
- [`simple-search/v1.1/dalnorm.php`](../simple-search/v1.1/dalnorm.php)
  `Dalnorm::normalize()` (line 69) canonicalizes anusvara/geminate/sandhi
  spelling before the variant is checked for existence against
  `hwnorm1c.sqlite`.
- `Simple_Search::searchdict_add_basic($k0)` (~line 234) accepts a generated
  variant into the result set once `dalnorm->get1()` confirms it exists —
  this is the point where a suppressed form's *neighbours* could surface
  instead of (or alongside) the form itself.
- `.ai_state.md`'s own dev notes already track this as "overgeneration"
  (equivalence classes applied unconditionally, validation is existence-only)
  — a pre-existing, independently-tracked v1.2 concern, not something this
  session introduces.

**Decision:** do not wire a suppression check into an actively-changing,
unshipped prototype in this pass. The concrete gate is: **before
`simple-search` v1.2 (or any successor) is wired into the production API**,
its equivalence-class variant generator must consult the do-not-file
suppression set the same way csl-atlas's H5 anomaly review does — the sibling
read + graceful-absence shape in
[`csl-atlas/scripts/lib/do-not-file-suppression.mjs`](https://github.com/sanskrit-lexicon/csl-atlas/blob/main/scripts/lib/do-not-file-suppression.mjs)
is the pattern to port. Recorded here so the check isn't lost when v1.2 work
resumes.

## Result

- **Production API: NOT APPLICABLE** (no correction/folding path exists).
- **csl-apidev wiring in this pass: none** — only csl-atlas
  ([csl-atlas#188](https://github.com/sanskrit-lexicon/csl-atlas/pull/188))
  carries the live suppression check, per H083's own fallback clause ("if
  genuinely nothing applies, record that honestly ... and wire csl-atlas
  only").
- **Follow-up gate recorded** for whenever `simple-search` v1.2 ships (see
  above) — flagged in [`PROJECT_INTERLINKS.md`](https://github.com/gasyoun/Uprava/blob/main/PROJECT_INTERLINKS.md).

_Dr. Mārcis Gasūns_
