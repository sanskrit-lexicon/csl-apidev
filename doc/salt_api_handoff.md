# Salt API — implementation handoff (Phase 1)

Status: the `api1/` controllers are **wired to the real data layer** (`Dal` / `Getword_data` /
transcoder) and **run-verified end-to-end (2026-06-14)** against the real MW `mw.sqlite`
(286,560 records, from the `csl-sqlite` release — see §2.1). All eight files pass `php -l`
(PHP 8.2, linted in CI); `php api1/salt_selftest.php mw agni indra ka` exits 0 with no
warnings and returns structurally-correct envelopes for **all three faces** — `entries`
(term + prefix), `ids` (batch), and `graphql entries`: correct `data.entries[]` / `data.ids[]`
shape, populated `csl.{lnum,page,column,scanUrl,references,accentedKey}`, and working
transliteration (`agni → अग्नि` / `agni`; `agni` resolves to MW p.5 col.1, lnum 890). An
earlier parser bug — `arg()` truncating `query:"a*"` to `"a"` (wildcards/diacritics/spaces
dropped) — is fixed.

**Open items the run surfaced** — all *contract / Phase-5* decisions for the parity pass (§7),
not wiring faults:

1. **`id` uniqueness — FIXED (2026-06-14).** Multi-record headwords previously shared one id
   (`agni`×5, `indra`×17 → `lemma-{key}`; `ka` mixed `lemma-ka-1/-2` with a bare colliding
   `lemma-ka`). `salt_entry_from_record` now disambiguates: `<hom>` present → `-{n}` (C-SALT
   form, unchanged); no `<hom>` → `-L{lnum}` fallback (the Cologne lnum is per-record unique).
   `salt_entries_for_id` parses both forms back, so `ids` resolves a single record. Verified:
   `ka` → `lemma-ka-1`, `lemma-ka-2`, `lemma-ka-L41336.05`, `lemma-ka-L41336.1`,
   `lemma-ka-L41336.2` (all unique); `ids=lemma-agni-L890,lemma-agni-L891` returns exactly
   those two records. **Parity note:** `-L{lnum}` is a sanctioned divergence from C-SALT's
   `lemma-{key}-{n}` for sub-records the source does not number; confirm in §7 whether C-SALT
   emits these sub-records at all, and reconcile in [`SALT_API_PROFILE.md`](https://github.com/sanskrit-lexicon/csl-standards/blob/salt-api-profile/docs/SALT_API_PROFILE.md).
2. **`prefix` returns successive records of the first headword, not distinct headwords**
   (`prefix agni` size 8 → 8 `agni` records, lnum 890–897, never reaching `agnika…`). This may
   be parity-correct for "entries matching a prefix" — confirm the intended `size` unit against
   C-SALT (records vs. headwords).
3. **`csl.html` still carries SLP1 display tags + stray `</s1>`/`</H1>` and untranscoded
   `<SA>`** — the `transcoder_processElements` item in §4. `csl.text` is clean.

`sense` / `re_headwords_slp1` / TEI `xml` remain TODO (Phase 5). This page is the one-stop
guide to test, deploy, and finish it.

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

### 2.1 Get the per-dict data

The smoke test needs the dictionary's own `*.sqlite` (the controllers read records via `Dal`).
`download_hwnorm1c_sqlite.sh` fetches only the headword-normalisation helper — **not** the
per-dict data. Get that from the [`csl-sqlite` releases](https://github.com/sanskrit-lexicon/csl-sqlite/releases/latest)
(`{dict}.zip`, e.g. `mw.zip` ≈ 26 MB) and place it where `dictinfo.php` looks in the xampp
layout — `../{dict}/web/sqlite/`, beside the `csl-apidev` directory:

```sh
gh release download --repo sanskrit-lexicon/csl-sqlite --pattern 'mw.zip'
unzip -o mw.zip -d _mw && mkdir -p ../mw/web/sqlite && mv _mw/*.sqlite ../mw/web/sqlite/
php api1/salt_selftest.php mw agni indra ka
```

Verified 2026-06-14 with `mw.sqlite` (286,560 records). `keydoc.sqlite` is not shipped in the
bundle and is optional — without it `Dal::get1_mwalt` falls back to its `get4b` gap-filling
path, which is what this run exercised. The xampp path is derived in `dictinfo::get_webPath()`
from a directory literally named `csl-apidev`, so run from a clone/worktree with that exact
basename.

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
| `html` is SLP1-tagged (pre-final-transcode) | **Confirmed 2026-06-14**: `csl.html` shows stray `</s1>`/`</H1>` and untranscoded `<SA>…</SA>`. If clients want display-script HTML, apply `transcoder_processElements($body,'slp1',$filter,'SA')` (as `getwordClass` does). `csl.text` is already clean. |
| `<ls>` reference extraction | The flat `references` list is a heuristic; confirm/adjust the tag per dictionary. (2026-06-14: `agni`→`["Uṇ."]`, sane.) |
| `id` uniqueness / homonym suffix vs C-SALT | **Fixed 2026-06-14** — `<hom>` → `-{n}` (C-SALT), no `<hom>` → `-L{lnum}` fallback; every record now has a unique id and the `ids` face resolves a single record (verified `ka`, `agni`, `indra`). Still confirm against §7 whether C-SALT emits the un-numbered sub-records and reconcile the `-L{lnum}` divergence in the profile. |

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
