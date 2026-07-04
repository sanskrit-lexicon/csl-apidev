# app/fixtures — offline endpoint fixtures for `?fixtures=1`

_Created: 04-07-2026 · Last updated: 04-07-2026_

[fixtures.json](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/fixtures/fixtures.json)
serves every endpoint request made by
[app/app.js](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/app.js)
when the page is opened with `?fixtures=1` — one JSON object whose keys are exactly
the client's cache keys, so live capture and offline replay stay in lockstep:

| Key pattern | Endpoint it stands in for |
|---|---|
| `suggest\|<dict>\|<input>\|<term>` | [getsuggest.php](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/getsuggest.php) |
| `wordlist\|<dict>\|<input>\|<key>` | [simple-search/v1.1/getword_list_1.0.php](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/v1.1/getword_list_1.0.php) |
| `dalglob\|<slp1-key>` | [dalglob.php](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/dalglob.php) (`dbglob=keydoc_glob1`) |
| `batch\|<dict>\|<output>\|<accent>\|<keys>` | [getword_batch.php](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/getword_batch.php) |
| `getword\|<dict>\|<output>\|<accent>\|<key>` | [getword.php](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/getword.php) (batch-404 fallback) |

A key missing from the file behaves like an HTTP 404 from the live endpoint.

## Provenance — what is real and what is synthetic

The Cologne server was down when slice 1 was built
([SERVER_OUTAGES.md](https://github.com/gasyoun/Uprava/blob/main/SERVER_OUTAGES.md)),
so a straight live capture was impossible. Instead:

- **Response shapes** are verified against the repo's own PHP:
  [getword_list_1.0_main.php](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/v1.1/getword_list_1.0_main.php),
  [dalglobClass.php](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/dalglobClass.php)
  (`get1`/`parse_glob1`),
  [getword_batch.php](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/getword_batch.php),
  [getsuggestClass.php](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/getsuggestClass.php).
- **Fuzzy candidate lists** (`wordlist|…`) are **real** — captured live 2026-06-11
  and stored in
  [simple-search/eval/fixtures.json](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/eval/fixtures.json);
  the `manas` 20-candidate fan-out is reproduced verbatim, wf-ranked order included.
- **dalglob dictionary sets and entry HTML are SYNTHETIC** placeholders. Every
  synthetic entry body carries a visible `[SYNTHETIC FIXTURE …]` watermark so a
  screenshot can never be mistaken for live dictionary text.

## Post-outage TODO

When the server recovers, recapture the synthetic keys from the live endpoints
(≥ 10 s between requests — the host rate-limits bursts, see
[Uprava FINDINGS §27](https://github.com/gasyoun/Uprava/blob/main/FINDINGS.md)) and
replace this file's dalglob/batch values with real payloads. Regenerate with
`python make_fixtures.py`-style scripting or by saving raw responses under the same
keys.

_Dr. Mārcis Gasūns_
