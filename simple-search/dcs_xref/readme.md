# DCS ↔ CDSL crosswalk

`dcs_cdsl_xref.tsv` maps every Digital Corpus of Sanskrit (2026) lemma to the
CDSL normalized head-key (`normkey`) that simple-search uses internally. The
`normkey` is the **join key**: at query time `hwnorm1c` resolves it to the
per-dictionary headword spelling. Roadmap: [`../roadmap_dh.md`](../roadmap_dh.md)
Stream B (Q B2).

## Columns
`dcs_id` · `dcs_lemma_iast` · `slp1` · `normkey` · `in_cdsl` · `token_count` · `grammar`

- `slp1` = `dcs_lemma_iast` transcoded via the repo's `roman_slp1.xml`.
- `normkey` = `slp1` run through `dalnorm.normalize()` (= the engine's key space).
- `in_cdsl` = 1 if `normkey` is present in `wf0/wf.txt`.
  **Semantics correction (Fable 5 second-pass review, PR #65):** despite the
  name, this flags membership in **wf0** (a frequency list), **not** the full
  CDSL head-key universe. `wf0` is incomplete as a headword census, so
  `in_cdsl=0` does **not** mean "not a real headword" — at least 89 of the
  2,957 `in_cdsl=0` rows *are* real CDSL headwords whose wf0 entry is stored
  under a dead pre-2017 spelling (see `../wf1/build_wf_from_dcs.py`'s
  legacy-spelling re-normalize step, addendum A1). Treat `in_cdsl=0` as "not
  found under this normkey in wf0 today", not as an authoritative headword
  census.

## Stats (this build)
- 15,902 DCS lemmas
- **12,945 (81.4%) linked to wf0** — `in_cdsl=1`; 12,055 distinct wf0 keys
- 2,957 DCS-only — corpus forms outside wf0 (causative/derived stems, sandhi
  compounds, plus the legacy-spelling cases above): **Stream A lemmatization
  targets** and candidate new entries.

## Why this matters
1. **Stream B join** — attach DCS frequency/genre/examples to a CDSL result by
   `normkey`.
2. **Reusable LOD linkset** — `dcs_id → CDSL head-key` is a citable crosswalk in
   its own right (a step toward the FAIR/LOD spine, Stream C).
3. **Coverage signal** — the 2,957 `in_cdsl=0` rows quantify where the corpus
   has forms not found under that normkey in `wf0` today (not all of them are
   genuinely absent from CDSL — see the `in_cdsl` semantics note above).

## Build
Reuses the faithful transcoder + normalize from `../wf1/build_wf_from_dcs.py`
(parses `roman_slp1.xml`, ports `dalnorm.normalize`, repairs 6 mojibake
lemma codepoints — see that script's `_MOJIBAKE_REPAIR`), so keys are
identical to the live engine — no PHP / hwnorm1c.sqlite needed.

```sh
python build_xref.py [lemmas.csv] [out.tsv]
```
Defaults resolve relative to this folder; needs the VisualDCS repo as a sibling
for the default `lemmas.csv` path.
