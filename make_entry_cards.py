# coding=utf-8
"""make_entry_cards.py — Cologne-owned OG preview cards (1200x630 PNG) for
dictionary entry pages (H6478 follow-up, MG ruling «make it now, similar to
kosha» / «keep them separate»).

Renders one og/<dict>_<token>.png per requested SLP1 headword; og_preview.php
emits og:image + twitter:card only when that file exists, so coverage grows
with the committed og/ set. Nothing here touches kosha or samskrtam.ru — the
cards are rendered from Cologne's own live entry pages.

Usage:
    python3 make_entry_cards.py --dict mw agni vac karman
    BASE_URL=... CHROME_BIN=...  (defaults: live Cologne + macOS Chrome)

The SLP1 -> Devanagari/IAST converters below are self-contained (standard SLP1
scheme); Vedic accent marks are not handled — dictionary headwords do not
carry them.
"""
import argparse
import html
import os
import re
import subprocess
import sys
import time
import urllib.parse
import urllib.request

if hasattr(sys.stdout, "reconfigure"):
    sys.stdout.reconfigure(encoding="utf-8")

# Transliteration via the org's single shared library (SHARED_CODE family #1,
# the same dependency kosha pins): pip install sanskrit-util, or add the org
# checkout's py/ dir to PYTHONPATH.
try:
    from sanskrit_util import from_slp1, iast_to_devanagari

    def slp1_to_devanagari(key):
        return iast_to_devanagari(from_slp1(key))

    def slp1_to_iast(key):
        return from_slp1(key)
except ImportError:  # pragma: no cover
    sys.exit("error: sanskrit-util is required (pip install sanskrit-util)")

BASE_URL = os.environ.get(
    "BASE_URL",
    "https://www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/getword.php")
CHROME = os.environ.get(
    "CHROME_BIN",
    "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome")
OUT_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), "og")
TMP_HTML = os.path.join(os.path.dirname(os.path.abspath(__file__)),
                        "_entry_card_tmp.html")

def card_token(slp1_key):
    key = slp1_key.lower()
    return re.sub(r"[^a-z0-9]", lambda m: "_%02x" % ord(m.group(0)), key)


def record_from_sqlite(sqlite_path, dictlower, key):
 # Cologne-owned offline source: the dictionary's own sqlite (e.g.
 # ~/Documents/GitHub/mw/web/sqlite/mw.sqlite, table <dict>, columns
 # key/lnum/data; data = <H1><h>...<body>html</body>). No network involved.
 import json
 import sqlite3
 conn = sqlite3.connect(sqlite_path)
 try:
  cur = conn.execute(
   'SELECT data FROM %s WHERE key = ? ORDER BY lnum LIMIT 1'
   % dictlower, (key,))
  row = cur.fetchone()
 finally:
  conn.close()
 if not row:
  raise KeyError('no record for %s in %s' % (key, sqlite_path))
 url = 'sqlite:%s#%s' % (os.path.basename(sqlite_path), key)
 return url, row[0]


def fetch_entry(dictlower, key):
    url = "%s?dict=%s&key=%s&input=slp1&output=iast" % (
        BASE_URL, dictlower, urllib.parse.quote(key))
    # the host filters user agents: browser-like strings get 403/500, the
    # curl UA passes (probed 11-10-2026)
    last = None
    for attempt in range(3):
        req = urllib.request.Request(
            url, headers={"User-Agent": "curl/8.7.1"})
        try:
            with urllib.request.urlopen(req, timeout=30) as resp:
                return url, resp.read().decode("utf-8", "replace")
        except Exception as e:  # host intermittently 403/500s; back off
            last = e
            time.sleep(2 * (attempt + 1))
    raise last


def parse_entry(page_html):
    mh1 = re.search(r"<h1>[^<]*(?:<span[^>]*>)?([^<]+)", page_html)
    headword = mh1.group(1).strip() if mh1 else ""
    # first display cell, record-ID tail dropped, tags stripped — mirrors
    # og_preview_description() in og_preview.php
    mtd = re.search(r"<td class='display'[^>]*>(.*?)</td>", page_html, re.S)
    sense = ""
    if mtd:
        body = re.sub(r'<span class="lnum">.*?</span>', "", mtd.group(1),
                      flags=re.S)
        sense = re.sub(r"\s+", " ", strip_tags(body)).strip()
        if len(sense) > 200:
            cut = sense.find(" ", 180)
            sense = sense[:cut if cut != -1 else 200].rstrip() + "..."
    return headword, sense


def strip_tags(s):
    return re.sub(r"<[^>]+>", " ", s)


def clean_sense(s):
    # Cologne data noise: udAtta slashes (agni/ -> agni), space before
    # punctuation, collapsed blanks
    s = s.replace("/", "").replace(" ,", ",").replace(" .", ".")
    return re.sub(r"\s+", " ", s).strip()


