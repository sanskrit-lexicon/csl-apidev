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
- `in_cdsl` = 1 if `normkey` is a known CDSL head-key (present in `wf0/wf.txt`).

## Stats (this build)
- 15,902 DCS lemmas
- **12,946 (81.4%) linked to CDSL** — `in_cdsl=1`; 12,055 distinct CDSL keys
- 2,956 DCS-only — corpus forms outside the headword set (causative/derived
  stems, sandhi compounds): **Stream A lemmatization targets** and candidate
  new entries.

## Why this matters
1. **Stream B join** — attach DCS frequency/genre/examples to a CDSL result by
   `normkey`.
2. **Reusable LOD linkset** — `dcs_id → CDSL head-key` is a citable crosswalk in
   its own right (a step toward the FAIR/LOD spine, Stream C).
3. **Coverage signal** — the 2,956 `in_cdsl=0` rows quantify where the corpus
   has forms the dictionaries don't index as headwords.

## Build
Reuses the faithful transcoder + normalize from `../wf1/build_wf_from_dcs.py`
(parses `roman_slp1.xml`, ports `dalnorm.normalize`), so keys are identical to
the live engine — no PHP / hwnorm1c.sqlite needed.

```sh
python build_xref.py [lemmas.csv] [out.tsv]
```
Defaults resolve relative to this folder; needs the VisualDCS repo as a sibling
for the default `lemmas.csv` path.
