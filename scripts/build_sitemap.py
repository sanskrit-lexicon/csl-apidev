#!/usr/bin/env python3
"""build_sitemap.py -- XML sitemap index + sharded child sitemaps for the
server-rendered entry permalinks (H227 P0.5).

Source of URLs: the union headword database hwnorm1c.sqlite (the same
normalized-key universe getsuggest/simple-search use; one row per
normalized SLP1 key, data = spelling:DICT,DICT;spelling2:DICTS).
Every emitted URL is one canonical stacked entry page:

    <base>/entry.php?key=<normkey>

Thin-content gate (doc/ux-redesign/SEO_PLAN.md §5):
  * a key is emitted only if it parses as a pure SLP1 headword
    (letters + optional trailing homonym digits) -- this drops OCR junk
    like '???' and keys with embedded spaces;
  * a key is emitted only once (the stacked page IS the canonical --
    per-dictionary views are noindex and never sitemapped);
  * keys are emitted only if attested in >= --min-dicts dictionaries
    (default 1: every union headword is a real entry in at least one
    dictionary, so there are no 'empty stub' pages by construction).

Output: sitemap.xml (index) + sitemap-NNN.xml.gz shards of at most
--shard-size URLs (default 50000, the sitemaps.org maximum).

Usage:
    python scripts/build_sitemap.py \
        --db simple-search/hwnorm1/hwnorm1c.sqlite \
        --base https://www.sanskrit-lexicon.uni-koeln.de/csl-apidev/app \
        --out app/sitemaps

hwnorm1c.sqlite is not committed; fetch it with download_hwnorm1c_sqlite.sh
(GitHub release asset of sanskrit-lexicon/csl-sqlite).
"""
import argparse
import gzip
import os
import re
import sqlite3
import sys
import urllib.parse
from html import escape  # stdlib; avoid xml.* (CodeQL use-defused-xml)

sys.stdout.reconfigure(encoding='utf-8')
sys.stderr.reconfigure(encoding='utf-8')

SLP1_KEY_RE = re.compile(r'^[a-zA-Z]+[0-9]*$')


def iter_keys(db_path, min_dicts):
    """Yield sitemap-eligible normalized keys from hwnorm1c.sqlite."""
    con = sqlite3.connect(db_path)
    try:
        for key, data in con.execute(
                'SELECT key, data FROM hwnorm1c ORDER BY key'):
            if not SLP1_KEY_RE.match(key):
                continue  # OCR junk ('???'), embedded spaces, non-SLP1
            if min_dicts > 1:
                dicts = set()
                for part in data.split(';'):
                    if ':' in part:
                        dicts.update(part.split(':', 1)[1].split(','))
                if len(dicts) < min_dicts:
                    continue
            yield key
    finally:
        con.close()


def write_shard(path, base, keys):
    with gzip.open(path, 'wt', encoding='utf-8', newline='\n') as f:
        f.write('<?xml version="1.0" encoding="UTF-8"?>\n')
        f.write('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n')
        for key in keys:
            loc = '%s/entry.php?key=%s' % (base, urllib.parse.quote(key))
            f.write(' <url><loc>%s</loc></url>\n' % escape(loc))
        f.write('</urlset>\n')


def write_index(path, base_out_url, shard_names):
    with open(path, 'w', encoding='utf-8', newline='\n') as f:
        f.write('<?xml version="1.0" encoding="UTF-8"?>\n')
        f.write('<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n')
        for name in shard_names:
            f.write(' <sitemap><loc>%s/%s</loc></sitemap>\n'
                    % (escape(base_out_url), name))
        f.write('</sitemapindex>\n')


def main():
    ap = argparse.ArgumentParser(description=__doc__.splitlines()[0])
    ap.add_argument('--db', default='simple-search/hwnorm1/hwnorm1c.sqlite',
                    help='path to hwnorm1c.sqlite')
    ap.add_argument('--base',
                    default='https://www.sanskrit-lexicon.uni-koeln.de/csl-apidev/app',
                    help='absolute base URL of app/ (no trailing slash)')
    ap.add_argument('--out', default='app/sitemaps',
                    help='output directory for sitemap.xml + shards')
    ap.add_argument('--shard-size', type=int, default=50000,
                    help='max URLs per shard (sitemaps.org cap: 50000)')
    ap.add_argument('--min-dicts', type=int, default=1,
                    help='emit keys attested in at least N dictionaries')
    args = ap.parse_args()

    if not os.path.exists(args.db):
        sys.exit('ERROR: %s not found -- fetch it with '
                 'download_hwnorm1c_sqlite.sh first.' % args.db)
    base = args.base.rstrip('/')
    os.makedirs(args.out, exist_ok=True)

    shard_names = []
    shard = []
    total = 0

    def flush():
        if not shard:
            return
        name = 'sitemap-%03d.xml.gz' % (len(shard_names) + 1)
        write_shard(os.path.join(args.out, name), base, shard)
        shard_names.append(name)
        print('  %s: %d urls' % (name, len(shard)))
        shard.clear()

    for key in iter_keys(args.db, args.min_dicts):
        shard.append(key)
        total += 1
        if len(shard) >= args.shard_size:
            flush()
    flush()

    # The sitemap index must reference the shards at their SERVED URL; the
    # shards are deployed next to sitemap.xml, so reuse the same directory.
    index_url = base + '/' + os.path.basename(os.path.normpath(args.out))
    write_index(os.path.join(args.out, 'sitemap.xml'), index_url, shard_names)
    print('sitemap.xml: %d shards, %d urls total' % (len(shard_names), total))


if __name__ == '__main__':
    main()
