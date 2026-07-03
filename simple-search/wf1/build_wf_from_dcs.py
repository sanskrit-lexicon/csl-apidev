#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
build_wf_from_dcs.py  --  refresh the simple-search ranking frequencies (wf.txt)
from the Digital Corpus of Sanskrit (DCS) 2026 lemma export.

Roadmap reference: simple-search/roadmap_v1.2.md  Fix I (section 12).

Pipeline (faithful to the live engine):
  DCS lemma (IAST)  --roman_slp1.xml-->  SLP1  --dalnorm.normalize()-->  normkey
Aggregate token_count by normkey (sum), then MERGE over the legacy wf0/wf.txt:
  refreshed count = DCS count where the normkey is attested, else legacy count.
This keeps the exact 50k key universe of wf0 (a drop-in replacement) while
refreshing the numbers with 2026 corpus data.

Why not reuse the repo transcoder.py?  It is Python-2 (uses unichr).  We parse
the SAME utilities/transcoder/roman_slp1.xml table and replicate its
longest-match substitution, so the SLP1 output is identical.  dalnorm.normalize()
is pure regex (no DB), ported line-for-line from v1.1/dalnorm.php.

Usage:
  python build_wf_from_dcs.py [lemmas.csv] [wf0/wf.txt] [out wf.txt] [roman_slp1.xml]
