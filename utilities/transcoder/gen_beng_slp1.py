#!/usr/bin/env python3
# Generates utilities/transcoder/beng_slp1.xml (Bengali Unicode -> SLP1)
# from the indic_transliteration Devanagari-pivoted scheme tables (H1497).
# Mirrors deva_slp1.xml's inherent-vowel handling, but with explicit
# consonant+matra / consonant+virama entries instead of the engine-specific
# '/^' lookahead hack (that hack only fires for a hardcoded fromto whitelist
# in transcoder.php and does not include beng_slp1).
#
# Bengali script does not distinguish some Devanagari letter pairs that map
# to the same Bengali grapheme (e.g. ब and व both write as ব). Where that
# happens, take the SAME canonical resolution indic_transliteration itself
# uses for its Bengali->Devanagari reverse direction (last dict-insertion
# wins), so this table's ambiguous cases agree with the library's own.
import sys
from indic_transliteration import sanscript

sys.stdout.reconfigure(encoding='utf-8')

beng = sanscript.SCHEMES['bengali']
slp1 = sanscript.SCHEMES['slp1']

def u_escape(ch):
    return ''.join('\\u%04x' % ord(c) for c in ch)

def reverse_map(category, extra=None):
    # beng-grapheme -> slp1-value, later devanagari keys win on collision
    # (matches indic_transliteration's own reverse-mapping convention)
    rev = {}
    src = dict(beng[category])
    if extra:
        src.update(extra)
    slp1_cat = slp1.get(category, {})
    slp1_extra = slp1.get('extra_' + category, {}) if category != 'extra_consonants' else {}
    for dv, bv in src.items():
        sv = slp1_cat.get(dv) or slp1_extra.get(dv)
        if sv is None or bv == dv:
            continue
        rev[bv] = sv
    return rev

entries = []
n = 0

def add(inval, outval):
    global n
    entries.append("<e n='%d'> <s>INIT</s> <in>%s</in> <out>%s</out> </e>" % (n, u_escape(inval), outval))
    n += 1

vowels = reverse_map('vowels')
vowel_marks = reverse_map('vowel_marks')
yogavaahas = reverse_map('yogavaahas')
symbols = reverse_map('symbols')

consonants = reverse_map('consonants')
consonants.update(reverse_map('extra_consonants'))

beng_virama = list(beng['virama'].values())[0]

entries.append('<!-- independent vowels -->')
for bv, sv in vowels.items():
    add(bv, sv)

entries.append('<!-- standalone vowel marks -->')
for bv, sv in vowel_marks.items():
    add(bv, sv)

entries.append('<!-- yogavaahas -->')
for bv, sv in yogavaahas.items():
    add(bv, sv)

entries.append('<!-- symbols -->')
for bv, sv in symbols.items():
    add(bv, sv)

entries.append('<!-- consonant + vowel-sign -> full syllable -->')
for bv_c, sv_c in consonants.items():
    for bv_v, sv_v in vowel_marks.items():
        add(bv_c + bv_v, sv_c + sv_v)

entries.append('<!-- consonant + virama -> bare consonant -->')
for bv_c, sv_c in consonants.items():
    add(bv_c + beng_virama, sv_c)

entries.append('<!-- bare consonant -> consonant + inherent a -->')
for bv_c, sv_c in consonants.items():
    add(bv_c, sv_c + 'a')

entries.append('<!-- whitespace passthrough -->')
for c in (chr(9), chr(10), chr(13), chr(32)):
    add(c, u_escape(c))

header = """<fsm start='INIT' inputDecoding='UTF-8' outputEncoding='UTF-8'>
<!-- 27-07-2026: Bengali script -> SLP1 transcoder table (H1497), derived
     programmatically from the indic_transliteration Devanagari-pivoted
     bengali/slp1 scheme tables. Mirrors deva_slp1.xml's inherent-vowel
     (schwa) handling via explicit consonant+matra / consonant+virama
     entries rather than the '/^' lookahead hack in transcoder.php, since
     that hack is hardcoded to a fromto whitelist that excludes beng_slp1.
     Bengali does not distinguish some Devanagari letter pairs that share
     one Bengali grapheme (e.g. ब/व both write as ব); those cases
     resolve to whichever letter indic_transliteration's own Bengali to
     Devanagari reverse mapping picks, so this table agrees with the
     library it was derived from.
     Storage/lookup table only, not wired into simple_search.php's
     detect loop (that step needs its own MG narrow-exception, same as
     Velthuis got). -->
"""

with open('beng_slp1.xml', 'w', encoding='utf-8', newline='\n') as f:
    f.write(header)
    for e in entries:
        f.write(e + '\n')
    f.write('</fsm>\n')

print('wrote', n, 'entries')
