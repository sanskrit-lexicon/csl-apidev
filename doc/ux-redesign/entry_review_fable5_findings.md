# Adversarial code review — H227 SSR entry-permalink pass

_Created: 08-07-2026 · Last updated: 08-07-2026_

**What this is.** A hostile, re-derive-everything review of the H227 server-rendered
entry-permalink pass ([PR #78](https://github.com/sanskrit-lexicon/csl-apidev/pull/78),
merged to `main` at `17f7ea6`), executed per
[H233](https://github.com/gasyoun/Uprava/blob/main/handoffs/archive/H233-Fable_csl-apidev_h227_entry_seo_adversarial_code_review_06.07.26.md).
Discipline: trust no claim; re-execute everything executable; re-derive every number.
Model: **Fable 5 (`claude-fable-5`)**. Same method as the simple-search adversarial review
([simple-search/review_fable5_findings.md](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/review_fable5_findings.md)).

**Environment.** PHP 8.2.12 (`php -S 127.0.0.1:8231`, repo root), `?fixtures=1` mode for the
live-gated `Dalglob`/`GetwordClass` paths (Cologne host down —
[SERVER_OUTAGES.md](https://github.com/gasyoun/Uprava/blob/main/SERVER_OUTAGES.md)); the real
`hwnorm1c.sqlite` (csl-sqlite `data-v*` release, 26 MB, fetched 08-07-2026) for the sitemap
re-derivation.

---

## Verdict per file

| File | Verdict |
|---|---|
| [app/entry.php](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/entry.php) | **Sound; one MINOR fixed** (thin-content gate hole), 3 MINOR / 1 NIT listed |
| [app/dictmeta.php](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/dictmeta.php) | **Sound** — 45 rows, byte-faithful to lookup/dictmeta.js (re-derived, zero drift) |
| [app/entry.js](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/entry.js) | **Sound** — progressive-enhancement only; page complete with JS off (verified by raw curl) |
| [app/entry.css](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/entry.css) | **Sound** — standalone; 600px breakpoint; `overflow-x:auto` on entry bodies |
| [app/.htaccess](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/.htaccess) | **Sound** — dict `{2,6}` + key `[A-Za-z]+[0-9]*` cover every code/key; whitelist deferred to entry.php |
| [app/robots.txt](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/robots.txt) | **Sound** — `getword_batch.php` correctly needs its own line; one observation (L3) |
| [scripts/build_sitemap.py](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/scripts/build_sitemap.py) | **Sound** — 406,595 URLs / 57 dropped / 9 shards re-derived exactly from the real DB |
| [doc/ux-redesign/SEO_PLAN.md](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/doc/ux-redesign/SEO_PLAN.md) | **Every factual claim survived re-derivation** (see §Re-derivation) |

**Bottom line:** the pass is solid. The known hotspots the handoff flagged are all either
sound-by-construction (SQLi gate, `$_REQUEST` mutation, dictmeta regen) or already handled.
No CRITICAL or MAJOR defect. One MINOR thin-content gate hole was fixed in this pass; the
rest are low-severity SEO-polish items listed below.

---

## Re-derivation (every number reproduced)

| SEO_PLAN claim | Re-derived | Status |
|---|---|---|
| hwnorm1c total keys | 406,652 | ✅ exact |
| junk keys dropped (non-SLP1) | 57 | ✅ exact (` apPala`, `???`, `AjamI\|a`, `SEla,SElI`, …) |
| sitemap URLs emitted | 406,595 | ✅ exact (406,652 − 57) |
| shards @ 50,000 | 9 (8×50k + 6,595) | ✅ exact; all shards + index parse (ElementTree round-trip) |
| JSON-LD `@graph` nodes (agni) | 13 | ✅ exact |
| exactly one `DefinedTerm` | 1 | ✅; 8 `DefinedTermSet`; **zero dangling `@id`** (spine walked programmatically) |
| homonyms `māna` → MW¹/MW² | `#mw-1` / `#mw-2`, `<sup>1</sup>`/`<sup>2</sup>`, one canonical | ✅ exact |
| miss → 404 + noindex | HTTP 404, `noindex,follow` | ✅ |
| single-dict view noindex, canonical→stacked | `noindex,follow`, canonical `?key=agni` | ✅ |
| XSS probes clean | `"><script>` and `key[]=x`: no raw reflection, no PHP error | ✅ |
| DCS freq band | `agni 295` in wf1/wf.txt → `corpus: frequent`, tooltip "Token count 295" | ✅ |

dictmeta.php vs lookup/dictmeta.js: 45 rows each, identical `code → (title, year, olderYear)`
for all 45 — no row dropped or mangled (umlauts `Böhtlingk`/`Goldstücker`, diacritics
`Kṛdantarūpamālā`/`Abhidhānacintāmaṇi` all intact).

---

## Hotspots — dispositions

1. **SQLi surface (`Dalglob::get1` interpolates `where key='$keynorm'`).** **Gate is airtight
   for entry.php.** Traced every path into `$slp1`: entry.php strips `$ # < > = ( ) " ' \`
   *before* transcoding (stricter than `Parm::init_inputs_key`, which keeps `'` and `\`), and
   the post-transcode gate `^[a-zA-Z]+[0-9]*$` empties anything with a surviving non-alnum.
   The transcoder default branch does pass an unmapped char through verbatim, but the gate
   catches it. Verified: `ag";DROP` → emptied; `agni'` → `agni`; `x)or(1` → `xor1` (a harmless
   alnum lookup, no injection). The latent unbound SQL in `get1()` is **unreachable from this
   endpoint**. (Worth a parameterized-query fix upstream regardless — but out of H227 scope.)
2. **`$_REQUEST` mutation loop.** No state leak: each iteration builds a fresh `GetwordClass`
   → `Parm` → `DictInfo`; the only shared state (`DictInfo::$dictyear`) is static read-only.
   `$_GET['key']` set once (line 115) is cosmetic — every consumer reads `$_REQUEST`. Perf:
   ~N-dict × (PDO open + transcoder pass) per view is real but bounded; a page-cache header is
   a **recommendation, not a defect** (see §Recommendations).
3. **`entry_frequency()` linear scan** of the 50,574-line wf1/wf.txt — bounded, one pass,
   breaks on hit. Measured fine; no index needed. (Minor: it keys on the *un-normalized*
   `$slp1` — see MINOR-2.)
4. **`chdir(__DIR__.'/..')`** — every relative path resolves from repo root (`simple-search/…`,
   `utilities/…`, class includes); fixtures read via `__DIR__`. Sound.
5. **Homonym/`dockeys` semantics.** entry.php's positional `<sup>` numbering matches
   `app.js` byte-for-byte (both use `multi ? sup(i+1)` over the `parse_glob1` dockey order,
   both order dicts by the alphabetical dictmeta index). Consistent; not a divergence. The
   theoretical "dockeys are variant spellings not homonyms" case would mislabel *both* views
   identically — a pre-existing data-semantics question, not an H227 regression.
6. **JSON-LD vs Schema.org.** `DefinedTerm`/`DefinedTermSet` shapes valid; last breadcrumb
   crumb correctly omits `item` (current Google guidance); `og:type=article` is acceptable for
   a reference entry (NIT below). Zero dangling refs.
7. **Headline-name `explode(' ',$title)[0]`.** Real cosmetic issue for MW-absent headwords
   (MINOR-3).
8. **`.htaccess` charset.** dict `{2,6}` covers `ae`…`pwkvn`; key `[A-Za-z]+[0-9]*` matches
   sitemap keys. Consistent.
9. **robots.txt.** `Disallow: /*getword.php` does **not** match `getword_batch.php` (no
   `getword.php` substring), so the explicit `getword_batch.php` line is correctly present.
   Nothing blocks `entry.php`, CSS/JS, or the sitemap. Observation on index.php below (L3).
10. **dictmeta regen drift.** None — re-derived byte-faithful (see §Re-derivation).

---

## Findings

### MINOR-1 — thin-content gate indexed body-less pages **[FIXED this pass]**

`$is_canonical_view` gated on `$found` (`count($blocks) > 0 || count($alldicts) > 0`), so a
page where `Dalglob` matched dictionaries but **every** `GetwordClass` render failed (a live
partial outage) was `index,follow` while its `<main>` carried only "temporary server error" —
exactly the thin content the gate exists to keep out of the index.
**Fix:** gate on `count($blocks) > 0` (rendered entry text), not `$found`. The 404/title/
JSON-LD paths still key on `$found`, so a body-less page still returns 200 to humans but is
now `noindex,follow`. Verified: agni still indexes, miss still 404+noindex, fixtures still
noindex. [app/entry.php](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/entry.php).

### MINOR-2 — canonical/frequency use the un-normalized input key **[listed]**

`$canonical` and `entry_frequency()` use the raw transcoded `$slp1`, not the hwnorm1c
normalized key that `Dalglob::get1()` computes internally. So a normalization-equivalent
spelling — e.g. `entry.php?key=agniH` (passes the gate, `Dalglob` normalizes `agniH`→`agni`
and returns agni's dicts) — serves agni's content but emits `<link rel=canonical
?key=agniH>` (self) and looks up frequency for the absent key `agniH` (band suppressed).
Two URLs, same content, each self-canonical — defeating "one canonical per headword" for
oblique inputs. **Low real-world exposure** (the sitemap emits only normalized keys, which
self-canonicalize correctly; variant URLs are only reachable if hand-crafted or from a stale
link). **Not fixed** because the clean fix needs `Dalglob::get1()` to *return* its `$keynorm`
(or entry.php to duplicate `normalize_key()`), neither of which is mechanical. Recommend
exposing the normkey from Dalglob and canonicalizing to it.

### MINOR-3 — headline author-name is silly for MW-absent headwords **[listed]**

`$headname = explode(' ', $headtitle)[0]` assumes the dictmeta title starts with an author
surname. True for MW/Apte/Böhtlingk, but MW-preference only masks it while MW attests the
word. A headword only in index/encyclopedia works yields titles like `agni — An & 3 more`
(pd = "**An** Encyclopedic Dictionary…"), `… — The & …` (pui/vei), `… — Index & …` (inm),
`… — Indian & …` (ieg), `… — Personal & …` (pgn). **Not fixed:** any fix is heuristic
(strip leading articles helps `An`/`The` but not `Index`/`Indian`/`Personal`, which are
genuinely author-less works) and risks new oddities in a `<title>`. A human should decide
whether title polish for the ~6 author-less reference works is worth a heuristic; recommend
leaving as-is otherwise.

### NIT-1 — dead description-strip regex **[listed]**

Line 248 strips `<h2 class="dictitle">…</h2>` from `$blocks[0]['html']` before building the
meta description, but `GetwordClass` with `dispopt=3` emits a bare `<div><table>` fragment
with no such heading — the regex is a harmless no-op (verified: descriptions are clean gloss
text). Drop the `preg_replace` or fix the comment; cosmetic only.

### Observation (L3) — API disallows hide index.php's content from crawlers

robots.txt disallowing `getword.php`/`dalglob.php` means Googlebot cannot fetch the XHR that
the SPA `app/index.php` renders client-side, so index.php is a content-less shell to crawlers.
This is **by design** — entry.php is the crawlable surface and index.php is not disallowed
(so it isn't dropped, just thin). No action needed; noted so the deploy pass doesn't mistake
index.php's thinness for a regression.

---

## Recommendations (non-blocking, for the deploy/next pass)

- **Page-cache header on entry.php.** Googlebot will hit ~406k of these; each view opens one
  PDO per attesting dictionary. Add `Cache-Control: public, max-age=…` (or a static-cache
  layer à la kosha's `build_static_cache.py`) at deploy. Cheap, high-leverage.
- **Parameterize `Dalglob::get1()`.** Unreachable from entry.php today, but the unbound
  `where key='$keynorm'` is a latent hazard for any future caller — bind it.
- **Expose the hwnorm1c normkey from Dalglob** to close MINOR-2 cleanly.

---

## Live-gated (do NOT half-build — fold into the csl-apidev outage @WAITING rows)

Everything in SEO_PLAN §6 "Remaining live checks" stands: real-entry-HTML curl, Google Rich
Results + Yandex microtest on 2–3 live URLs, `.htaccess` rewrite confirmation,
`servepdf` scan-link resolution, sitemap submission. Blocked on the Cologne outage + Jim's
server-checkout pull.

_Dr. Mārcis Gasūns_
