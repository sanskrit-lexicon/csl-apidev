# Changelog

All notable changes to **simple-search** (and related `csl-apidev` work) are
recorded here. Format loosely follows [Keep a Changelog](https://keepachangelog.com).
Dates are UTC+3 (project local).

## [Unreleased]

### Added
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

### Notes
- Overgeneration verified as a **`default`-mode** phenomenon only:
  `restrict_to_user_word` already collapses precise input to the exact word.
