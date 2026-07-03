<!-- Body of GitHub issue sanskrit-lexicon/csl-apidev#47.
     Title: simple-search: v1.2 + DH-grade implementation — master task list
     Labels: enhancement, major ; Milestone: User Experience ; assignee: funderburkjim
     NOTE: links use full blob/tree URLs because relative links do not resolve in issue bodies. -->

# simple-search: v1.2 + DH-grade implementation — master task list

Everything needed from **@funderburkjim** to take simple-search from v1.1 to a
v1.2 engine and then to a DH-grade, corpus-grounded, FAIR resource. Detailed
specs live in two roadmaps; this issue is the ordered checklist + the decisions
+ the open questions, in one place. Feeds the v1.1 rollout/feedback issue
[#26](https://github.com/sanskrit-lexicon/csl-apidev/issues/26).

The PHP engine in [simple-search/v1.1/](https://github.com/sanskrit-lexicon/csl-apidev/tree/master/simple-search/v1.1) is **frozen**; all code work below is
new. Everything marked **(data ready)** is already built and committed — only
the wiring is yours.

## Specs (read these for detail)
- Engine fixes A–I, milestones M1–M5, questions Q1–Q13 → [simple-search/roadmap_v1.2.md](https://github.com/sanskrit-lexicon/csl-apidev/blob/master/simple-search/roadmap_v1.2.md)
- DH streams A–D, questions → [simple-search/roadmap_dh.md](https://github.com/sanskrit-lexicon/csl-apidev/blob/master/simple-search/roadmap_dh.md)
- Engine overview + worked examples → [simple-search/readme.org](https://github.com/sanskrit-lexicon/csl-apidev/blob/master/simple-search/readme.org)
- Measurement/gate → [simple-search/eval/readme.md](https://github.com/sanskrit-lexicon/csl-apidev/blob/master/simple-search/eval/readme.md)

## Decisions already locked (don't re-litigate)
1. **Trust precise input** — no nasal/sibilant fuzz for slp1/deva/iast.
2. **Result policy = score + hard-drop** (not collapse, not reorder-only).
3. **Add four input tracks** — loose-ASCII+case, Brahmic scripts, WX+Velthuis, ISO-15919/NFC/case.
4. **TEI Lex-0** for archival interop; **Vidyut** for morphology; full eval harness.
5. **Repo boundary:** [csl-standards](https://github.com/sanskrit-lexicon/csl-standards) owns model + TEI/OntoLex + loss; **simple-search owns retrieve + rank + address + corpus-ground.**

---

## Phase 0 — quick wins (do first, low risk)
- [ ] **Wire the DCS-2026 frequencies (Fix I).** One line: point
  `init_word_frequency()` in [simple-search/v1.1/getword_list_1.0_main.php](https://github.com/sanskrit-lexicon/csl-apidev/blob/master/simple-search/v1.1/getword_list_1.0_main.php) at
  [simple-search/wf1/wf.txt](https://github.com/sanskrit-lexicon/csl-apidev/blob/master/simple-search/wf1/wf.txt) instead of [wf0/wf.txt](https://github.com/sanskrit-lexicon/csl-apidev/blob/master/simple-search/wf0/wf.txt). **(data ready)** —
  12,096 lines refreshed direct + 90 via legacy-spelling re-normalize (addendum A1); `tad 180→3734`, `kf 163→1083`, `rAjan 84→588`.
- [ ] **Record the live baseline.** On the server run
  `python simple-search/eval/eval_search.py --live` (see [eval_search.py](https://github.com/sanskrit-lexicon/csl-apidev/blob/master/simple-search/eval/eval_search.py))
  and save the numbers. Offline baseline today (non-aspirational rows —
  `rama` is excluded, see below): recall@5 = 1.00, **default mean #results =
  5.00** vs precise 1.00, P@1 = 1.00.

## Phase 1 — M1 engine hygiene  ([roadmap_v1.2 §§4, 7, 8](https://github.com/sanskrit-lexicon/csl-apidev/blob/master/simple-search/roadmap_v1.2.md))
- [ ] **Fix A** — add `transitionTable_precise`; route slp1/deva/iast to it.
- [ ] **Fix D** — dedup results by `(dicthw, output)`.
- [ ] **Fix E1** — Unicode NFC pass at the top of `convert_nonascii`.

## Phase 2 — M2 ranking (the headline fix)  ([roadmap_v1.2 §5](https://github.com/sanskrit-lexicon/csl-apidev/blob/master/simple-search/roadmap_v1.2.md))
- [ ] **Fix B** — per-row substitution costs; thread an edit-cost through
  `doVariant`; score each candidate; **hard-drop** below `best + DELTA`
  (never drop the user's exact word); expose `score` in the JSON.
- [ ] **A2** — soften `restrict_to_user_word` to return exact + scored near-matches.
- [ ] **Gate with the harness** (re-run `--live`): **recall@5 ≥ 0.98 over
  non-aspirational rows**, **default mean #results 5.00 → ≤ 3**. Do **not**
  target `rama` P@1 → 1.0 — `put_user_word_first` unconditionally floats the
  user's literal spelling and v1.2 keeps that design, so `rama` is capped at
  rank 2 regardless of `wf1`/Fix B scoring; it is marked `aspirational` in
  `gold.tsv` (recall-only; H122/M2 ruling).

## Phase 3 — M3 precision  ([roadmap_v1.2 §§6, 9](https://github.com/sanskrit-lexicon/csl-apidev/blob/master/simple-search/roadmap_v1.2.md))
- [ ] **Fix C** — phonotactic prune: word-initial ṅ/ṇ only (letter-names ṅa/ṇa
  whitelisted). The original rule (b) — "ṇ needs a nati trigger" — is
  **deleted**: it vetoed real lexical ṇ-words (guṇa, maṇi, paṇa, …) in every
  input mode, including precise (Fable 5 review finding C1). Do not
  reintroduce a nati-trigger veto without gating it to generated variants
  only, never precise modes, never before `restrict_to_user_word`.
- [ ] **Fix F** — `folknorm()` pre-normalizer (sh/ch/ri/ee/oo, ksh/x→kṣ, gya/dnya→jñ, case-fold), replacing the scattered `clean_default` hacks.

## Phase 4 — M4 input coverage  ([roadmap_v1.2 §§8, 10](https://github.com/sanskrit-lexicon/csl-apidev/blob/master/simple-search/roadmap_v1.2.md))
- [ ] **Fix E2/E3** — extend non-ASCII auto-detect (Bengali, Tamil/Grantha, Telugu,
  Kannada, Malayalam, Gujarati); add WX + Velthuis as **explicit** input options
  (ASCII schemes can't be auto-detected). WX table already exists.
- [ ] **Fix G** — add the missing `<script>_slp1.xml` transcoder tables.

## Phase 5 — M5 scale (optional)  ([roadmap_v1.2 §11](https://github.com/sanskrit-lexicon/csl-apidev/blob/master/simple-search/roadmap_v1.2.md))
- [ ] **Fix H** — index-side "blur-key" retrieval (the [simple-search/simpleslp/](https://github.com/sanskrit-lexicon/csl-apidev/tree/master/simple-search/simpleslp) machinery is the base).

## DH streams  ([roadmap_dh.md](https://github.com/sanskrit-lexicon/csl-apidev/blob/master/simple-search/roadmap_dh.md))
- [ ] **Stream A — lemmatization & sandhi** (Vidyut, `analyze=yes`): `gacchati`→`gam`,
  split `devarājaḥ`→`deva`+`rājan`. Decide hosting (microservice vs pre-expanded table).
- [ ] **Stream B — corpus-grounding** (`corpus=yes`): DCS frequency + genre + examples
  per result. **Join key ready** → [simple-search/dcs_xref/dcs_cdsl_xref.tsv](https://github.com/sanskrit-lexicon/csl-apidev/blob/master/simple-search/dcs_xref/dcs_cdsl_xref.tsv)
  (DCS lemma → CDSL normkey; 12,945 linked). Examples in the VisualDCS repo (`visual/conc_*.json`).
- [ ] **Stream C — FAIR / TEI Lex-0 / LOD**: reuse the [csl-standards](https://github.com/sanskrit-lexicon/csl-standards) entry id;
  content-negotiate CDSL/TEI-Lex-0/OntoLex; citable permalinks via [COLOGNE#249](https://github.com/sanskrit-lexicon/COLOGNE/issues/249).
  Align with [csl-standards](https://github.com/sanskrit-lexicon/csl-standards); do not duplicate its model.
- [x] **Stream D — measured quality**: [simple-search/eval/](https://github.com/sanskrit-lexicon/csl-apidev/tree/master/simple-search/eval) (done; your job is to keep
  the gate green and grow the gold set — see Q D1/D2/D3).

---

## Questions for you to resolve
**Engine ([roadmap_v1.2 §15](https://github.com/sanskrit-lexicon/csl-apidev/blob/master/simple-search/roadmap_v1.2.md)):**
1. Hard-drop `DELTA` and floor `N`; always keep the user's exact word?
2. `x → kṣ` (folk) vs `x → z` (foreign, current) in default mode?
3. `sh → ś` vs `ṣ` default bias?
4. OK to add `score` to the JSON contract (consumers: [list-0.2s_rw.php](https://github.com/sanskrit-lexicon/csl-apidev/blob/master/simple-search/v1.1/list-0.2s_rw.php), sanlex-vue)?
5. WX/Velthuis as explicit `<select>` options (confirm)?
6. Brahmic detect order / codepoint-range collisions?
7. Is PHP `intl` (`Normalizer`) available on the Cologne server?
8. Phonotactic filter: start with only the two safe rules?
9. Index-side rewrite (Fix H) worth it, or is generate-and-test fast enough?
10. Case-significant ASCII in default mode — fold (current) or heuristic guess?
11. DCS frequency source — `lemmas.csv` (clean) or raw `dcs_full.sqlite`?
12. Frequency merge policy — refresh-where-present (current) or DCS-only?
13. Combine freq × edit-score as `f(cost)·g(log(1+freq))`, or freq as tie-breaker?

**DH ([roadmap_dh.md §8](https://github.com/sanskrit-lexicon/csl-apidev/blob/master/simple-search/roadmap_dh.md)):**
- A. Vidyut hosting (microservice vs pre-expanded inflection table)?
- B. Corpus payload (inline examples vs `examples.php`); where to store the crosswalk?
- C. Does simple-search *serve* TEI/OntoLex (proxy csl-standards) or only *link*? id scheme? confirm TEI **Lex-0**.
- D. Eval governance — gold-set curator; target thresholds; CI gate?
- Boundary sub-question: who owns the DCS↔CDSL crosswalk (now in [simple-search/dcs_xref/](https://github.com/sanskrit-lexicon/csl-apidev/tree/master/simple-search/dcs_xref))?

## Where everything is
| What | Path | State |
|---|---|---|
| Engine (frozen) | [simple-search/v1.1/](https://github.com/sanskrit-lexicon/csl-apidev/tree/master/simple-search/v1.1) | reference |
| v1.2 spec | [simple-search/roadmap_v1.2.md](https://github.com/sanskrit-lexicon/csl-apidev/blob/master/simple-search/roadmap_v1.2.md) | ready |
| DH spec | [simple-search/roadmap_dh.md](https://github.com/sanskrit-lexicon/csl-apidev/blob/master/simple-search/roadmap_dh.md) | ready |
| Refreshed frequencies | [simple-search/wf1/wf.txt](https://github.com/sanskrit-lexicon/csl-apidev/blob/master/simple-search/wf1/wf.txt) | **data ready, unwired** |
| Eval harness + gate | [simple-search/eval/](https://github.com/sanskrit-lexicon/csl-apidev/tree/master/simple-search/eval) | done |
| DCS↔CDSL crosswalk | [simple-search/dcs_xref/dcs_cdsl_xref.tsv](https://github.com/sanskrit-lexicon/csl-apidev/blob/master/simple-search/dcs_xref/dcs_cdsl_xref.tsv) | done |
| Changelog | [CHANGELOG.md](https://github.com/sanskrit-lexicon/csl-apidev/blob/master/CHANGELOG.md) | maintained |
