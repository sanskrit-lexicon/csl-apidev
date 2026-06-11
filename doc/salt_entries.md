# Salt API: entries

C-SALT-compatible search over one dictionary. This mirrors the C-SALT / Kosh
`/dicts/{id}/restful/entries` contract (verified live against
`api.c-salt.uni-koeln.de/dicts/mw` on 2026-06-11) so that a client written for the C-SALT
APIs works against `sanskrit-lexicon.uni-koeln.de` unchanged.

Render path is unchanged: this endpoint wraps the existing `getword` data (see
[getword](getword.md)) in the Salt JSON envelope. Parameters reuse the `Parm` class
([restfulparm](restfulparm.md)). Normative contract: `csl-standards/docs/SALT_API_PROFILE.md`
(+ `data/schema/salt-api.openapi.yaml`).

## 1. Search entries

### 1.1. URL

https://www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/salt_entries.php?dict=mw&field=headword_slp1&query=agni&query_type=term&size=10

### 1.2. Input parameters

| restful | example | Parm / notes |
|---|---|---|
| dict | mw | Cologne dict code, lower-cased ([restfulparm](restfulparm.md) `dict`). Pilot: `mw` only. |
| field | headword_slp1 | one of `id`, `headword_slp1`, `sense`, `re_headwords_slp1`, `created`, `xml` |
| query | agni | the search string, in the `input` transliteration (Parm `keyin`) |
| query_type | term | one of `term`, `fuzzy`, `match`, `match_phrase`, `prefix`, `wildcard`, `regexp` |
| size | 10 | max records; optional |
| input | slp1 | = restful `input`/`transLit` (Parm `filterin`) |
| output | deva | = restful `output`/`filter` (Parm `filter`) |
| accent | no | Parm `accent` |

`input`, `output`, `accent` are CSL extensions; C-SALT has no equivalents. They MUST NOT
change the meaning of `field`/`query`/`query_type`.

### 1.3. Suggested Clean URL

C-SALT-identical form:
```
/dicts/{id}/restful/entries?field={field}&query={query}&query_type={query_type}&size={size}
```
Permalink form (subsumes the `cleanurl` roadmap, COLOGNE#249):
```
/{dict}/{ref}            ref = headword (any input transliteration) or lnum
```

### 1.4. Examples

1. https://sanskrit-lexicon.uni-koeln.de/dicts/mw/restful/entries?field=headword_slp1&query=agni&query_type=term
2. https://sanskrit-lexicon.uni-koeln.de/dicts/mw/restful/entries?field=headword_slp1&query=agn&query_type=prefix&size=25
3. https://sanskrit-lexicon.uni-koeln.de/MW/agni        (permalink, by headword)
4. https://sanskrit-lexicon.uni-koeln.de/MW/144239      (permalink, by lnum)

### 1.5. Allowable values

1. dict — the Cologne dict codes ([restfulparm](restfulparm.md) `dict`). Pilot: `mw`.
2. field — `id` / `headword_slp1` / `sense` / `re_headwords_slp1` / `created` / `xml`.
3. query_type — `term` / `fuzzy` / `match` / `match_phrase` / `prefix` / `wildcard` / `regexp`.
4. input, output — `s/d/h/r/i` (slp1/deva/hk/roman/itrans).
5. accent — `y/n`.

### 1.6. Defaults

1. field — `headword_slp1`
2. query_type — `term`
3. size — `25`
4. input — `slp1`
5. output — `deva`
6. accent — `no`

### 1.7. Rewrite rules

```
# C-SALT-identical query form → salt_entries.php (base path scans/awork/apidev)
RewriteRule ^dicts/([^/]*)/restful/entries$  /scans/awork/apidev/salt_entries.php?dict=$1  [QSA,L]

# Permalink (subsumes cleanurl / COLOGNE#249): /{DICT}/{ref}, ref = headword or lnum.
# 'restful' and 'graphql' are reserved and not valid {dict} values.
RewriteRule ^([A-Za-z0-9]+)/([^/]+)$  /scans/awork/apidev/salt_entries.php?dict=$1&query=$2  [L]
```

### 1.8. Expected output

```json
{
  "data": {
    "entries": [
      {
        "id": "lemma-agni",
        "headword_slp1": "agni",
        "sense": ["fire, sacrificial fire …", "the number three …"],
        "re_headwords_slp1": ["agnikaRa", "agnikarman", "agnikalpa"],
        "created": "2026-06-11T00:00:00",
        "xml": null,
        "csl": {
          "lnum": "144239",
          "page": "5", "column": "1",
          "scanUrl": "https://sanskrit-lexicon.uni-koeln.de/MW/page/5",
          "html": "…", "text": "…",
          "xmlCsl": "<H1>…</H1>",
          "references": ["RV.", "AV.", "MBh."],
          "headwordDeva": "अग्नि", "headwordIast": "agni",
          "accentedKey": "agn/i"
        }
      }
    ]
  }
}
```
Notes:
- `id` matches C-SALT exactly: `lemma-{headword_slp1}`, or `lemma-{headword_slp1}-{n}` for
  homonyms (`ka` → `lemma-ka-1`…`-4`). Build from `keyin` + the `hc1` homonym number.
- `xml` is the TEI-P5 body and is `null` until TEI conversion ships (roadmap Phase 5); it
  MUST NOT contain CSL display-XML. The display-XML is `csl.xmlCsl`, available now.
- `csl.lnum` is the existing `lnum` ([restfulparm](restfulparm.md)); it equals the TEI
  `monier_<lnum>` on the C-SALT side.

### 1.9. Questions

1. `match` / `match_phrase` over `field=sense` or `field=xml` need a body index not built
   for the pilot. Return HTTP 400 until Phase 4 (Elasticsearch or SQLite FTS5), never a
   silent empty result. OK?
2. Reuse `Parm` `input`/`output`/`accent` as shown, or expose `transLit`/`filter` synonyms
   at the Salt surface too?
3. Confirm CSL homonym ordering matches C-SALT's `-1`/`-2`/… for a sample (Phase 3).
