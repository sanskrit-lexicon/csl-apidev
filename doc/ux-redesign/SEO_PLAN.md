# SEO plan — crawlable entry permalinks + structured data (H227)

_Created: 06-07-2026 · Last updated: 06-07-2026_

**What this is.** The applied SEO recipe for the `app/` redesign: server-rendered,
unique-URL entry permalinks with full JSON-LD, an XML sitemap over the union
headword list, and the thin-content gate — following the org
[SEO_STRUCTURED_DATA_PLAYBOOK.md](https://github.com/gasyoun/Uprava/blob/main/SEO_STRUCTURED_DATA_PLAYBOOK.md)
(§9 sequence, adapted from its commercial context to a scholarly reference site)
and the competitive lessons of
[kosha/COMPARISON.md](https://github.com/gasyoun/kosha/blob/main/COMPARISON.md)
("What each site teaches", steal items 1, 2, 7, 8, 11). Handoff:
[H227](https://github.com/gasyoun/Uprava/blob/main/handoffs/archive/H227-Opus_csl-apidev_ux_seo_crawlable_structured_data_06.07.26.md).

**Why.** COMPARISON.md names the failure mode the current
[app/index.php](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/index.php)
falls into: *"SPAs with no server-rendered permalinks (Logeion, Gandhāri,
learnsanskrit.cc)"* are invisible to crawlers. The thing to steal is
*"server-rendered per-headword permalinks (crawlable, archivable, SEO)"*
(Wisdom Library, michaelmeyer.fr). MG ruling 06-07-2026: crawlability is a
**hard requirement**, not an annotation.

---

## 1. What shipped (P0)

| File | Role |
|---|---|
| [app/entry.php](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/entry.php) | Server-rendered stacked entry permalink: every dictionary's full entry text in the initial PHP response (a bot with JS off sees the complete entry) |
| [app/dictmeta.php](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/dictmeta.php) | Per-dictionary title/version table, machine-derived from [lookup/dictmeta.js](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/lookup/dictmeta.js) |
| [app/entry.css](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/entry.css) / [app/entry.js](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/entry.js) | Styling + progressive enhancement only (copy-citation buttons); the page is complete with JS off |
| [app/.htaccess](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/.htaccess) | Clean-URL rewrites `entry/<key>` and `entry/<dict>/<key>` |
| [scripts/build_sitemap.py](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/scripts/build_sitemap.py) | Sitemap index + gzipped 50k shards from the union headword DB |
| [app/robots.txt](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/robots.txt) | Version-controlled source for the host-root robots.txt (sitemap pointer + API-endpoint disallows) |

Server-side reuse, no new data paths: dictionary resolution is the existing
`Dalglob` class ([dalglobClass.php](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/dalglobClass.php),
`keydoc_glob1`), entry HTML is the existing `GetwordClass`
([getwordClass.php](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/getwordClass.php))
called in-process with `dispopt=3` (bare fragment) — the same objects behind
`dalglob.php` / `getword.php`, driven the same way
[getword_batch.php](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/getword_batch.php)
drives them. `?fixtures=1` replays
[app/fixtures/fixtures.json](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/fixtures/fixtures.json)
server-side for offline development (always `noindex`).

## 2. URL scheme

- **Canonical (one per headword):** `…/app/entry.php?key=<slp1>` — the stacked
  all-dictionaries page. Emitted in `<link rel="canonical">`, OpenGraph and
  JSON-LD on every variant of the page.
- **Single-dictionary view:** `…/app/entry.php?key=<slp1>&dict=<code>` —
  `noindex,follow`, canonical → the stacked page (homonym-fragment /
  per-source duplicates collapse to one canonical, playbook §4.3).
- **Clean form** (once [app/.htaccess](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/.htaccess)
  is confirmed active on the Cologne host — needs `AllowOverride FileInfo`):
  `…/app/entry/agni` and `…/app/entry/mw/agni`. Example:
  [entry.php?key=agni](https://www.sanskrit-lexicon.uni-koeln.de/csl-apidev/app/entry.php?key=agni).
  The canonical stays the query form until the rewrite is verified live;
  flipping canonicals to the clean form is a one-line change in `entry.php`
  (and a `--base`-only rerun of the sitemap builder). This coexists with the
  content-negotiated `/{DICT}/{ref}` permalink planned in
  [doc/cleanurl.md](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/doc/cleanurl.md)
  (COLOGNE#249): `entry/` is a distinct path segment, and the dict-code
  whitelist idea is reused here in the rewrite pattern.
- **Input-scheme variants** (`&input=iast|hk|deva|velthuis|itrans`) are accepted
  and transcoded, then canonicalized to the SLP1 form — no duplicate URL space.
- **Kosha compatibility:** the stable per-entry identifier is the SLP1 key;
  per-block anchors are `#<dict>` / `#<dict>-<n>` for homonyms, and the
  displayed block id is `{dict}.{dockey}` — the same `{dict}.{key}` spine kosha's
  sense ids extend as `{dict}.{L}.{sense}@{version}`
  ([kosha app/cite.py](https://github.com/gasyoun/kosha/blob/main/app/cite.py)).
  Cologne cites at entry level, kosha at sense level; the dict codes and key
  space are shared, so the conventions stay compatible, not competing.

## 3. JSON-LD shapes (playbook P0 spine, scholarly adaptation)

One `@graph` per entry page ([validation](#6-validation-results) below):

- `Organization` — `@id: {site}/#org` (Cologne Digital Sanskrit Dictionaries,
  University of Cologne). The playbook's entity spine: every other node
  references it.
- `WebSite` — `@id: {site}/#website`, `publisher → #org`, `inLanguage [sa, en]`.
- `WebPage` — `@id` = canonical URL, `isPartOf → #website`,
  `mainEntity → …#term`, `breadcrumb → …#breadcrumb`.
- `BreadcrumbList` — Home → Dictionary search → headword (last crumb without
  `item`, per playbook §2.3).
- **`DefinedTerm`** — exactly one per page, `@id: {canonical}#term`;
  `name` = IAST headword, `alternateName` = [Devanagari, SLP1],
  `identifier` = SLP1 key, `inDefinedTermSet` → the sets below.
- **`DefinedTermSet`** — one per dictionary attesting the headword,
  `@id: {base}/entry.php#dictset-{code}` (stable across all entry pages, so
  the engines see 45 dictionary entities, not millions), `name` = full
  edition title from dictmeta, `description` carries the CDSL version.

The commercial parts of the playbook (`Offer`, `Course`) are deliberately
omitted — nothing is for sale; `sameAs` Wikidata triplets are deferred (playbook
§4.4: confidence-gated matching is its own pass, never guessed).

Emission mechanics: built as one PHP array, emitted with `json_encode`
(default flags escape `/` → `\/`, so `</script>` cannot terminate the block —
playbook §5).

## 4. Sitemap strategy

- **Source:** `hwnorm1c.sqlite` — the union normalized-headword DB
  (406,652 keys; the same universe getsuggest/simple-search query), fetched via
  [download_hwnorm1c_sqlite.sh](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/download_hwnorm1c_sqlite.sh)
  from [csl-sqlite releases](https://github.com/sanskrit-lexicon/csl-sqlite/releases).
- **Output:** `sitemap.xml` index + `sitemap-NNN.xml.gz` shards, ≤50,000 URLs
  each (the sitemaps.org cap), one URL per canonical stacked page.
- **Generated, not committed:** run
  `python scripts/build_sitemap.py --db … --base … --out app/sitemaps`
  on the host (or locally + upload). Verified build 06-07-2026: **9 shards,
  406,595 URLs**, all shards well-formed XML (ElementTree round-trip).
- [app/robots.txt](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/robots.txt)
  points to the index; it must be merged into the **host-root** robots.txt at
  deploy time (a robots.txt below root is ignored — deploy note in the file).

## 5. Thin-content / over-indexation gate

Applied per playbook §4, adapted: dictionary entries are *not* Systema-style
one-line stubs — every union headword is a full scholarly entry in ≥1
dictionary — so the gate here is structural, not editorial:

1. **No crawlable page for empty keys:** a headword in no dictionary returns
   HTTP 404 + `noindex,follow` (with a fuzzy-search escape hatch for humans).
2. **Junk keys never sitemapped:** `build_sitemap.py` drops keys that don't
   parse as SLP1 headwords — OCR junk (`???`), embedded spaces
   (57 of 406,652 dropped in the verified build).
3. **One canonical per headword:** single-dict views, non-SLP1 input spellings
   and `?fixtures=1` pages are all `noindex,follow` with canonical → the
   stacked page; homonyms are fragments (`#mw-2`) of the one canonical, never
   separate URLs.
4. **No Wave-0 master switch** (deviation from playbook §5, reasoned): MG ruled
   crawlability a hard requirement for this institutional reference site, and
   the host is not yet serving the pages at all — the gate that matters is
   Jim's server-checkout pull. If a staged rollout is still wanted later, ship
   the sitemap index with a subset of shards; no code change needed.

## 6. Validation results (offline, 06-07-2026)

Live checks are blocked — the Cologne host is on
[SERVER_OUTAGES.md](https://github.com/gasyoun/Uprava/blob/main/SERVER_OUTAGES.md) —
so validation ran against PHP 8.2.12 (`php -S`, repo root) in `?fixtures=1`
mode, per the slice-1 discipline ("not live ≠ not done"):

| Check | Result |
|---|---|
| Raw `curl` (JS-off semantics) of `entry.php?key=agni&fixtures=1` | ✅ full entry text of every fixture-covered dictionary present in the initial response; zero client fetches needed |
| `<head>` | ✅ unique per headword: `<title>agni — Monier-Williams & 7 more · Cologne Sanskrit Lexicon</title>`, first-gloss meta description, canonical, OG + Twitter tags |
| JSON-LD | ✅ parses as JSON; 13-node `@graph`; exactly **one `DefinedTerm`**; zero dangling `@id` references (spine walked programmatically); Schema.org-shape fields per playbook §6 offline procedure |
| Homonyms | ✅ `māna` renders MW¹/MW² blocks with `#mw-1`/`#mw-2` anchors, one canonical |
| Miss (`zzzznothere`) | ✅ HTTP 404 + `noindex,follow` |
| Single-dict view | ✅ `noindex,follow`, canonical → stacked page |
| XSS probes | ✅ `key=("><script>…)`, `key[]=x` array injection: no raw reflection, no PHP errors (Parm-style invalid-char strip + `htmlspecialchars ENT_QUOTES` on every echo; Host-header guard on the canonical base) |
| Sitemap | ✅ 9 shards / 406,595 URLs from the real hwnorm1c.sqlite, all well-formed, index resolves shard names |

**Remaining live checks (post-outage, for the deploy pass):** re-run the same
curl checks against real entry HTML; Google Rich Results Test + Yandex
[microtest](https://webmaster.yandex.ru/tools/microtest/) on 2–3 entry URLs
(playbook §6); confirm `.htaccess` rewrite; spot-check `servepdf` scan links
resolve to the right page (needs the live PDF service); submit the sitemap in
Yandex.Webmaster + Google Search Console.

## 7. L1–L4 status (COMPARISON steal lessons)

| # | Lesson | Status |
|---|---|---|
| L1 | **Crawlable stacked multi-source page** (Wisdom Library / meyer) | **BUILT** — the P0 page itself; degrades to nothing-lost with JS off (entry.js is copy-buttons only) |
| L2 | **Per-sense scan links** (meyer) | **PARTIAL: entry-level built, per-sense spec'd.** Every block carries `☞ scan` → `servepdf.php?dict=&key=` (page-accurate per headword). Per-**sense** page+column anchors need the canonical `ls_resolver` ([SHARED_CODE.md §11](https://github.com/gasyoun/github-spine/blob/main/SHARED_CODE.md)) run over each dictionary's `<ls>` inventory — that is the `/cologne-link-target` DTB workflow, one dictionary at a time, and its per-sense output plugs into the block template as `☞ p.5, col.1` anchors after each sense marker. Not half-built here per the handoff's "spec precisely and ship P0 fully" rule. |
| L3 | **Input auto-detect + query operators** (sanskritdictionary.com) | **PARTIAL: auto-detect built, operators spec'd.** entry.php auto-detects Devanagari/IAST/SLP1 (no scheme picker on the permalink page; index.php already auto-detects). `sandhi:` / `root:` operators have **no existing endpoint** — spec: `sandhi:<surface>` should call a segmenter (Vidyut, per [simple-search/roadmap_dh.md](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/roadmap_dh.md) Stream A) then link each segment to its entry page; `root:<dhatu>` needs a root→derivative index (the MW/Whitney/DCS root crosswalks in [Uprava/PROJECT_INTERLINKS.md](https://github.com/gasyoun/Uprava/blob/main/PROJECT_INTERLINKS.md)). Both are index-side work, not page-side; wire when an endpoint exists. |
| L4 | **Corpus-frequency + versioned Cite** (Logeion/DCS/Gandhāri) | **BUILT (band + cite), sidebar spec'd.** Each entry page shows a DCS corpus band (`corpus: frequent`, token count in the tooltip) read from the existing [simple-search/wf1/wf.txt](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/wf1/wf.txt) DCS-2026 asset — consumed, not rebuilt. The **Cite block** is versioned (per-dictionary CDSL versions, `MW@2020`) and host-independent in id (`{dict}.{key}`), kosha's citation shape at entry granularity, with BibTeX + copy buttons. A full diachronic attestation sidebar (DCS per-epoch counts) awaits the DCS epoch export — spec: same placement, data from [VisualDCS](https://github.com/gasyoun/VisualDCS) exports keyed by the existing [dcs_cdsl_xref.tsv](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/dcs_xref/dcs_cdsl_xref.tsv) crosswalk. |

## 8. Deploy checklist (Jim / post-outage)

1. Pull the server checkout (`app/entry.php` + friends are inert until then —
   same as [PR #63](https://github.com/sanskrit-lexicon/csl-apidev/pull/63)).
2. Fetch `hwnorm1c.sqlite`, run `scripts/build_sitemap.py` with the real
   `--base`, confirm `app/sitemaps/` is served.
3. Merge [app/robots.txt](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/robots.txt)
   into the host-root robots.txt.
4. Confirm the `.htaccess` rewrite works (`AllowOverride FileInfo`); if yes,
   flip the canonical to the clean form and rebuild the sitemap with it.
5. Run the live validation row of §6; register the sitemap in Yandex.Webmaster
   and Google Search Console.

_Dr. Mārcis Gasūns_
