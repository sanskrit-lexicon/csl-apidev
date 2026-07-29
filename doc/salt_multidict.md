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
| output | deva | For reference; the entry shape always includes `headwordDeva` / `headwordIast`. |

`input` and `output` follow the same convention as [salt_entries](salt_entries.md) §1.2.

### 1.3. Suggested Clean URL

```
/multidict/{key}?input={input}
```

### 1.4. Examples

1.  SLP1 input:

    ```
    https://sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/api1/salt_multidict.php?key=agni
    ```

2.  Devanagari input:

    ```
    https://sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/api1/salt_multidict.php?key=अग्नि&input=deva
    ```

3.  IAST input:

    ```
    https://sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/api1/salt_multidict.php?key=agni&input=roman
    ```

4.  JSONP:

    ```
    https://sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/api1/salt_multidict.php?key=agni&callback=myFunc
    ```

### 1.5. Allowable values

1.  key — any headword in the supported transliteration schemes.
2.  input — `slp1`, `deva`, `hk`, `roman`, `itrans`, `velthuis`.
3.  output — `slp1`, `deva`, `hk`, `roman`.

### 1.6. Defaults

| parameter | default |
|---|---|
| key | *(required — no default)* |
| input | `slp1` |
| output | `deva` |

### 1.7. Rewrite rules

```apache
RewriteRule ^multidict/(.*)$  /scans/awork/apidev/api1/salt_multidict.php?key=$1  [QSA,L]
```

### 1.8. Expected output

Status 200 — one or more dictionaries contain the headword:

```json
{
  "status": 200,
  "key": "agni",
  "input": "slp1",
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
          "html": "<span class='sdata_siddhanta'><SA>agni</SA></span>   m. (√ag, Uṇ.) fire, …",
          "text": "agni   m. (√ag, Uṇ.) fire, …",
          "xmlCsl": "<H1><h><key1>agni</key1>…</H1>",
          "references": ["Uṇ."],
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

## 3. Questions

1.  Pagination — the current response returns every record for the headword
    across all matching dictionaries. Should a `size` parameter cap the total?
2.  Order — dicts appear in `keydoc_glob1` order (alphabetical). Should the
    response follow the canonical `dictmeta` order (the same ordering as
    `app/entry.php`)?
3.  Field filtering — should a `field` parameter (like `salt_entries`) allow
    selecting only `headword_slp1` + `csl.text` for lightweight responses?
