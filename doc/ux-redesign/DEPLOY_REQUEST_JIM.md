# Deploy request — sitemap, robots.txt, canonical flip (H227 §8 steps 2–5)

_Created: 07-11-2026 · Last updated: 07-11-2026_

For: Jim (Cologne host admin) · From: csl-apidev maintainers · Scope: the four
pending steps of [SEO_PLAN.md §8](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/doc/ux-redesign/SEO_PLAN.md)
(step 1 — pulling the server checkout — is already live, see Before-state).
Everything below is copy-pasteable; adjust only the two path variables first.

## Context

The H227 entry-permalink P0 ([SEO_PLAN.md](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/doc/ux-redesign/SEO_PLAN.md))
shipped stacked per-headword pages at
`https://www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/app/entry.php?key=<slp1>`
with per-headword canonical/OG/JSON-LD, clean-URL rewrites
([app/.htaccess](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/.htaccess)),
and a sitemap strategy (§4): one URL per canonical page, generated on the host
from the union headword DB — never committed. Two things remain host-side:
build + serve `app/sitemaps/`, and merge the crawl rules into the **host-root**
robots.txt. A conditional canonical flip follows once the rewrite is confirmed
(it is — see Before-state).

Base URLs matter: the live app base is
`https://www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/app/`. The
`/csl-apidev/app/` base still appearing in
[app/robots.txt](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/robots.txt)
(and one SEO_PLAN §2 example) is stale — it returns 404 today. Use the awork
base everywhere below; the Sitemap line in Host commands is already corrected.

## Before-state

Captured 07-11-2026 (curl, from a maintainer box), same-pass, per the
re-probe checklist contract:

| Probe (curl -s -o /dev/null -w '%{http_code}') | Observed |
|---|---|
| `…/scans/awork/apidev/app/entry.php?key=agni` | **200** — full stacked page (`<title>agni — Monier-Williams & 31 more · …</title>`), canonical = query form |
| `…/scans/awork/apidev/app/sitemaps/sitemap.xml` | **404** (not built yet) |
| `https://www.sanskrit-lexicon.uni-koeln.de/robots.txt` | **200**, **no `Sitemap:` line** |
| `…/scans/awork/apidev/app/entry/agni` (rewrite) | **200** — real entry page, same title/canonical: **the `.htaccess` rewrite is already live** (`AllowOverride FileInfo` works) |
| `…/csl-apidev/app/entry.php?key=agni` | **404** — the `/csl-apidev/` base is not the live base |

Re-probe commands (post-deploy verification reuses them):

```bash
B=https://www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/app
curl -s -o /dev/null -w '%{http_code}\n' "$B/entry.php?key=agni"
curl -s -o /dev/null -w '%{http_code}\n' "$B/sitemaps/sitemap.xml"
curl -s -o /dev/null -w '%{http_code}\n' https://www.sanskrit-lexicon.uni-koeln.de/robots.txt
curl -s https://www.sanskrit-lexicon.uni-koeln.de/robots.txt | grep -i '^sitemap:' || echo "no Sitemap line"
```

If entry.php is not 200 on your re-probe, stop — this request is written
against the state above, never against a phantom state.

## Host commands

Set once (adjust the two paths to this host):

```bash
APIDEV=/path/to/csl-apidev          # server checkout; its app/ is served at …/scans/awork/apidev/app/
DOCROOT=/path/to/host-docroot       # directory whose robots.txt is https://www.sanskrit-lexicon.uni-koeln.de/robots.txt
```

### Step 2 — build the sitemap (§4)

