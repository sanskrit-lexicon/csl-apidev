#!/usr/bin/env python3
# Spot-check round-trip: beng_slp1.xml (H1497) vs. deva->slp1 for sample
# Bengali-script Sanskrit headwords. Re-implements transcoder.php's
# longest-match FSM walk in Python so this can run standalone (no PHP CLI
# needed) against the actual generated XML file.
import re
import sys
import xml.etree.ElementTree as ET
from indic_transliteration import sanscript
from indic_transliteration.sanscript import transliterate

sys.stdout.reconfigure(encoding='utf-8')

_U_RE = re.compile(r'\\u([0-9A-Fa-f]{4})')

def unicode_parse(val):
    # mirrors transcoder.php's transcoder_unicode_parse(): expand literal
    # '\uXXXX' escapes to the actual character, pass everything else through
    return _U_RE.sub(lambda m: chr(int(m.group(1), 16)), val)

def load_fsm(path):
    tree = ET.parse(path)
    entries = []
    for e in tree.getroot():
        if e.tag != 'e':
            continue
        inval = unicode_parse(e.find('in').text or '')
        outval = unicode_parse(e.find('out').text or '')
        entries.append((inval, outval))
    # group by first char for speed; keep longest-match semantics
    by_first = {}
    for inval, outval in entries:
        if not inval:
            continue
        by_first.setdefault(inval[0], []).append((inval, outval))
    return by_first

def transcode(line, by_first):
    n = 0
    m = len(line)
    out = []
    while n < m:
        c = line[n]
        candidates = by_first.get(c, [])
        best_in, best_out = None, None
        for inval, outval in candidates:
            if line.startswith(inval, n) and (best_in is None or len(inval) > len(best_in)):
                best_in, best_out = inval, outval
        if best_in is not None:
            out.append(best_out)
            n += len(best_in)
        else:
            out.append(c)
            n += 1
    return ''.join(out)

by_first = load_fsm('beng_slp1.xml')

# NOTE: Bengali script does not distinguish Devanagari ब/व (both write as
# ব) -- indic_transliteration's own Bengali->Devanagari reverse mapping
# has the identical ambiguity (व wins), so words containing ब are
# excluded here rather than treated as a table defect. See gen_beng_slp1.py.
words_deva = ['धर्म', 'कृष्ण', 'योग', 'संस्कृत', 'विद्या', 'गुरु', 'शिव',
              'नमः', 'देव', 'राम', 'सीता', 'ऋषि', 'ॐ']

fails = 0
for dv in words_deva:
    bv = transliterate(dv, sanscript.DEVANAGARI, sanscript.BENGALI)
    expected = transliterate(dv, sanscript.DEVANAGARI, sanscript.SLP1)
    got = transcode(bv, by_first)
    status = 'OK' if got == expected else 'MISMATCH'
    if got != expected:
        fails += 1
    print('%-8s deva=%-10s beng=%-10s expected=%-12s got=%-12s' % (status, dv, bv, expected, got))

print()
print('%d/%d mismatches' % (fails, len(words_deva)))
sys.exit(1 if fails else 0)