TEMPLATE = u"""<!DOCTYPE html>
<html><head><meta charset="UTF-8">
<style>
html,body {{ margin:0; padding:0; }}
#card {{ width:1200px; height:630px; background:#ffffff; box-sizing:border-box;
 padding:56px 72px 48px; border-top:10px solid #1a3e6e; display:flex; flex-direction:column;
 font-family:'Charis SIL','Gentium Plus',Georgia,'Times New Roman',serif; color:#1f2328; }}
.badge {{ font-size:24px; color:#59636e; }}
.badge b {{ color:#1a3e6e; }}
.hw {{ margin-top:14px; display:flex; align-items:baseline; gap:28px; }}
.deva {{ font-family:'Devanagari Sangam MN','Noto Serif Devanagari','Sanskrit 2003',serif;
 font-size:84px; color:#8b0000; line-height:1.2; }}
.iast {{ font-size:48px; font-style:italic; color:#3d444d; }}
.dict {{ font-size:24px; color:#ffffff; background:#1a3e6e; border-radius:8px;
 padding:4px 14px; margin-left:auto; }}
.sense {{ font-size:32px; line-height:1.5; color:#24292f; margin-top:34px;
 display:-webkit-box; -webkit-line-clamp:5; -webkit-box-orient:vertical; overflow:hidden; }}
.footer {{ margin-top:auto; display:flex; justify-content:space-between;
 font-size:24px; color:#59636e; border-top:2px solid #d1d9e0; padding-top:18px; }}
</style></head>
<body><div id="card">
<div class="badge"><b>Cologne Digital Sanskrit Dictionaries</b> · {dictlabel}</div>
<div class="hw"><span class="deva">{deva}</span><span class="iast">{iast}</span><span class="dict">{dictlabel}</span></div>
<div class="sense">{sense}</div>
<div class="footer"><span>{url}</span><span>scanned print anchored</span></div>
</div></body></html>
"""


def render(dictlower, key, sqlite_path=None):
    if sqlite_path:
        url, page = record_from_sqlite(sqlite_path, dictlower, key)
        page = re.search(r"<body>(.*)</body>", page, re.S).group(1)
        headword = slp1_to_iast(key)
        body = re.sub(r'<span class="lnum">.*?</span>', "", page, flags=re.S)
        sense = clean_sense(re.sub(r"\s+", " ", strip_tags(body)))
    else:
        url, page = fetch_entry(dictlower, key)
        headword, sense = parse_entry(page)
    deva = slp1_to_devanagari(key)
    iast = headword or slp1_to_iast(key)
    dictlabel = dictlower.upper()
    entry_url = (
        "https://www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/"
        "getword.php?dict=%s&key=%s&input=slp1&output=iast"
        % (dictlower, urllib.parse.quote(key)))
    page_html = TEMPLATE.format(
        dictlabel=dictlabel, deva=deva, iast=html.escape(iast),
        sense=html.escape(sense or (iast + " — Cologne entry")),
        url=entry_url)
    with open(TMP_HTML, "w", encoding="utf-8") as f:
        f.write(page_html)
    out = os.path.join(OUT_DIR, "%s_%s.png" % (dictlower, card_token(key)))
    if os.path.exists(out):
        os.remove(out)
    cmd = [CHROME, "--headless", "--disable-gpu", "--hide-scrollbars",
           "--allow-file-access-from-files", "--window-size=1200,630",
           "--virtual-time-budget=3000", "--screenshot=%s" % out,
           "file://%s" % TMP_HTML]
    try:
        subprocess.run(cmd, capture_output=True, timeout=60,
                       encoding="utf-8", errors="replace")
    except subprocess.TimeoutExpired:
        print("CARD TIMEOUT", dictlower, key)
        return False
    if not os.path.exists(out) or os.path.getsize(out) == 0:
        print("CARD FAIL", dictlower, key)
        return False
    print("card written:", os.path.relpath(out), os.path.getsize(out), "bytes")
    return True


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--dict", default="mw", help="dictionary code (mw, ap90, ...)")
    ap.add_argument("--sqlite", default=None,
                    help="offline source: the dictionary's own sqlite "
                         "(preferred; the live host throttles and blocks)")
    ap.add_argument("keys", nargs="+", help="SLP1 headwords")
    args = ap.parse_args()
    dictlower = args.dict.lower()
    os.makedirs(OUT_DIR, exist_ok=True)
    fails = [k for k in args.keys if not render(dictlower, k, args.sqlite)]
    if os.path.exists(TMP_HTML):
        os.remove(TMP_HTML)
    if fails:
        print("FAILED:", ",".join(fails))
        return 1
    print("%d cards OK (%s)" % (len(args.keys), dictlower))
    return 0


if __name__ == "__main__":
    sys.exit(main())
