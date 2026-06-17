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

POST https://www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/salt_graphql.php?dict=mw

Clean form: `POST /dicts/{id}/graphql`.

### 3.2. Casing note

GraphQL uses camelCase (`queryType`, `headwordSlp1`, `reHeadwordsSlp1`); the REST face
uses the snake_case forms (`query_type`, `headword_slp1`, `re_headwords_slp1`). Same
concepts, two spellings — this matches C-SALT.

### 3.3. Example queries

```graphql
# search
{ entries(field: headword_slp1, query: "agni", queryType: term, size: 1) {
    id headwordSlp1 sense reHeadwordsSlp1 created xml
    csl { lnum page column scanUrl html text xmlCsl references headwordDeva headwordIast accentedKey }
} }

# get-by-id
{ ids(ids: ["lemma-ka-1", "lemma-ka-2"]) { id headwordSlp1 xml } }
```

### 3.4. Expected output

```json
{ "data": { "entries": [ { "id": "lemma-agni", "headwordSlp1": "agni", "...": "..." } ] } }
```

### 3.5. Rewrite rules

```
RewriteRule ^dicts/([^/]*)/graphql$  /scans/awork/apidev/salt_graphql.php?dict=$1  [QSA,L]
```

### 3.6. Questions

1. `webonyx/graphql-php` Composer dependency on the Cologne host, or a minimal hand-rolled
   resolver for just `entries`/`ids`? (Recommendation: `webonyx/graphql-php`.)
2. The live C-SALT service names the entry object type after the dict (`mw`, `ap90`, …).
   Follow that, or use a single `Entry` type? (Profile uses `Entry`.)