All four have sensible defaults relative to this script.
"""
import csv, os, re, sys, unicodedata
import xml.etree.ElementTree as ET
sys.stdout.reconfigure(encoding='utf-8')
sys.stderr.reconfigure(encoding='utf-8')

HERE = os.path.dirname(os.path.abspath(__file__))
DEF_LEMMAS = os.path.normpath(os.path.join(
    HERE, '..', '..', '..', 'VisualDCS', 'src', 'DCS-data-2026',
    'exports', 'clean', 'lemmas.csv'))
DEF_WF0   = os.path.normpath(os.path.join(HERE, '..', 'wf0', 'wf.txt'))
DEF_OUT   = os.path.join(HERE, 'wf.txt')
DEF_XML   = os.path.normpath(os.path.join(
    HERE, '..', '..', 'utilities', 'transcoder', 'roman_slp1.xml'))

# ---------------------------------------------------------------- transcoder
def _decode(s):
    """Replicate transcoder.py to_unicode(): decode literal \\uXXXX escapes."""
    if s is None:
        return ''
    if '\\u' not in s:
        return s
    parts = s.split('\\u')
    out = parts[0]
    for z in parts[1:]:
        if z == '':
            continue
        out += chr(int(z[:4], 16)) + z[4:]
    return out

def load_roman_slp1(xmlpath):
    tree = ET.parse(xmlpath)
    mapping = {}
    for e in tree.getroot():
        if e.tag != 'e':
            continue
        inv = _decode(e.findtext('in'))
        outv = _decode(e.findtext('out')) or ''
        if inv:
            mapping[inv] = outv
    keys = sorted(mapping.keys(), key=len, reverse=True)  # longest-match first
    return mapping, keys

# MINOR-6: VisualDCS's clean/lemmas.csv export carries mangled codepoints for
# 6 lemmas (kḷp/prakḷp/vikḷp/prakṝ/āpṝ/avakḷptika, 23 tokens total) --
# U+FFB1 in place of vocalic-l ḷ (U+1E37) and U+FFDE in place of long vocalic-r
# ṝ (U+1E5D). Uncorrected, these silently transcode/strip to garbage ('kp',
# 'prak', 'Ap', ...) and land as 6 junk rows in dcs_cdsl_xref.tsv (harmless for
# wf.txt -- the garbage keys don't collide with real wf0 keys). Repair before
# transcoding; the export defect itself is filed upstream to VisualDCS.
_MOJIBAKE_REPAIR = {'ﾱ': 'ḷ', '￞': 'ṝ'}

def repair_mojibake(word):
    for bad, good in _MOJIBAKE_REPAIR.items():
        word = word.replace(bad, good)
    return word

def make_transcoder(xmlpath):
    mapping, keys = load_roman_slp1(xmlpath)
    def transcode(word):
        w = unicodedata.normalize('NFC', repair_mojibake(word))
        out = []
        i, n = 0, len(w)
        while i < n:
            for k in keys:
                if w.startswith(k, i):
                    out.append(mapping[k]); i += len(k); break
            else:
                out.append(w[i]); i += 1   # identity pass-through (k->k, a->a ...)
        return re.sub(r'[^a-zA-Z]', '', ''.join(out))  # keep SLP1 letters only
    return transcode

# ------------------------------------------- dalnorm.normalize (ported, v1.1)
_NASAL = {'k':'N','K':'N','g':'N','G':'N','N':'N',
          'c':'Y','C':'Y','j':'Y','J':'Y','Y':'Y',
          'w':'R','W':'R','q':'R','Q':'R','R':'R',
          't':'n','T':'n','d':'n','D':'n','n':'n',
          'p':'m','P':'m','b':'m','B':'m','m':'m'}
_RXX = {'k':'K','g':'G','c':'C','j':'J','w':'W','q':'Q','t':'T','d':'D','p':'P','b':'B'}

def dalnorm_normalize(key):
    a = key
    a = re.sub(r'(M)([kKgGNcCjJYwWqQRtTdDnpPbBm])', lambda m: _NASAL[m.group(2)] + m.group(2), a)
    a = re.sub(r'([r])(.)\2', r'\1\2', a)
    def _rxX(m):
        x, X = m.group(1), m.group(2)
        return ('r' + X) if (x in _RXX and X == _RXX[x]) else ('r' + x + X)
    a = re.sub(r'r(.)(.)', _rxX, a)
    a = re.sub(r'aH$', 'a', a)
    a = re.sub(r'uH$', 'u', a)
    a = re.sub(r'iH$', 'i', a)
    a = re.sub(r'ttr', 'tr', a)
    a = re.sub(r'ant$', 'at', a)
    a = re.sub(r'([aAiIuUfFxXeEoO])C', r'\1cC', a)
    a = re.sub(r'([kKgGNcCjJYwWqQRtTdDnpPbBmyrlvhzSsHM])cC', r'\1C', a)
    return a

# ------------------------------------------------------------------- main
def main():
    lemmas = sys.argv[1] if len(sys.argv) > 1 else DEF_LEMMAS
    wf0    = sys.argv[2] if len(sys.argv) > 2 else DEF_WF0
    out    = sys.argv[3] if len(sys.argv) > 3 else DEF_OUT
    xml    = sys.argv[4] if len(sys.argv) > 4 else DEF_XML
    for p in (lemmas, wf0, xml):
        if not os.path.exists(p):
            sys.exit('MISSING input: %s' % p)

    transcode = make_transcoder(xml)

    # 1. DCS lemmas -> normkey frequencies
    dcs = {}           # normkey -> summed token_count
    n_lemma = n_mapped = 0
    with open(lemmas, encoding='utf-8') as f:
        for row in csv.DictReader(f):
            n_lemma += 1
            tc = int(row['token_count'] or 0)
            nk = dalnorm_normalize(transcode(row['lemma']))
            if not nk:
                continue
            n_mapped += 1
            dcs[nk] = dcs.get(nk, 0) + tc

    # 2. legacy wf0 (preserve order) -> refreshed wf1
    keys_order, oldcount = [], {}
    with open(wf0, encoding='utf-8') as f:
        for line in f:
            line = line.strip()
            if not line:
                continue
            parts = line.split()
            k = parts[0]
            v = int(parts[1]) if len(parts) > 1 and parts[1].lstrip('-').isdigit() else 0
            keys_order.append(k); oldcount[k] = v

    # Addendum A1 (Fable 5 second-pass review, PR #65): some wf0 keys are dead
    # pre-2017 legacy spellings that predate a later dalnorm rule change --
    # e.g. wf0 stores "praC" but the current dalnorm.normalize() rule (the
    # cC-doubling clause) would fold that same word to "pracC", which is the
    # key the DCS export actually produces. A literal `k in dcs` lookup never
    # matches these, so their real corpus counts are silently forfeited.
    # Re-normalizing the wf0 key a second time recovers 89 refreshable
    # normkeys / 396 tokens (pracC<-praC 91, icCA<-iCA 28, ...).
    refreshed = zeroed_to_pos = legacy_refreshed = 0
    with open(out, 'w', encoding='utf-8', newline='\n') as g:   # no BOM, LF
        for k in keys_order:
            renorm = dalnorm_normalize(k)
            if k in dcs:
                refreshed += 1
                if oldcount[k] <= 0 and dcs[k] > 0:
                    zeroed_to_pos += 1
                g.write('%s %d\n' % (k, dcs[k]))
            elif renorm != k and renorm in dcs:
                legacy_refreshed += 1
                if oldcount[k] <= 0 and dcs[renorm] > 0:
                    zeroed_to_pos += 1
                g.write('%s %d\n' % (k, dcs[renorm]))
            else:
                g.write('%s %d\n' % (k, oldcount[k]))

    dcs_only = sorted((c, k) for k, c in dcs.items() if k not in oldcount)
    dcs_only.reverse()
    print('DCS lemmas read              : %d' % n_lemma)
    print('  -> mapped to a normkey     : %d' % n_mapped)
    print('  -> distinct normkeys       : %d' % len(dcs))
    print('wf0 keys (key universe kept) : %d' % len(keys_order))
    print('  -> refreshed from DCS      : %d  (%.1f%%)' % (refreshed, 100*refreshed/len(keys_order)))
    print('  -> refreshed via legacy-spelling re-normalize (A1): %d' % legacy_refreshed)
    print('  -> were 0/neg, now positive: %d' % zeroed_to_pos)
    print('DCS normkeys NOT in wf0       : %d  (corpus forms outside the headword set)' % len(dcs_only))
    print('  top 6 such:', ', '.join('%s(%d)' % (k, c) for c, k in dcs_only[:6]))
    print('wrote: %s' % out)

if __name__ == '__main__':
    main()
