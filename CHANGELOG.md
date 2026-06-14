# Changelog

All notable changes to **simple-search** (and related `csl-apidev` work) are
recorded here. Format loosely follows [Keep a Changelog](https://keepachangelog.com).
Dates are UTC+3 (project local).

## [Unreleased]

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

#### Fixed
- **Entry `id` now unique per record.** Multi-record headwords previously collided on a
  single id, so the `ids` face could not address an individual record. `salt_entry_from_record`
  now disambiguates: `<hom>` present → `-{n}` (C-SALT), else `-L{lnum}` fallback;
  `salt_entries_for_id` parses both forms back. Verified `ka` → 5 unique ids,
  `ids=lemma-agni-L890,lemma-agni-L891` → exactly those 2 records. — 2026-06-14
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
