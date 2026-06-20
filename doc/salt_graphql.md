# Salt API: graphql

C-SALT-compatible GraphQL face. Mirrors the C-SALT / Kosh GraphQL schema verified live
(introspected against `api.c-salt.uni-koeln.de/dicts/mw/graphql`, 2026-06-11). Two root
fields only: `entries` (search) and `ids` (get-by-id). Resolvers MUST call the same
`getword` data path as the REST endpoints ([salt_entries](salt_entries.md)) so the two
faces cannot diverge.

Schema (authoritative): `csl-standards/data/schema/salt-api.graphql`.
Recommended PHP library: `webonyx/graphql-php` (provisional).

## 3. GraphQL endpoint

### 3.1. URL

POST https://www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/api1/salt_graphql.php?dict=mw

Clean form: `POST /dicts/{id}/graphql`.

### 3.2. Casing note

GraphQL uses camelCase (`queryType`, `headwordSlp1`, `reHeadwordsSlp1`); the REST face
uses the snake_case forms (`query_type`, `headword_slp1`, `re_headwords_slp1`). Same
concepts, two spellings — this matches C-SALT.

Phase 1 implements `field: headword_slp1` only. Other C-SALT field enum values return a
GraphQL error until the corresponding Phase 4/5 resolver or index exists; this mirrors the
REST face and avoids silent empty results.

### 3.3. Example queries

```graphql
# search
{ entries(field: headword_slp1, query: "agni", queryType: term, size: 1) {
    id headwordSlp1 sense reHeadwordsSlp1 created xml
    csl { lnum page column scanUrl html text xmlCsl references headwordDeva headwordIast accentedKey }
} }

# get-by-id
query($ids: [String!]!) { ids(ids: $ids) { id headwordSlp1 xml } }
```

### 3.4. Expected output

Same data as the REST face, keyed under `data.entries` / `data.ids`, with camelCase fields.
**Real response** (verified 2026-06-14) for `{ entries(field: headword_slp1, query: "agni",
queryType: term, size: 2) { id headwordSlp1 csl { lnum page column } } }`:

```json
{
  "data": {
    "entries": [
      { "id": "lemma-agni-L890", "headwordSlp1": "agni", "csl": { "lnum": "890", "page": "5", "column": "1" } },
      { "id": "lemma-agni-L891", "headwordSlp1": "agni", "csl": { "lnum": "891", "page": "5", "column": "1" } }
    ]
  }
}
```

Only the selected fields are returned (GraphQL projection). The `id` scheme is identical to
REST ([salt_entries](salt_entries.md) §1.8), including the `-L{lnum}` fallback.

For the current hand-rolled Phase-1 dispatcher, send ids through JSON variables, for example:

```json
{
  "query": "query($ids: [String!]!) { ids(ids: $ids) { id headwordSlp1 xml } }",
  "variables": { "ids": ["lemma-ka-1", "lemma-ka-2"] }
}
```

### 3.4.1. Parser notes

- The Phase-1 hand-rolled dispatcher reads literal args (`field`, `query`, `queryType`,
  `size`). An earlier bug — `arg()` truncating `query:"a*"` to `"a"` (dropping wildcards,
  diacritics, spaces) — is fixed; quoted strings are parsed whole.
- `entries` arguments may be supplied inline (as above). `ids(ids: [...])` needs an **array
  variable**, i.e. a `php://input` JSON body — exercise it over HTTP, not from the CLI
  self-test (which has no request body). The `webonyx/graphql-php` block (§3.6 Q1) is the
  production path.

### 3.5. Rewrite rules

```
RewriteRule ^dicts/([^/]*)/graphql$  /scans/awork/apidev/api1/salt_graphql.php?dict=$1  [QSA,L]
```

### 3.6. Questions

1. `webonyx/graphql-php` Composer dependency on the Cologne host, or a minimal hand-rolled
   resolver for just `entries`/`ids`? (Recommendation: `webonyx/graphql-php`.)
2. The live C-SALT service names the entry object type after the dict (`mw`, `ap90`, …).
   Follow that, or use a single `Entry` type? (Profile uses `Entry`.)