`download_hwnorm1c_sqlite.sh` verifies then installs the DB at
`simple-search/hwnorm1/hwnorm1c.sqlite` inside the checkout (also the
builder's default `--db`; passing `--db hwnorm1c.sqlite` would miss it):

```bash
cd "$APIDEV"
bash download_hwnorm1c_sqlite.sh
python3 scripts/build_sitemap.py \
  --db simple-search/hwnorm1/hwnorm1c.sqlite \
  --base https://www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/app \
  --out app/sitemaps
ls -l app/sitemaps/
```

Expect `sitemap.xml` (index) plus ~9 `sitemap-NNN.xml.gz` shards, ~406k URLs
total (offline reference build 06-07-2026: 9 shards / 406,595 URLs). The
builder is stdlib-only. `app/sitemaps/` is generated output — never committed.

### Step 3 — merge the host-root robots.txt

[app/robots.txt](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/robots.txt)
is the version-controlled source; a robots.txt below host root is ignored.
Two edits to `"$DOCROOT/robots.txt"`, backup first:

```bash
cp "$DOCROOT/robots.txt" "$DOCROOT/robots.txt.bak-$(date +%F)"
cat "$DOCROOT/robots.txt"
```

(a) The `Sitemap:` line is position-independent — appending at the end is
safe. Note the URL is the **awork** base, not the stale `/csl-apidev/` one in
app/robots.txt:

```bash
printf '\nSitemap: https://www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/app/sitemaps/sitemap.xml\n' >> "$DOCROOT/robots.txt"
```

(b) The four `Disallow` lines are **group members**, not appendable: they must
sit inside the existing `User-agent: *` group (appending them after a later
`User-agent:` group would silently rebind them). If `User-agent: *` is the
last group in the file, plain appending is fine; otherwise insert them right
below that line:

```text
Disallow: /*getword.php
Disallow: /*getword_batch.php
Disallow: /*getsuggest.php
Disallow: /*dalglob.php
```

Placement check (the four Disallows must appear after the `*` group header and
before any other `User-agent:` line):

```bash
grep -n -i 'user-agent\|getword\|getsuggest\|dalglob\|sitemap' "$DOCROOT/robots.txt"
```

### Step 4 — rewrite check + conditional canonical flip

The `.htaccess` rewrite is **already verified live** (Before-state row 4), so
the §2 condition for the flip is met. Two parts:

- **4a (Jim, now):** nothing to change — re-confirm with the probe below.
- **4b (maintainers, conditional follow-up PR):** flip canonicals to the clean
  form. This is repo-side, not a host edit: a one-line change in `entry.php`
  (canonical construction, currently `$base . '/entry.php?key=' …`) plus the
  matching URL form in `scripts/build_sitemap.py` (currently hardcodes
  `<base>/entry.php?key=<key>`) — SEO_PLAN §2 calls it a one-liner, but the
  sitemap URL form is a second line or the sitemap would list non-canonical
  URLs. After that PR lands and the checkout is pulled: rerun the Step 2
  builder unchanged (it then emits clean URLs).

```bash
B=https://www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/app
curl -s -o /dev/null -w '%{http_code}\n' "$B/entry/agni"      # 200 = rewrite live
```

## Post-deploy validation

Mechanical (every command + expected value):

```bash
B=https://www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/app
curl -s -o /dev/null -w '%{http_code}\n' "$B/entry.php?key=agni"          # 200
curl -s "$B/entry.php?key=agni" | grep -c 'rel="canonical"'                # 1
curl -s -o /dev/null -w '%{http_code}\n' "$B/entry.php?key=zzzznothere"    # 404
curl -s "$B/entry.php?key=agni&dict=mw" | grep -c 'noindex'                # 1 (single-dict view)
curl -s -o /dev/null -w '%{http_code}\n' "$B/entry/agni"                   # 200
curl -s -o /dev/null -w '%{http_code}\n' "$B/sitemaps/sitemap.xml"         # 200 (was 404)
curl -s "$B/sitemaps/sitemap.xml" | head -3                                # <sitemapindex> listing the shards
curl -s -o /dev/null -w '%{http_code}\n' "$B/sitemaps/sitemap-001.xml.gz"  # 200 (first shard, name as listed in the index)
curl -s https://www.sanskrit-lexicon.uni-koeln.de/robots.txt | grep -c '^Sitemap:'   # 1
```

Then the human checks from SEO_PLAN §6 (browser, ~10 min):
[Google Rich Results Test](https://search.google.com/test/rich-results) and
[Yandex microtest](https://webmaster.yandex.ru/tools/microtest/) on 2–3 entry
URLs (e.g. `…/entry.php?key=agni`, one homonym like `mana`, one miss), and
submit `…/scans/awork/apidev/app/sitemaps/sitemap.xml` in Google Search
Console and Yandex.Webmaster.

## Escalation

Stop and report instead of improvising when: `entry.php` stops returning 200;
`download_hwnorm1c_sqlite.sh` fails its own verification (it refuses to touch
the live DB on any failed check — that refusal is correct, do not bypass it);
the sitemap index lists shards that fetch non-200; or the robots.txt merge
would alter non-`*` groups. Rollback is cheap: restore
`robots.txt.bak-<date>`, and (if needed) `rm -rf "$APIDEV/app/sitemaps"` —
both are regenerable. Report failures as a GitHub issue on
[sanskrit-lexicon/csl-apidev](https://github.com/sanskrit-lexicon/csl-apidev/issues)
with the failing command and its full output.

_Dr. Mārcis Gasūns_
