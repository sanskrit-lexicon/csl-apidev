# Salt API: multidict

Resolve a headword across **all** Cologne dictionaries in one call. This is a
CSL extension — there is no C-SALT equivalent (C-SALT covers 7 dictionaries;
CSL covers 45+). Internally it uses `Dalglob` (`keydoc_glob1`) to find which
dictionaries contain the headword, then calls `salt_entries_for_key()` (the
same render path as [salt_entries](salt_entries.md)) per dictionary.

Normative contract: single-dict entry shape matches `csl-standards/docs/SALT_API_PROFILE.md` §1–§4.

## 1. Multi-dictionary lookup

### 1.1. URL

```
https://www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/api1/salt_multidict.php?key=agni
```

### 1.2. Input parameters

| parameter | example | notes |
|---|---|---|
| key | agni | Headword in the `input` transliteration scheme. Required. |
| input | slp1 | One of `slp1`, `deva`, `hk`, `roman`, `itrans`, `velthuis`. Default `slp1`. |
| output | deva | Output transliteration for `csl.html` / `csl.text`. One of `deva`, `slp1`, `hk`, `roman`. Default `deva`. |
| size | 3 | Max entries **per dictionary**; 0 = unlimited (default). |
| field | id,html,lnum | Comma-separated response fields; bare csl sub-field names (`html`, `lnum`, `text`, …) are auto-scoped to the `csl` object. Use `csl` for the full csl object. Default: all fields. |

`input`, `output`, `size`, `field` follow the same convention as [salt_entries](salt_entries.md) §1.2.

### 1.3. Suggested Clean URL

```
/multidict/{key}?input={input}
```

### 1.4. Examples

1.  SLP1 input (default output=deva):

    ```
    https://sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/api1/salt_multidict.php?key=agni
    ```

2.  Devanagari input:

    ```
    https://sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/api1/salt_multidict.php?key=अग्नि&input=deva
    ```

3.  IAST input with size cap:

    ```
    https://sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/api1/salt_multidict.php?key=agni&input=roman&size=2
    ```

4.  Field-limited (top-level only, no csl):

    ```
    https://sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/api1/salt_multidict.php?key=agni&field=headword_slp1
    ```

5.  CSL sub-fields only (bare names, no prefix):

    ```
    https://sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/api1/salt_multidict.php?key=agni&field=id,html,lnum
    ```

6.  JSONP:

    ```
    https://sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/api1/salt_multidict.php?key=agni&callback=myFunc
    ```

### 1.5. Allowable values

1.  key — any headword in the supported transliteration schemes.
2.  input — `slp1`, `deva`, `hk`, `roman`, `itrans`, `velthuis`.
3.  output — `slp1`, `deva`, `hk`, `roman`.
4.  size — positive integer (0 = unlimited).
5.  field — comma-separated list from: top-level names (`id`, `headword_slp1`, `sense`, `re_headwords_slp1`, `created`, `xml`), `csl` (full csl object), and bare csl sub-field names (`lnum`, `page`, `column`, `scanUrl`, `html`, `text`, `xmlCsl`, `references`, `headwordDeva`, `headwordIast`, `accentedKey`). No dot-notation needed.

### 1.6. Defaults

| parameter | default |
|---|---|
| key | *(required — no default)* |
| input | `slp1` |
| output | `deva` |
| size | `0` (unlimited) |
| field | *(all fields)* |

### 1.7. Rewrite rules

```apache
RewriteRule ^multidict/(.*)$  /scans/awork/apidev/api1/salt_multidict.php?key=$1  [QSA,L]
```

### 1.8. Expected output

Status 200 — one or more dictionaries contain the headword. Dictionaries are
ordered by the canonical `$APP_DICTMETA` order (the same ordering as
`app/entry.php`). A `dictmeta` map provides human-readable names and years so
clients do not need their own mapping table.

```json
{
  "status": 200,
  "key": "agni",
  "input": "slp1",
  "output": "deva",
  "dictmeta": {
    "mw": {
      "name": "Monier-Williams Sanskrit-English Dictionary",
      "year": "2020"
    },
    "ap90": {
      "name": "Apte Practical Sanskrit-English Dictionary",
      "year": "2020"
    }
  },
  "dicts": {
    "mw": [
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
          "html": "<span class='sdata_siddhanta'><SA>अग्नि</SA></span>   m. (√अग्, उण.) fire, …",
          "text": "अग्नि   m. (√अग्, उण.) fire, …",
          "xmlCsl": "<H1><h><key1>agni</key1>…</H1>",
          "references": ["उण."],
          "headwordDeva": "अग्नि",
          "headwordIast": "agni",
          "accentedKey": "agni/"
        }
      }
    ],
    "ap90": [ … ],
    "ben": [ … ],
    …
  }
}
```

Status 404 — the headword was not found in any dictionary:

```json
{
  "status": 404,
  "key": "xyznonexistent",
  "input": "slp1",
  "dicts": {}
}
```

Status 400 — missing or empty `key` parameter:

```json
{
  "error": "Missing parameter: 'key'"
}
```

With `field=id,html,lnum` the per-entry response is limited to `id` and the
requested `csl` sub-fields:

```json
{
  "status": 200,
  "key": "agni",
  "input": "slp1",
  "output": "deva",
  "dictmeta": { … },
  "dicts": {
    "mw": [
      { "id": "lemma-agni-L890", "csl": { "html": "…", "lnum": "890" } }
    ]
  }
}
```

### 1.9. Dictionary scope

The endpoint queries `keydoc_glob1`, the global headword index. Only
dictionaries that actually contain the headword appear in the response — empty
dicts are omitted. As of 2026 the index covers 40+ production dictionaries;
newer or experimental dictionaries may not be indexed yet.

## 2. Implementation

Two files in `api1/`:

| File | Role |
|---|---|
| `api1/salt_multidict.php` | HTTP entry point (thin — validates callback, echoes JSON). |
| `api1/salt_multidictClass.php` | Class: reads `key`/`input`, transcodes to SLP1, resolves via `Dalglob`, iterates `salt_entries_for_key()`. |

No new runtime dependencies. Reuses `salt_common.php` (the shared Salt search
builder), `dalglobClass.php` (cross-dict headword index), and the standard
transcoder.

### 2.1. Pipeline

```
SLP1 headword
  → Dalglob(keydoc_glob1)          → [which dicts have this word]
  → salt_entries_for_key() per dict → [Salt-format entries]
  → JSON envelope
```

### 2.2. Selftest (CLI)

```sh
php -r '
$_REQUEST["key"] = "agni";
require "api1/salt_multidictClass.php";
$t = new SaltMultidictClass();
echo $t->json;
'
```


