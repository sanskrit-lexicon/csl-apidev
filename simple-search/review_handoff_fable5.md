# REVIEW HANDOFF — Cologne "simple-search" overhaul (for Fable 5)

Paste this into a fresh **Fable 5** agent that has read/shell access to the
`csl-apidev` clone. For a version that needs **no repo or network access** (key
source embedded inline), use [review_handoff_fable5_inlined.md](review_handoff_fable5_inlined.md).

---

You are a senior reviewer. Another AI (Claude) produced a large body of work this
week on the Cologne Sanskrit Lexicon **simple-search** engine. Your job is a FULL,
CRITICAL, ADVERSARIAL review: independently verify the claims, re-derive the
numbers, check the code ports line-by-line, and judge whether it is safe for the
maintainer (Jim Funderburk) to implement and deploy from. Do NOT rubber-stamp.
Prefer re-deriving over trusting the docs. Every finding must cite `file:line` and
give evidence.

## Environment
- Repo `github.com/sanskrit-lexicon/csl-apidev`, branch `master`. Local clone:
  `C:\Users\user\Documents\GitHub\csl-apidev`. Sibling repo `VisualDCS` holds the
  DCS-2026 data (`src/DCS-data-2026/exports/clean/lemmas.csv`).
- The v1.1 engine is FROZEN PHP under `simple-search/v1.1/` — treat it as ground truth:
  `simple_search.php` (Simple_Search class, transitionTable, doVariant, convert_nonascii),
  `dalnorm.php` (`normalize()`), `getword_list_1.0_main.php` (order_by_wf,
  restrict_to_user_word, init_word_frequency).
- Transcoder table `utilities/transcoder/roman_slp1.xml`; old freq file `simple-search/wf0/wf.txt`.
- Live API (returns SLP1 normalized headwords):
  `https://www.sanskrit-lexicon.uni-koeln.de/scans/csl-apidev/simple-search/v1.1/getword_list_1.0.php?dict=mw&input=default&output=iast&key=KARA`
  (swap KARA; `input=default` = fuzzy mode, `input=iast` = precise.)
- See what changed: `git -C <repo> log --oneline -25` (the feat/test/docs(simple-search)
  commits dated 2026-06-11/12).

