# Salt API — use cases & recipes

Practical, copy-paste recipes for the C-SALT-compatible Salt API over CDSL data. Every
example is **run-verified (2026-06-14)** against the real MW data (`mw.sqlite`, 286,560
records) unless marked *Phase N*. Contract: [`salt_entries.md`](salt_entries.md) ·
[`salt_ids.md`](salt_ids.md) · [`salt_graphql.md`](salt_graphql.md) · run/deploy notes:
[`salt_api_handoff.md`](salt_api_handoff.md).

Base path on the server is `scans/awork/apidev/api1/`; the clean forms below assume the
rewrite rules in each spec's "Rewrite rules" section are installed. `$H` =
`https://sanskrit-lexicon.uni-koeln.de`.

---

## 1. Look up a headword (the 80% case)

> *"What does MW say under* agni*?"*

```sh
curl "$H/dicts/mw/restful/entries?field=headword_slp1&query=agni&query_type=term"
```

Returns the 5 `agni` records, each a full entry with `csl.text` (clean prose),
`csl.page`/`column` and `csl.scanUrl`. The query is transcoded to SLP1 first, so the
call works when the declared input script matches the query text: use
`query=agni&input=slp1`, `query=अग्नि&input=deva`, or `query=agni&input=roman` for
IAST-style roman input. See [`salt_entries.md`](salt_entries.md) §1.8 for the verbatim
response.

## 2. Address one exact record (deep link / citation)

Each record has a stable, unique `id`. Two real shapes:

```sh
# homonym-numbered (source carries <hom>):
curl "$H/dicts/mw/restful/ids?ids=lemma-ka-1"
# lnum-addressed sub-record (no <hom>):
curl "$H/dicts/mw/restful/ids?ids=lemma-agni-L890"
```

`lemma-agni-L890` resolves to **exactly one** record (MW lnum 890, p.5 c.1). Use these ids as
citable handles — they survive in the `entries` response, so a client lists results then
deep-links each one.

## 3. Batch-fetch several entries at once

`ids` is a repeated parameter — one round-trip for a whole reading list:

```sh
curl "$H/dicts/mw/restful/ids?ids=lemma-agni-L890&ids=lemma-agni-L891&ids=lemma-ka-1"
```

Returns `data.ids[]` in the same entry shape. Unknown ids are simply omitted (not an error).
A bare `ids=lemma-agni` returns **all** `agni` records — the "give me the whole headword" form.

## 4. Prefix browse (type-ahead / suggest)

```sh
curl "$H/dicts/mw/restful/entries?field=headword_slp1&query=agni&query_type=prefix&size=25"
```

Backed by `Dal::get3c` (`key LIKE 'agni%'`). **Caveat (Phase-3 parity):** `size` currently
caps *records*, so a dense headword like `agni` can fill the page before later headwords
appear — see [`salt_entries.md`](salt_entries.md) §1.10. For a strict headword-suggest list,
raise `size` or wait on the parity decision.

## 5. Wildcard search

```sh
# * → %  and  ? → _   (SQL LIKE under the hood)
curl "$H/dicts/mw/restful/entries?field=headword_slp1&query=agni*&query_type=wildcard"
```

`fuzzy` is presently approximated by `prefix` (matches `getsuggest`'s behaviour). `regexp`,
`match`, and `match_phrase` return **HTTP 400** until the Phase-4 body index lands — by design,
never a silent empty result.

## 6. Get the headword in Devanagari or IAST

Transliteration is computed per entry — no extra call:

```sh
curl "$H/dicts/mw/restful/entries?field=headword_slp1&query=agni&query_type=term"
# → csl.headwordDeva = "अग्नि", csl.headwordIast = "agni", csl.accentedKey = "agni/"
```

`csl.text` is transcode-clean prose. `csl.html` is still SLP1 display-tagged in Phase 1
(carries `<SA>…</SA>` + stray `</s1>`/`</H1>`); apply `transcoder_processElements` for final
display HTML (Phase 5).

## 7. Link to the scanned page

Every entry carries `csl.scanUrl`, a relative permalink:

```
"scanUrl": "/MW/page/5"      →  $H/MW/page/5
```

Prefix the host to send a reader straight to the MW scan for that lnum's page/column.

## 8. Embed cross-domain (JSONP)

For a `<script>`-tag client on another origin (CORS is already open with `*`, so prefer
`fetch`; JSONP is the legacy fallback):

```html
<script src="https://sanskrit-lexicon.uni-koeln.de/dicts/mw/restful/entries?field=headword_slp1&query=agni&query_type=term&callback=showAgni"></script>
```

The response is wrapped as `showAgni({...})`. The callback name must match
`^[A-Za-z_$][A-Za-z0-9_$.]{0,127}$`; anything else returns `400 invalid callback` (the
reflected-XSS guard).

## 9. GraphQL with field projection

Ask for only the fields you need (one POST):

```sh
curl -X POST "$H/dicts/mw/graphql" -H 'content-type: application/json' -d '{
  "query": "{ entries(field: headword_slp1, query: \"agni\", queryType: term, size: 2) { id headwordSlp1 csl { lnum page column } } }"
}'
```

Returns only `id`, `headwordSlp1`, and the three `csl` fields requested. Note the **camelCase**
GraphQL spelling (`queryType`, `headwordSlp1`) vs. REST snake_case — both match C-SALT.
`ids(ids: [...])` needs an array variable in the JSON body (see [`salt_graphql.md`](salt_graphql.md)
§3.4.1).

## 10. Migrate an existing C-SALT client (the whole point)

The REST query form, GraphQL schema, envelope shape, and `lemma-…` id scheme mirror
`api.c-salt.uni-koeln.de`. To repoint a client written for C-SALT:

1. Swap the host → `sanskrit-lexicon.uni-koeln.de`.
2. Headword search, ids, envelopes, and GraphQL field names map directly; Phase 1
   caveats remain explicit: unsupported fields/body-search modes return 400, and GraphQL
   `ids` currently needs variables until webonyx is wired.
3. Two CSL-only conveniences are additive (ignore them and you have plain C-SALT): the
   `csl{}` block (page/column/scanUrl/translit/html), and the `input`/`output`/`accent`
   query params for display transliteration.

**Known divergences to expect** during the Phase-3 parity pass (none break the contract):
`sense` / `re_headwords_slp1` are `[]` and `xml` (TEI) is `null` until Phase 5; the
`-L{lnum}` id fallback addresses sub-records the C-SALT `-{n}` scheme does not number; and the
`prefix` `size` unit (records vs. headwords) is still to be confirmed.
