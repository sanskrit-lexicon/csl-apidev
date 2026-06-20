# Salt API: ids

Batch-fetch entries by id. Mirrors the C-SALT / Kosh `/dicts/{id}/restful/ids` contract:
this is a **get-by-id**, not a search. Each id is an `lemma-{headword_slp1}` token (§
[salt_entries](salt_entries.md)); internally it resolves to a `lnum` lookup via the
existing `getword` record path.

Normative contract: `csl-standards/docs/SALT_API_PROFILE.md` §5.

## 2. Fetch entries by id

### 2.1. URL

https://www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/api1/salt_ids.php?dict=mw&ids=lemma-agni&ids=lemma-indra

### 2.2. Input parameters

| restful | example | notes |
|---|---|---|
| dict | mw | Cologne dict code, lower-cased. |
| ids | lemma-agni | repeated (multi-value) parameter; one or more entry ids. |
| input | slp1 | CSL extension (display transliteration), as in [salt_entries](salt_entries.md). |
| output | deva | CSL extension. |
| accent | no | CSL extension. |

### 2.3. Suggested Clean URL

```
/dicts/{id}/restful/ids?ids={id}&ids={id}…
```

### 2.4. Examples

1. https://sanskrit-lexicon.uni-koeln.de/dicts/mw/restful/ids?ids=lemma-agni
2. https://sanskrit-lexicon.uni-koeln.de/dicts/mw/restful/ids?ids=lemma-ka-1&ids=lemma-ka-2

### 2.5. Allowable values

1. dict — Cologne dict codes. Pilot: `mw`.
2. ids — entry-id tokens in any of the three forms minted by [salt_entries](salt_entries.md) §1.8:
   - `lemma-{headword_slp1}` — a single-record headword, **or** "the whole headword" (returns
     every record under the key);
   - `lemma-{headword_slp1}-{n}` — the `n`-th homonym (`<hom>`-numbered, C-SALT form);
   - `lemma-{headword_slp1}-L{lnum}` — one specific record by Cologne `lnum` (the fallback for
     un-numbered sub-records, e.g. `lemma-agni-L890`).

   Resolution strips the suffix to recover the SLP1 key, fetches the headword's records, then
   returns the exact id match (or all records for the bare-key form).

### 2.6. Defaults

None for `dict`/`ids` (mandatory). `input=slp1`, `output=deva`, `accent=no`.

### 2.7. Rewrite rules

```
RewriteRule ^dicts/([^/]*)/restful/ids$  /scans/awork/apidev/api1/salt_ids.php?dict=$1  [QSA,L]
```

### 2.8. Expected output

Entry objects, identical shape to [salt_entries](salt_entries.md) §1.8, under `data.ids`.
**Real round-trip** (verified 2026-06-14): `ids=lemma-agni-L890&ids=lemma-agni-L891` returns
exactly those two records — id ↔ record is 1:1 once a record is addressed by its full id:

```json
{
  "data": {
    "ids": [
      { "id": "lemma-agni-L890", "headword_slp1": "agni", "csl": { "lnum": "890", "page": "5", "column": "1", "…": "…" } },
      { "id": "lemma-agni-L891", "headword_slp1": "agni", "csl": { "lnum": "891", "page": "5", "column": "1", "…": "…" } }
    ]
  }
}
```

By contrast a bare `ids=lemma-agni` returns **all 5** `agni` records (the "whole headword"
form). Unknown ids resolve to no entry (omitted from the array), not an error.

### 2.9. Questions

1. Should `ids` also accept a bare `lnum` (e.g. `890`) as a convenience, in addition to the
   `lemma-…` forms? (C-SALT only accepts its own id; `-L{lnum}` already encodes the lnum.)
2. Order: C-SALT returns ids in request order. Phase 1 returns them grouped per resolved
   headword — confirm the required ordering in the parity pass.