## Review targets (all on master)
- `simple-search/readme.org` — enriched engine docs (mermaid, worked examples, overgeneration analysis, missing input modes).
- `simple-search/roadmap_v1.2.md` — engine Fixes A–I, milestones M1–M5, questions Q1–Q13.
- `simple-search/roadmap_dh.md` — DH program (Streams A–D), aligned to the `csl-standards` repo.
- `simple-search/wf1/` — `build_wf_from_dcs.py` + generated `wf.txt` (DCS-2026 frequency refresh; drop-in for `wf0/wf.txt`).
- `simple-search/dcs_xref/` — `build_xref.py` + generated `dcs_cdsl_xref.tsv` (DCS↔CDSL crosswalk).
- `simple-search/eval/` — `eval_search.py` + `gold.tsv` (43 rows) + `fixtures.json` + `readme.md`.
- `CHANGELOG.md` ; `simple-search/issue_jim_implementation.md` (= issue #47) ; `simple-search/ops-cheatsheet.md`.

## HIGH-RISK — verify first
1. **Faithfulness of the dalnorm port.** `simple-search/wf1/build_wf_from_dcs.py`
   `dalnorm_normalize()` claims to be a regex-for-regex port of `normalize()` in
   `simple-search/v1.1/dalnorm.php`. Diff rule-by-rule (anusvara→homorganic-nasal map,
   the r-doubling rule, the rxX aspirate rule, aH/uH/iH endings, ttr→tr, ant→at, the
   two cC rules). Any divergence corrupts every wf1 key. Report exact mismatches.
2. **Faithfulness of the transcoder.** The same script reimplements IAST→SLP1 by parsing
   `roman_slp1.xml` (longest-match substitution, `\uXXXX` decode, identity pass-through).
   Confirm it matches the table and the multi-codepoint entries (ai…bh, ṭh=`ṭh`,
   ḍh, ḷh, ṁ/ṃ→M, candrabindu, avagraha). Find inputs where it would diverge.
3. **Re-run and reconcile stats.**
   `python simple-search/wf1/build_wf_from_dcs.py "C:/Users/user/Documents/GitHub/VisualDCS/src/DCS-data-2026/exports/clean/lemmas.csv"`
   → ~12,096 refreshed / 1,573 0→positive.
   `python simple-search/dcs_xref/build_xref.py "<same lemmas.csv>"` → 12,946 (81.4%) linked / 2,956 DCS-only.
   Confirm the 12,096-vs-12,946 gap is explained (distinct keys refreshed vs all lemma rows
   linked), not a bug. Spot-check `wf1/wf.txt` vs `wf0/wf.txt` for tad, ca, kf, rAjan, agni.

## Also verify
4. **Overgeneration diagnosis.** Re-hit the live API: default kara/sana/manas return ≈11/18/20
   but `input=iast` returns 1 each (the 2021 `restrict_to_user_word` guard). Confirm the
   central claim "overgeneration is default-mode only" from the actual code path.
5. **Eval harness.** Read `eval_search.py`; re-derive P@1, recall@5, MRR, mean#results by hand
   for ~4 gold rows from `fixtures.json`; confirm baseline (ALL n=22 P@1=0.95 recall@5=1.00
   MRR=0.977 mean#=4.45; default mean#=5.22). Check the empty-intended (zero-result) scoring
   and the "skip un-fixtured rows offline" logic. `python simple-search/eval/eval_search.py`.
6. **Sanskrit correctness of gold.tsv `intended` forms.** Are they the MW headword in SLP1?
   Scrutinize judgment calls: rama→rAma (vs literal rama), atman→Atman, ahimsa→ahiMsA,
   guna→guRa, prakriti→prakfti, the precise-IAST rows (kṛṣṇa→kfzRa), the Stream-A
   "expected-miss" rows (gacchati→gam, rAmasya→rAma). Flag any wrong/ambiguous intended.
7. **Folk-ASCII + transition-table claims** (roadmap_v1.2 Fix F + readme.org): mappings
   (sh→ś, ksh/x→kṣ, ri→ṛ, gya/dnya→jñ, ee/oo/aa) linguistically sound? Any that hurt recall?
   Is the "x→kṣ vs x→z" conflict correctly flagged?
8. **Roadmap implementability.** Do the PHP rewrite blocks match the real v1.1 code? Would any
   proposed fix (esp. Fix B hard-drop, Fix C phonotactics, Fix A precise table) silently drop
   the correct answer? Is the eval gate (recall@5 ≥ 0.98) sufficient to catch that?
9. **Spot-check factual claims** against the files: wx_slp1.xml exists but unwired; Velthuis/
   other-Brahmic tables absent; convert_nonascii auto-detects only Deva+Cyrillic; mb_strtolower
   case-folding; the JSON result shape (key/dicthw/dicthwoutput/status/wf).
10. **roadmap_dh.md vs csl-standards.** Read `csl-standards/docs/INTEROPERABILITY_MODEL.md` and
    `PROJECT_SPEC.md`. Is "simple-search = discovery layer over the csl-standards model; don't
    duplicate it" accurate and non-overlapping? Is TEI Lex-0 the right target? Is the DCS↔CDSL
    crosswalk the correct Stream-B join, and the sample TEI fragment valid?
11. **Provenance caveat.** `lemmas.csv` token_counts sum to ~134k, not the 4.57M-token full
    corpus — confirm the docs flag this and that relative ranking is still sound.

## Self-flagged uncertainties (probe these hardest)
- The dalnorm + transcoder Python ports are the single biggest correctness risk, and they were
  **never executed against the real PHP** (PHP + hwnorm1c.sqlite weren't on the author's host).
- Many result lists/counts were captured via a small fast model in a web-fetch tool (NOT
  authoritative); the `manas` fixture showed an apparent duplicate. Re-fetch from the live API.
- Live counts captured 2026-06-11 and may have drifted.
- Some gold `intended` forms were judgment calls under ambiguity.

## Output
- Findings by severity: **CRITICAL** (wrong/unsafe to ship) / **MAJOR** / **MINOR** / **NIT**.
  Each: `file:line`, the problem, the evidence (a re-derivation, a diff, or an API result), a fix.
- A **VERDICT** on three questions: (a) Is `simple-search/wf1/wf.txt` correct to deploy?
  (b) Can Jim implement Fixes A–I from `roadmap_v1.2.md` without recall regressions?
  (c) Is the DH plan (`roadmap_dh.md`) sound and correctly scoped against csl-standards?
- A list of claims you could **not** verify, and why.
Be concrete and skeptical. Cite line numbers. Re-run the scripts and re-hit the API where you can.
