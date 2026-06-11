# simple-search evaluation harness

Measured IR quality for simple-search — the "make it reproducible" half of DH
rigor. Roadmap: [`../roadmap_dh.md`](../roadmap_dh.md) Stream D.

## Files
- `gold.tsv` — gold set: `query · input · dict · intended_dicthw · note`. An
  empty `intended` is a "should return nothing" case.
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

## Baseline — v1.1 engine (wf0 ranking), seed gold set, 2026-06-11
```
ALL       n=16   P@1=0.94  recall@5=1.00  MRR=0.969  mean#results=5.25
default   n=12   P@1=0.92  recall@5=1.00  MRR=0.958  mean#results=6.67
precise   n=4    P@1=1.00  recall@5=1.00  MRR=1.000  mean#results=1.00
```

**Reading it.** Recall is already perfect — the engine never *loses* the
intended word. The problem is **overgeneration**: `default` mode returns 6.7×
more results than precise mode (6.67 vs 1.00). The single ranking miss is
`rama` → intended `rAma` sits at rank 2 behind the literal headword `rama`
(the `put_user_word_first`-vs-frequency tension; `wf1` DCS frequencies + Fix B
scoring should fix it).

## How this gates the v1.2 fixes
Re-run after each of Fixes A–I. The targets (proposed, see roadmap Q D2):
- **recall@5 must stay ≥ 0.98** (never trade away the right answer), AND
- **default mean #results should fall from 6.67 toward ≤ 3**, AND
- **`rama` P@1 → 1.0** once `wf1` is wired (Fix I) + Fix B scoring lands.

So the harness is both a baseline and a regression gate: overgeneration down,
recall held.

## Extending the gold set
Add rows to `gold.tsv`. For ambiguous queries the *full* relevant set needs
scholarly judgement (roadmap Q D1) — start with the unambiguous intended word.
To refresh fixtures from live, run `--live` and capture the `dicthw` lists.
