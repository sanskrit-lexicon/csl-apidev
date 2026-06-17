# cleanurl

This is a roadmap for a *clean-URL* (permalink) layer that lets a person link
directly to a dictionary entry, as requested in
[COLOGNE#249](https://github.com/sanskrit-lexicon/COLOGNE/issues/249).

The entry-display and list machinery already exist ([listview](listview.md),
[listhier](listhier.md), [getword](getword.md), and the `dal->get2` id lookup).
What is new here is only a thin **routing** layer: a server-root rewrite that
sends `/{DICT}/...` to a new `cleanurl.php`, which parses the path, resolves it to
the same restful parameters [listview](listview.md) already understands, and serves
the existing listview display.

## 0. Status — unified with the Salt API permalink

Since this roadmap was first written, the same user-facing permalink —
`/{DICT}/{ref}`, `ref` = headword (any input transliteration) or `lnum` — has been
adopted by the **Salt API** as its permalink form
([salt_entries](salt_entries.md) §1.3, §1.7), which "subsumes" this document. The two
are **the same URL**, resolved by **one** rewrite, and must not ship as competing
schemes. Division of labor:

- **Salt API** owns the *data* face: `Accept: application/json` (or the explicit
  `/dicts/{id}/restful/entries` query form, or `?format=json`) → `salt_entries.php` →
  the C-SALT-compatible JSON envelope.
- **cleanurl** (this doc) owns the *human* face: a default browser request
  (`Accept: text/html`) → the full **listview** display that #249 asked for. JSON in a
  browser is not the "land on the entry" experience the issue wanted.

So the single `/{DICT}/{ref}` rewrite should **content-negotiate** on `Accept` (with an
explicit `.json` / `?format=` override), routing to `salt_entries.php` for API clients
and to the listview render (`cleanurl.php`, below) for people.

Two things this doc specifies that the current Salt draft (§1.7) **dropped** and must be
carried back into the unified rewrite:

1. **Collision-safe routing (whitelist).** Salt's rule `^([A-Za-z0-9]+)/([^/]+)$`
   matches *any* two-segment path — it would capture `/images/x`, `/php/x`, `/css/x`,
   `/js/x`, … not just dictionaries; reserving only `restful`/`graphql` is not enough.
   Use the **dict-code whitelist** of §4 below so non-dictionary root paths pass through
   untouched.
2. **Homonyms and decimal ids.** Salt's two-segment rule cannot express the homonym
   form `/{DICT}/{KEY}/{HOM}` (§2) and is silent on decimal `lnum` (`/MW/144239.1`).
   Both must survive (the §3 disambiguation rules already cover them).

The rest of this document — scheme grammar (§2), id-vs-headword disambiguation (§3),
the whitelist rewrite (§4), the `cleanurl.php` router (§5), id→entry resolution (§6),
and the lnum-stability caveat (§9) — remains the spec for the **HTML** side and for
**safe routing**, and applies to the unified permalink regardless of which backend
renders it.

## 1. The two requests, restated

#249 asks for two link forms. We satisfy both, but with **no query string** — the
term form becomes a clean path, so both forms are SEO-friendly permalinks.

| #249 wanted | We serve | Meaning |
|---|---|---|
| `/MW/144239` | `/MW/144239` | the record whose Cologne id (`lnum`) is 144239 |
| `/MW?q=bAQa` | `/MW/bAQa` | headword lookup for `bAQa`, list + entry |

## 2. The unified scheme

One positional grammar, top-level, keyed on the dictionary code. It is the same
"positional path segments" idea already used by `/simple/` (see
[parse_uri](../simple-search/v1.1/parse_uri.php)), lifted to the domain root and
keyed by dictionary:

```
/{DICT}                     dictionary front page (search box, no entry)
/{DICT}/{ID}                ID  = /^\d+(\.\d+)?$/    -> permalink to that record
/{DICT}/{KEY}               KEY = non-numeric        -> headword lookup (1st homonym)
/{DICT}/{KEY}/{HOM}         HOM = /^\d+$/            -> the HOM-th homonym of KEY
```

`{DICT}` is the uppercase dictionary code (`MW`, `PWG`, `AP90`, ...). `{KEY}` is the
headword, by default in **slp1** (`bAQa`), but may be percent-encoded Devanagari or
IAST (see [§9 Questions](#questions)). All forms render the **full listview** (list
pane + entry), centered on the resolved record.

### 2.1 Worked example — बाढ in MW

#249's own example: the headword **बाढ** `bAQa` has two homonyms, at `lnum` 144239
and 144239.1. Every row below lands on the same listview display, centered on the
named record:

```
https://www.sanskrit-lexicon.uni-koeln.de/MW/bAQa          -> bAQa, 1st homonym  (lnum 144239)
https://www.sanskrit-lexicon.uni-koeln.de/MW/bAQa/2        -> bAQa, 2nd homonym  (lnum 144239.1)
https://www.sanskrit-lexicon.uni-koeln.de/MW/144239        -> record lnum 144239 (= bAQa/1)
https://www.sanskrit-lexicon.uni-koeln.de/MW/144239.1      -> record lnum 144239.1 (= bAQa/2)
https://www.sanskrit-lexicon.uni-koeln.de/MW               -> MW front page, empty search
```

Note the pleasing symmetry: `/MW/bAQa/2` and `/MW/144239.1` are two routes to the
one record.

## 3. Disambiguation rules

A single segment after the dict can be an id or a headword. The decision is purely
lexical and unambiguous, because slp1 headwords are never all-numeric:

1. If the first post-dict segment matches `/^\d+(\.\d+)?$/` -> **id route** (`lnum`).
2. Otherwise -> **headword route** (`key`). An optional following all-numeric segment
   `/^\d+$/` is the **homonym index** (`/MW/bAQa/2`).
3. Anything after that is ignored (room for a future `/INPUT/OUTPUT/ACCENT` tail, §8).

Display options (input/output/accent) are **not** in the permalink — they come from
the user's cookie, exactly as the `/simple/` page already does
([list-0.2s_rw.php](../simple-search/v1.1/list-0.2s_rw.php) -> `cookieUpdate`). This
keeps permalinks short and stable.

## 4. The rewrite (server-root `.htaccess`)

The dictionary codes are **whitelisted** so the new routes cannot collide with
existing root paths (`/scans/`, `/php/`, `/simple/`, `/images/`, `/css/`, `/js/`).
Any path whose first segment is not a known dict code passes through untouched.

```apache
# --- Clean dictionary permalinks (COLOGNE#249) -------------------------
# /{DICT} or /{DICT}/...  ->  cleanurl.php router.
# Longer codes first (defensive); the (/|$) boundary already prevents
# MW from swallowing MW72, AP from swallowing AP90, PW from PWG, etc.
RewriteEngine On
RewriteRule ^(MW72|AP90|ARMH|WIL|YAT|GST|BEN|LAN|CAE|SHS|BHS|MWE|BOR|BUR|STC|PWG|GRA|CCS|SCH|BOP|SKD|VCP|INM|VEI|PUI|ACC|KRM|IEG|SNP|PGN|MCI|FRI|MD|MW|AP|PD|AE|PW|PE)(/.*)?$ \
            /scans/csl-apidev/cleanurl.php [L,QSA]
# -----------------------------------------------------------------------
```

The full code list is the same set [parse_uri.php](../simple-search/v1.1/parse_uri.php)
already whitelists (`wil yat gst ben mw72 ap90 lan cae md mw shs bhs ap pd mwe bor ae
bur stc pwg gra pw ccs sch bop skd vcp inm vei pui acc krm ieg snp pe pgn mci armh
fri`), uppercased. `cleanurl.php` reads `$_SERVER['REQUEST_URI']` itself, so the rule
need not capture the tail.

## 5. The router — `cleanurl.php`

A new file at the repo root, a sibling of `listview.php`. It is
[list-0.2s_rw.php](../simple-search/v1.1/list-0.2s_rw.php) generalized from one fixed
`/simple/` prefix to a dict-keyed prefix. It does **not** re-implement the display —
it serves the existing listview page seeded with parameters.

Flow:

1. **Parse** `$_SERVER['REQUEST_URI']` with a `parse_cleanurl()` helper modelled on
   `parse_uri()` ([parse_uri.php](../simple-search/v1.1/parse_uri.php)): split into
   `[DICT, SEG1, SEG2, ...]`, lowercase + validate `DICT` against the whitelist.
2. **Classify** `SEG1` by the §3 rules -> `{matched: id|headword|front, lnum?, key?, hom?}`.
3. **Resolve**:
   - id route -> `key` via `dal->get2(lnum, lnum)` (`dal.php:158`); keep `lnum` so
     listview/listhier center exactly on that homonym (lnum takes precedence over key,
     see [listhier](listhier.md)).
   - headword route -> `key` as given; if `hom` present, map (key, hom) -> the right
     `lnum` (the n-th record with that `key`, via `dal->get3`/the homonym walk in
     `listhierClass`).
4. **Seed + serve** the existing listview page: reuse the
   [list-0.2s_rw.php](../simple-search/v1.1/list-0.2s_rw.php) body and its `phpinit()`
   injection of `$phpvals` (`key, dict, input, input_simple, output, accent`), adding
   an `lnum` value when known. The page's existing JS then calls `listview.php` and
   renders the two-pane display.

Reuse map:

| Need | Existing code |
|---|---|
| path -> params parsing | [parse_uri.php](../simple-search/v1.1/parse_uri.php) (`parse_uri`) |
| cologne-vs-xampp detection | `dictinfowhich.php` |
| id -> key,data | `dal->get2` (`dal.php:158`) |
| homonym walk | `listhierClass` (`get2`/`get4a`/`get4b`) |
| two-pane display | `listview.php` (+ `list-0.2s_rw.php` body) |
| cookie-based input/output/accent | `cookieUpdate.js`, `phpinit()` |

## 6. id → entry resolution (detail)

`dal->get2($L,$L)` returns the record(s) with `lnum == $L` as `[key, lnum, data]`.
For `/MW/144239` the router calls `get2(144239,144239)`, reads back `key = bAQa`, and
seeds listview with `dict=mw, key=bAQa, lnum=144239`. Because `listhier` honors `lnum`
over `key`, the list centers on exactly that homonym even when the headword has
several. Decimal ids (`144239.1`) work unchanged — `get4a`/`get4b` already round `lnum`
to 3 places for the prev/next walk (`dal.php:300`).

## 7. Expected output

The page response is HTML (the listview display). For implementation and debugging,
`cleanurl.php` also accepts a diagnostic `?format=json` that returns the routing
decision *without* rendering — the contract to unit-test against:

`GET /MW/144239?format=json`
```json
{
  "url":     "/MW/144239",
  "dict":    "mw",
  "matched": "id",
  "lnum":    "144239",
  "key":     "bAQa",
  "hom":     1,
  "render":  "listview",
  "params":  { "dict":"mw", "key":"bAQa", "lnum":"144239",
               "input":"slp1", "output":"deva", "accent":"no" }
}
```

`GET /MW/bAQa/2?format=json`
```json
{
  "url":     "/MW/bAQa/2",
  "dict":    "mw",
  "matched": "headword",
  "key":     "bAQa",
  "hom":     2,
  "lnum":    "144239.1",
  "render":  "listview",
  "params":  { "dict":"mw", "key":"bAQa", "lnum":"144239.1",
               "input":"slp1", "output":"deva", "accent":"no" }
}
```

`GET /MW/xqzqxq?format=json`  (no such headword)
```json
{ "url":"/MW/xqzqxq", "dict":"mw", "matched":"headword",
  "key":"xqzqxq", "hom":1, "lnum":null, "render":"listview",
  "note":"no record; listview shows 'not found' in entry pane" }
```

## 8. Relation to the existing `/simple/` scheme

The `/simple/` route stays exactly as it is. `cleanurl.php` is a sibling that shares
the **same** parse-and-seed machinery, so the two should be kept DRY:

- Generalize `parse_uri()` so the fixed `simple` prefix becomes a parameter; the
  `/simple/` page and `cleanurl.php` then call the same parser.
- The optional `/INPUT/OUTPUT/ACCENT` tail that `/simple/` supports can later be
  allowed on `/{DICT}/{KEY}` too (e.g. `/MW/bAQa/iast/deva/no`) — deferred; cookie
  defaults cover the common case and keep permalinks clean.

## 9. Caveat — lnum stability

`lnum` is **line-derived**: it is assigned from the position of a record in the source
`.txt`/`.xml` and **can shift when a dictionary is regenerated** (records
inserted/removed upstream). So `/MW/144239` is a *convenience* alias, not a
guaranteed-eternal permalink. The headword form `/MW/bAQa` (and `/MW/bAQa/2`) is the
more durable citation, because it is keyed on lexical content, not file position.
Recommendation: advertise the headword form as the canonical permalink in UI "copy
link" affordances, keep the id form working as an alias. (Making ids truly immutable
is a larger data-pipeline question — out of scope here.)

## 10. Build & test plan

1. Implement `parse_cleanurl()` (generalized `parse_uri`) + `cleanurl.php`; verify the
   `?format=json` contract against the three §7 cases on a local XAMPP install
   (`dictinfowhich == "xampp"`).
2. Add the §4 rewrite block to the server-root `.htaccess`. Confirm a non-dict path
   (`/scans/...`, `/simple/...`) is untouched and only whitelisted codes route.
3. Smoke-test the live forms end-to-end (all should show बाढ in MW):
   `/MW/bAQa`, `/MW/bAQa/2`, `/MW/144239`, `/MW/144239.1`, `/MW` (empty).
4. Spot-check collision-prone short codes (`/AP`, `/PE`, `/MD`, `/AE`, `/PD`) and the
   longer/shorter pairs (`/MW` vs `/MW72`, `/AP` vs `/AP90`, `/PW` vs `/PWG`).
5. Regression: `/simple/...` still works (shared parser unchanged in behavior).

## Questions

1. **Case of dict code.** #249 writes `/MW`. Accept lowercase `/mw/...` too and
   301-redirect to the uppercase canonical, or 404 it? (Recommend: accept, redirect.)
2. **Input encoding of `{KEY}`.** Default slp1. Should we auto-detect percent-encoded
   Devanagari / IAST in the path (reusing `convert_nonascii`) and 301-redirect to the
   canonical slp1 permalink for SEO? Or require slp1 in links?
3. **English-headword dictionaries** (`ae`, `mwe`, `bor`). `{KEY}` is an English word;
   homonym indices and id routing still apply, but confirm the headword route should
   accept spaces / mixed case (`/AE/abandon`).
4. **Front page `/MW`.** Land on the existing search page pre-set to that dict, or a
   lighter dict-specific splash? (Recommend: existing search page, dict preselected.)
5. **`/INPUT/OUTPUT/ACCENT` tail** (§8) — worth supporting now for shareable
   non-default renderings, or defer until asked?
6. **Trailing slash & `.html`/`.json` suffix** policy — normalize with a 301 to one
   canonical form?
7. **Collision governance.** The whitelist is the guard; do we want a regression test
   that fails if a future root directory ever equals a dict code?
8. **Content-negotiation trigger (§0).** The unified `/{DICT}/{ref}` permalink must
   route HTML (listview) vs JSON (`salt_entries.php`). Key the decision on the `Accept`
   header alone, or also honor an explicit override — a `.json` suffix and/or
   `?format=json` — for clients that can't set `Accept` (and what wins when both are
   present)? (Recommend: `Accept` as default, explicit `.json`/`?format=` override
   wins; relates to the suffix policy in Q6.)
