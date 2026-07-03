# simple-search evaluation harness

Measured IR quality for simple-search — the "make it reproducible" half of DH
rigor. Roadmap: [`../roadmap_dh.md`](../roadmap_dh.md) Stream D.

## Files
- `gold.tsv` — gold set: `query · input · dict · intended_dicthw · note ·
  expect`. An empty `intended` is a "should return nothing" case. `expect` is
  optional; `aspirational` marks a row DESIGNED to miss under v1.1/v1.2 as
  specified (Stream-A lemmatization targets; the `rama` ranking constraint —
  see H122/M2 below). Aspirational rows are scored and printed but excluded
  from the ALL/default/precise gate, and reported in their own bucket.
- `eval_search.py` — scorer (P@1, recall@K, MRR, mean #results).
- `fixtures.json` — cached live responses (`dict|input|key → ordered dicthw`),
  so the harness runs offline. Captured 2026-06-11 from the v1.1 endpoint.

## Run
```sh
python eval_search.py            # offline, scores fixtures.json
python eval_search.py --live     # scores the live API (needs network)
python eval_search.py --k 5      # recall@K cutoff
```

## Metrics
- **P@1** — is the intended headword ranked first?
- **recall@K** — is it anywhere in the top K?
- **MRR** — mean reciprocal rank of the intended headword.
- **mean #results** — average result-count per query = the **overgeneration**
  metric. Reported split by input mode, because overgeneration is a
  `default`-mode phenomenon (precise modes self-limit via
  `restrict_to_user_word`).

## Baseline — v1.1 engine (wf0 ranking), 47-case gold set (21 with offline fixtures), 2026-06-11
```
ALL          n=21   P@1=1.00  recall@5=1.00  MRR=1.000  mean#results=4.24
default      n=17   P@1=1.00  recall@5=1.00  MRR=1.000  mean#results=5.00
precise      n=4    P@1=1.00  recall@5=1.00  MRR=1.000  mean#results=1.00
aspirational n=1    P@1=0.00  recall@5=1.00  MRR=0.500  mean#results=9.00
```
(`n` counts only rows with an offline fixture; the other gold rows — common
headwords, precise IAST, Stream-A lemmatization targets, the C1/M3
phonotactic tripwires — are scored on `--live`. The `aspirational` bucket is
`rama` — see below; ALL/default/precise exclude it, so their P@1 is a clean
1.00 rather than the 0.94–0.95 the pre-fix-up baseline reported.)

**Reading it.** Recall is already perfect — the engine never *loses* the
intended word. The problem is **overgeneration**: `default` mode returns ~5×
more results than precise mode (5.00 vs 1.00).

**The `rama` case is not a bug to fix — it's a design tension to document
(H122/M2 ruling).** `put_user_word_first` runs *after* ranking and
unconditionally floats the user's literal spelling; `rama` is itself an MW
headword, so it is always rank 1 and intended `rAma` is capped at rank 2 — no
frequency refresh or Fix B score can change that, and v1.2 explicitly
*retains* user-word-first. The gold row is marked `aspirational`: it is
scored for recall (rank 2 ≤ 5, so recall@5 still credits it) but excluded from
the P@1 gate. **Earlier drafts of this doc promised "`rama` P@1 → 1.0 once
wf1 + Fix B land" — that promise is withdrawn; it contradicts the locked
user-word-first design and will never happen.**

## How this gates the v1.2 fixes
Re-run after each of Fixes A–I. The targets (proposed, see roadmap Q D2), over
the **non-aspirational** rows:
- **recall@5 must stay ≥ 0.98** (never trade away the right answer), run
  `--live`, AND
- **default mean #results should fall from 5.00 toward ≤ 3.**

(Before this fix-up the gate was unsatisfiable by construction: the 2
Stream-A rows are *designed* to miss, capping live recall@5 at 41/43 ≈ 0.953
< 0.98, and — because both carry precise input modes — capping the `precise`
bucket at 7/9 ≈ 0.78. Marking them `aspirational` removes them from the
denominator instead of pretending they'll pass.)

So the harness is both a baseline and a regression gate: overgeneration down,
recall held, aspirational rows tracked separately rather than silently
poisoning the pass/fail number.

## Extending the gold set
Add rows to `gold.tsv`. For ambiguous queries the *full* relevant set needs
scholarly judgement (roadmap Q D1) — start with the unambiguous intended word.
To refresh fixtures from live, run `--live` and capture the `dicthw` lists.
