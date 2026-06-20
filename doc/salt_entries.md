# Salt API: entries

C-SALT-compatible search over one dictionary. This mirrors the C-SALT / Kosh
`/dicts/{id}/restful/entries` contract (verified live against
`api.c-salt.uni-koeln.de/dicts/mw` on 2026-06-11) so that a client written for the C-SALT
APIs uses the same endpoint shapes against `sanskrit-lexicon.uni-koeln.de`, with Phase 1
caveats documented in the Salt specs.

Render path is unchanged: this endpoint wraps the existing `getword` data (see
[getword](getword.md)) in the Salt JSON envelope. Parameters reuse the `Parm` class
([restfulparm](restfulparm.md)). Normative contract: `csl-standards/docs/SALT_API_PROFILE.md`
(+ `data/schema/salt-api.openapi.yaml`).

## 1. Search entries

### 1.1. URL

https://www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/api1/salt_entries.php?dict=mw&field=headword_slp1&query=agni&query_type=term&size=10

### 1.2. Input parameters

| restful | example | Parm / notes |
|---|---|---|
| dict | mw | Cologne dict code, lower-cased ([restfulparm](restfulparm.md) `dict`). Pilot: `mw` only. |
| field | headword_slp1 | one of `id`, `headword_slp1`, `sense`, `re_headwords_slp1`, `created`, `xml`; Phase 1 implements `headword_slp1` only |
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
RewriteRule ^dicts/([^/]*)/restful/entries$  /scans/awork/apidev/api1/salt_entries.php?dict=$1  [QSA,L]

