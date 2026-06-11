# Salt API: ids

Batch-fetch entries by id. Mirrors the C-SALT / Kosh `/dicts/{id}/restful/ids` contract:
this is a **get-by-id**, not a search. Each id is an `lemma-{headword_slp1}` token (§
[salt_entries](salt_entries.md)); internally it resolves to a `lnum` lookup via the
existing `getword` record path.

Normative contract: `csl-standards/docs/SALT_API_PROFILE.md` §5.

## 2. Fetch entries by id

### 2.1. URL

https://www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/salt_ids.php?dict=mw&ids=lemma-agni&ids=lemma-indra

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
2. ids — `lemma-{headword_slp1}` or `lemma-{headword_slp1}-{n}` tokens.

### 2.6. Defaults

None for `dict`/`ids` (mandatory). `input=slp1`, `output=deva`, `accent=no`.

### 2.7. Rewrite rules

```
RewriteRule ^dicts/([^/]*)/restful/ids$  /scans/awork/apidev/salt_ids.php?dict=$1  [QSA,L]
```

### 2.8. Expected output

```json
{ "data": { "ids": [ /* Entry objects, same shape as salt_entries §1.8 */ ] } }
```

### 2.9. Questions

1. Should `ids` also accept a bare `lnum` (e.g. `144239`) as a convenience, in addition to
   the C-SALT `lemma-…` form? (C-SALT only accepts its own id.)
