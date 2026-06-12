# Salt API — implementation handoff (Phase 1)

Status: the `api1/` controllers are a **wired skeleton** — connected to the real data layer
(`Dal` / `Getword_data` / transcoder) but **NOT run-verified** (authored without a PHP
runtime or the per-dict `*.sqlite` databases). This page is the one-stop guide to test,
deploy, and finish it.

- Contract (normative): [`SALT_API_PROFILE.md`](https://github.com/sanskrit-lexicon/csl-standards/blob/salt-api-profile/docs/SALT_API_PROFILE.md) · [`salt-api.openapi.yaml`](https://github.com/sanskrit-lexicon/csl-standards/blob/salt-api-profile/data/schema/salt-api.openapi.yaml) · [`salt-api.graphql`](https://github.com/sanskrit-lexicon/csl-standards/blob/salt-api-profile/data/schema/salt-api.graphql)
- Plan: [`SALT_API_INTEGRATION_ROADMAP.md`](https://github.com/sanskrit-lexicon/csl-standards/blob/salt-api-profile/docs/SALT_API_INTEGRATION_ROADMAP.md) · Divergences: [`SALT_API_LOSS_REPORT.md`](https://github.com/sanskrit-lexicon/csl-standards/blob/salt-api-profile/docs/SALT_API_LOSS_REPORT.md)
- Endpoint specs: [`salt_entries.md`](salt_entries.md) · [`salt_ids.md`](salt_ids.md) · [`salt_graphql.md`](salt_graphql.md)

## 1. Files

| File | Role |
|---|---|
| [`api1/salt_common.php`](../api1/salt_common.php) | shared search + envelope builder (`salt_search_entries`, `salt_entries_for_id`, `salt_entries_for_key`, `salt_translit`) |
| [`api1/salt_entries.php`](../api1/salt_entries.php) + `salt_entriesClass.php` | `GET /dicts/{id}/restful/entries` |
| [`api1/salt_ids.php`](../api1/salt_ids.php) + `salt_idsClass.php` | `GET /dicts/{id}/restful/ids` (batch by id) |
| [`api1/salt_graphql.php`](../api1/salt_graphql.php) + `salt_graphqlClass.php` | `POST /dicts/{id}/graphql` (`entries`, `ids`) |
| [`api1/salt_selftest.php`](../api1/salt_selftest.php) | CLI smoke test (§2) |

It reuses the existing pipeline (no new runtime): `Dal` for headword search, `Getword_data`
for per-record rendering, `transcoder_processString` for transliteration.

## 2. Smoke test (one command)

```sh
php api1/salt_selftest.php mw agni indra ka
```

It drives the three controller classes for the given headwords and prints each JSON
envelope — `entries` (term + prefix), `ids`, and `graphql entries`. Run it from the **repo
root** (the controllers `chdir` to root, and the `{dict}.sqlite` paths resolve from there).

- Non-empty entries with sane `lnum`/`page`/`headword_slp1` → the happy path works.
- **Empty entries** → usually the `mw.sqlite` DB isn’t found from the repo root, or a
  `VERIFY:` assumption (§4) needs adjusting.
- A hard `exit(1)` mid-run → a record tripped a parser `exit` inside `getword_data.php`;
  note the headword and inspect that record.

## 3. Deploy (Apache rewrites)

Controllers live under the existing base path `scans/awork/apidev/api1/`. Add these beside
the current `getword`/`servepdf`/`list` rules (and lower-case the dict in the handler):

```apache
# REST — C-SALT-identical query form
RewriteRule ^dicts/([^/]*)/restful/entries$  /scans/awork/apidev/api1/salt_entries.php?dict=$1  [QSA,L]
RewriteRule ^dicts/([^/]*)/restful/ids$      /scans/awork/apidev/api1/salt_ids.php?dict=$1      [QSA,L]
# GraphQL
RewriteRule ^dicts/([^/]*)/graphql$          /scans/awork/apidev/api1/salt_graphql.php?dict=$1  [QSA,L]
```

The human permalink `/{DICT}/{ref}` (headword or lnum) is **not** added here — it stays with
[`cleanurl`](cleanurl.md) as its HTML + collision-safe-routing face, per
[`salt_entries.md`](salt_entries.md) §1.7 (whitelist the dict code, content-negotiate
`Accept: application/json` → `salt_entries.php`).

## 4. VERIFY: punch-list (assumptions to confirm against real data)

Each is also flagged `VERIFY:` at the relevant line in [`salt_common.php`](../api1/salt_common.php).

| Assumption | What to confirm |
|---|---|
| MW `<info>` format `page,col:hcode:key2:hom:hui` | `page`/`column`/`accentedKey`/homonym suffix come out right for MW (`agni`, `ka`). |
| Non-MW `<info>` = page reference | Check a non-MW dict (e.g. `ap90`, `pwg`) — the page format differs; adjust `salt_entry_from_record`. |
| `Getword_data(false)` via `$_REQUEST` | Acceptable, or refactor `Getword_data` to take a direct `(dict, key)` constructor (cleaner, avoids the `$_REQUEST` save/restore). |
| `html` is SLP1-tagged (pre-final-transcode) | If clients want display-script HTML, apply `transcoder_processElements($body,'slp1',$filter,'SA')` (as `getwordClass` does). |
| `<ls>` reference extraction | The flat `references` list is a heuristic; confirm/adjust the tag per dictionary. |
| `id` homonym suffix vs C-SALT | `lemma-ka-1..4` ordering matches C-SALT (run §7 parity). |

## 5. TODO (deferred, by phase)

- **Phase 4** — body search: `regexp` / `match` / `match_phrase` currently return HTTP 400.
  Add an FTS index (SQLite FTS5 keeps the no-new-runtime property; Elasticsearch is the
  Kosh-identical option) and implement `field=sense`/`field=xml` search.
- **Phase 5** — `sense[]`, `re_headwords_slp1[]` (currently `[]`), and the TEI `xml` field
  (currently `null`). These come from the CDSL→TEI conversion; see the roadmap.
- **Cleanup** — give `Getword_data` a direct constructor (drop the `$_REQUEST` reuse);
  decide whether `csl.html` should be display-script or SLP1-tagged.

## 6. Open questions (recommended defaults — all provisional, none blocking)

1. **GraphQL library** — `webonyx/graphql-php` (a wiring block is in
   [`salt_graphqlClass.php`](../api1/salt_graphqlClass.php)), or keep the hand-rolled
   dispatcher? *Default: webonyx for production.*
2. **`id` / `lnum` stability** — is `lnum` stable across the server’s
   `redo_xampp_selective.sh` refresh? *Default: yes; `id = lemma-{key}[-{n}]` matches
   C-SALT, `lnum` exposed in `csl.lnum`.*
3. **CORS / auth / rate-limit** — *Default: match C-SALT (open), subject to the host’s
   usual limits.* The controllers already send `Access-Control-Allow-Origin: *`.
4. **`input`/`output` vs `transLit`/`filter`** — the Salt params reuse `Parm`’s
   `input`/`output`/`accent`. Expose `transLit`/`filter` synonyms too? *Default: reuse as-is.*

## 7. Parity check (Phase 3)

Once MW answers on the server:

```sh
python data/pilot/parity_mw.py --csl-base https://sanskrit-lexicon.uni-koeln.de
```

(in the `csl-standards` repo — [`parity_mw.py`](https://github.com/sanskrit-lexicon/csl-standards/blob/salt-api-profile/data/pilot/parity_mw.py)).
It diffs entry `id`s/counts against `api.c-salt.uni-koeln.de/dicts/mw`. Divergences are
expected where CSL covers homonyms or scan apparatus the 7-dictionary derivative does not.
Use the results to decide 7-vs-40 dictionary scope.