# Permalink (subsumes cleanurl / COLOGNE#249): /{DICT}/{ref}, ref = headword or lnum.
# 'restful' and 'graphql' are reserved and not valid {dict} values.
RewriteRule ^([A-Za-z0-9]+)/([^/]+)$  /scans/awork/apidev/api1/salt_entries.php?dict=$1&query=$2  [L]
```

> **Reconciliation note — see [cleanurl](cleanurl.md) §0.** The permalink rule above is
> collision-unsafe as written: `^([A-Za-z0-9]+)/([^/]+)$` captures *every* two-segment
> root path (`/images/x`, `/php/x`, `/css/x`, …), not only dictionaries — reserving just
> `restful`/`graphql` is insufficient. The unified `/{DICT}/{ref}` rewrite must
> (a) restrict `{dict}` to the **dict-code whitelist** in [cleanurl](cleanurl.md) §4;
> (b) **content-negotiate** — `Accept: text/html` → the listview display
> (`cleanurl.php`), `Accept: application/json` (or `?format=json`) → `salt_entries.php`
> as above; and (c) preserve the homonym form `/{DICT}/{KEY}/{HOM}` and decimal `lnum`
> (`/MW/144239.1`) from [cleanurl](cleanurl.md) §3.

### 1.8. Expected output

**Real response** — `query=agni&query_type=term`, the first of 5 records, captured from the
run-verified Phase-1 build (2026-06-14, against the real `mw.sqlite`, 286,560 records). Only
the `html` is abbreviated (`…`); every other value is verbatim:

```json
{
  "data": {
    "entries": [
      {
        "id": "lemma-agni-L890",
        "headword_slp1": "agni",
        "sense": [],
        "re_headwords_slp1": [],
        "created": null,
        "xml": null,
        "csl": {
          "lnum": "890",
          "page": "5",
          "column": "1",
          "scanUrl": "/MW/page/5",
          "html": "<span class='sdata_siddhanta'><SA>agni</SA></span>   <span title='masculine gender' …>m.</span> (√<span …>ag</span>, <span …>Uṇ.</span>) fire, sacrificial fire …</H1>",
          "text": "agni   m. (√ag, Uṇ.) fire, sacrificial fire (of three kinds, Gārhapatya, Āhavanīya, and Dakṣiṇa)",
          "xmlCsl": "<H1><h><key1>agni</key1><key2>agni/</key2></h><body><s>agni/</s>   <lex>m.</lex> (√ <s>ag</s>, <ls>Uṇ.</ls>) fire, sacrificial fire …</body><tail><L>890</L><pc>5,1</pc></tail></H1>",
          "references": ["Uṇ."],
          "headwordDeva": "अग्नि",
          "headwordIast": "agni",
          "accentedKey": "agni/"
        }
      }
    ]
  }
}
```

**Which fields are live now (Phase 1) vs. deferred:**

| Field | Phase 1 status |
|---|---|
| `id`, `headword_slp1`, `csl.{lnum,page,column,scanUrl,references,headwordDeva,headwordIast,accentedKey}` | **populated, verified** |
| `csl.text` | **populated** — plain text, transcode-clean |
| `csl.html` / `csl.xmlCsl` | **populated** but SLP1 display-tagged: `html` still carries `<SA>…</SA>` and stray `</s1>`/`</H1>` — apply `transcoder_processElements` for final display (Phase 5). `xmlCsl` is the CSL display-XML (not TEI). |
| `sense`, `re_headwords_slp1` | `[]` — Phase 5 (TEI-grade sense split / run-ons) |
| `created`, `xml` | `null` — `xml` is the TEI-P5 body, Phase 5; it MUST NOT carry display-XML (that is `csl.xmlCsl`) |

Notes:
- **`id` scheme.** `lemma-{headword_slp1}` for a single-record headword; for multi-record
  headwords each record is disambiguated so the `ids` face can address it individually:
  `-{n}` when the source carries a `<hom>` number (matches C-SALT, e.g. `lemma-ka-1`,
  `lemma-ka-2`), else **`-L{lnum}`** (the per-record Cologne `lnum`, e.g. `lemma-agni-L890`).
  The `-L{lnum}` fallback is a **sanctioned divergence** from C-SALT's `-{n}` for sub-records
  the print does not number — reconcile in the profile during the Phase-3 parity pass (§1.9 Q3).
- `csl.lnum` is the existing `lnum` ([restfulparm](restfulparm.md)); it equals the TEI
  `monier_<lnum>` on the C-SALT side.
- `csl.scanUrl` is a relative permalink (`/MW/page/{page}`); clients prefix the host.

### 1.9. Questions

1. `match` / `match_phrase` over `field=sense` or `field=xml` need a body index not built
   for the pilot. Return HTTP 400 until Phase 4 (Elasticsearch or SQLite FTS5), never a
   silent empty result. OK?
2. Reuse `Parm` `input`/`output`/`accent` as shown, or expose `transLit`/`filter` synonyms
   at the Salt surface too?
3. Confirm CSL homonym ordering matches C-SALT's `-1`/`-2`/… for a sample, and reconcile the
   `-L{lnum}` fallback (§1.8) against what C-SALT emits for un-numbered sub-records (Phase 3).

### 1.10. `query_type` behaviour (Phase 1)

How each mode is served today. The headword modes run over the existing key index via `Dal`
(no new runtime); the body modes await a Phase-4 index. `query` is transcoded to SLP1 first,
so the same word works in any `input` script.

| `query_type` | Phase-1 backing | Notes |
|---|---|---|
| `term` | exact key (the transcoded `query`) | returns every record under that headword |
| `prefix` | `Dal::get3c` — `key LIKE 'q%'`, distinct keys | suggest/browse; `size` caps **records**, not headwords (see §1.10 note) |
| `wildcard` | `Dal::get3b` — `*`→`%`, `?`→`_` | e.g. `query=agni*ra` |
| `fuzzy` | approximated by `prefix` | matches `getsuggest`'s behaviour; revisit in Phase 4 |
| `regexp`, `match`, `match_phrase` | **HTTP 400** | need a body/FTS index (Phase 4); never a silent empty result |

Phase 1 also returns **HTTP 400** for `field` values other than `headword_slp1`. The
C-SALT enum is preserved, but `id`, `sense`, `re_headwords_slp1`, `created`, and `xml`
search need a later resolver/index and must not silently run a headword search.

> **`size` unit (open, Phase-3 parity).** `prefix agni&size=8` returns the first 8 *records*
> of `agni` (lnum 890–897), not 8 distinct headwords — the cap counts entries. Confirm against
> C-SALT whether `size` should count records or headwords before relying on it for paging.

### 1.11. Errors and JSONP

- **400** — unsupported `query_type` (`regexp`/`match`/`match_phrase` in Phase 1),
  unsupported `field` (anything except `headword_slp1` in Phase 1), or an unknown `field`.
  The body is a JSON error envelope; the cause is named, not swallowed.
- **JSONP** — append `&callback={fn}` to wrap the JSON in `fn(...)`. The callback name is
  validated against `^[A-Za-z_$][A-Za-z0-9_$.]{0,127}$`; anything else returns
  `400 invalid callback` (reflected-XSS guard — see [`salt_api_handoff.md`](salt_api_handoff.md)).
- **CORS** — every response sends `Access-Control-Allow-Origin: *` (matches C-SALT, open).
- An empty `entries` array is a valid "no match", **not** an error.
