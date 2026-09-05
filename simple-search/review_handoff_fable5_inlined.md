_Created: 12-06-2026 · Last updated: 05-09-2026_

# REVIEW HANDOFF (fully inlined) — Cologne "simple-search" overhaul, for Fable 5

Paste into a fresh **Fable 5** session. **No repo or network access is required for
the core checks** — the critical source is embedded below. (A repo-access version
with run-it-yourself steps is in [review_handoff_fable5.md](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/review_handoff_fable5.md).)

You are a senior reviewer doing a FULL, CRITICAL, ADVERSARIAL review of work an AI
(Claude) produced on the Cologne Sanskrit Lexicon **simple-search** engine. Re-derive,
diff, and judge correctness. Do NOT rubber-stamp. Cite evidence for every finding.

## Background you need
simple-search is forgiving Sanskrit headword lookup. The user types a word (any
script/spelling); the engine normalizes to **SLP1** (an ASCII transliteration:
`A`=ā, `I`=ī, `f`=ṛ, `S`=ś, `z`=ṣ, `R`=ṇ, `M`=anusvāra, `H`=visarga, `w`=ṭ, `q`=ḍ,
`K`=kh, `C`=ch, etc.) and returns matching headwords. Result ranking uses a word-
frequency file `wf0/wf.txt` (`slp1_key  count`). The author **refreshed those
frequencies from the 2026 Digital Corpus of Sanskrit (DCS)** and built supporting
tooling. The single biggest correctness risk is whether two Python reimplementations
faithfully reproduce the live PHP engine. **They were never executed against the real
PHP (PHP + the hwnorm1c DB were not on the author's host).** That is your TASK 1.

---

## TASK 1 (CRITICAL) — Is the `dalnorm.normalize()` port faithful?

`normalize()` maps an SLP1 string to the engine's canonical key. The author ported it
to Python for `build_wf_from_dcs.py`. **Diff the two below, rule by rule.** Any
divergence corrupts every refreshed frequency key. Look especially at: regex anchoring
(`$`), global vs first-match semantics, the order of rules, the `r(.)(.)`/`r(.)\2`
interaction, and the two `cC` rules.

### Ground truth — PHP (`simple-search/v1.1/dalnorm.php`)
```php
public function normalize($key) {
  $a = $key;
  #1. Use homorganic nasal rather than anusvara
  $a = preg_replace_callback('/(M)([kKgGNcCjJYwWqQRtTdDnpPbBm])/',
    function ($matches) {
     $slp1_cmp1_helper_data = array(
      'k'=>'N','K'=>'N','g'=>'N','G'=>'N','N'=>'N',
      'c'=>'Y','C'=>'Y','j'=>'Y','J'=>'Y','Y'=>'Y',
      'w'=>'R','W'=>'R','q'=>'R','Q'=>'R','R'=>'R',
      't'=>'n','T'=>'n','d'=>'n','D'=>'n','n'=>'n',
      'p'=>'m','P'=>'m','b'=>'m','B'=>'m','m'=>'m');
     $c = $matches[2]; $nasal = $slp1_cmp1_helper_data[$c];
     return ($nasal . $c);
    }, $a);
 #2. 'rxx' -> 'rx'  (NOTE: only 'r', the 'f' variant is commented out in source)
 $a = preg_replace('|([r])(.)\\2|','\\1\\2',$a);
 #2-asp. 'rxX' -> 'rX' where X is the aspirate of x
 $a = preg_replace_callback('/r(.)(.)/',
    function ($matches) {
     $rxX_helper_data = array('k'=>'K','g'=>'G','c'=>'C','j'=>'J','w'=>'W',
       'q'=>'Q','t'=>'T','d'=>'D','p'=>'P','b'=>'B');
     $x = $matches[1]; $X = $matches[2];
     if (isset($rxX_helper_data[$x]) && ($X == $rxX_helper_data[$x])) return ('r' . $X);
     else return ('r' . $x . $X);
    }, $a);
 #4.  aH$ -> a
 $a = preg_replace('|aH$|','a',$a);
 #4a. uH$ -> u
 $a = preg_replace('|uH$|','u',$a);
 #4b. iH$ -> i
 $a = preg_replace('|iH$|','i',$a);
 #5.  ttr -> tr
 $a = preg_replace('|ttr|','tr',$a);
 #6.  ant$ -> at
 $a = preg_replace('|ant$|','at',$a);
 # X(vowel) + C -> XcC
 $a = preg_replace('|([aAiIuUfFxXeEoO])C|','\\1cC',$a);
 # X(consonant) + cC -> XC
 $a = preg_replace('|([kKgGNcCjJYwWqQRtTdDnpPbBmyrlvhzSsHM])cC|','\\1C',$a);
 return $a;
}
```

### Port — Python (`simple-search/wf1/build_wf_from_dcs.py`)
```python
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
```
**Hint:** PHP `$` in `|...$|` and Python `$` in `re.sub(... '...$' ...)` both match
end-of-string (PHP without `/m` = no multiline; Python without `re.M` = end or just
before a trailing `\n`). Inputs here have no newlines. Check whether that, the
non-overlapping left-to-right scan of `r(.)(.)`, and rule ordering truly agree. Produce
a few concrete SLP1 inputs (if any) where the two outputs differ — or state that you
proved equivalence over the SLP1 alphabet.

---

## TASK 2 — Is the IAST→SLP1 transcoder port faithful to the table?

The author did NOT reuse the repo's `transcoder.py` (it's Python-2). Instead the port
parses the table below and does **greedy longest-match** substitution with identity
pass-through for unlisted characters, after `unicodedata.normalize('NFC', ...)`, then
strips non-`[a-zA-Z]`. Check: does greedy-longest-match reproduce the FSM? Are the
multi-codepoint inputs handled (`ṭh`=`ṭh`, `ḍh`, `ḷh`, `aï`, `m̐`)? Does stripping
to `[a-zA-Z]` silently drop avagraha/accents in a way that matters?

### Table (`utilities/transcoder/roman_slp1.xml`)
```xml
<e><in>ai</in><out>E</out></e>   <e><in>au</in><out>O</out></e>
<e><in>kh</in><out>K</out></e>   <e><in>gh</in><out>G</out></e>
<e><in>ch</in><out>C</out></e>   <e><in>jh</in><out>J</out></e>
<e><in>th</in><out>T</out></e>   <e><in>dh</in><out>D</out></e>
<e><in>ph</in><out>P</out></e>   <e><in>bh</in><out>B</out></e>
<e><in>ṭh (ṭh)</in><out>W</out></e>   <e><in>ḍh (ḍh)</in><out>Q</out></e>
<e><in>ṛ (ṛ)</in><out>f</out></e>   <e><in>ḷ (ḷ)</in><out>x</out></e>
<e><in>ā (ā)</in><out>A</out></e>   <e><in>ī (ī)</in><out>I</out></e>
<e><in>ū (ū)</in><out>U</out></e>   <e><in>ṝ (ṝ)</in><out>F</out></e>
<e><in>ḹ (ḹ)</in><out>X</out></e>   <e><in>ṅ (ṅ)</in><out>N</out></e>
<e><in>ñ (ñ)</in><out>Y</out></e>   <e><in>ṭ (ṭ)</in><out>w</out></e>
<e><in>ḍ (ḍ)</in><out>q</out></e>   <e><in>ṇ (ṇ)</in><out>R</out></e>
<e><in>ś (ś)</in><out>S</out></e>   <e><in>ṣ (ṣ)</in><out>z</out></e>
<e><in>ḥ (ḥ)</in><out>H</out></e>   <e><in>ṁ (ṁ)</in><out>M</out></e>
<e><in>ṃ (ṃ)</in><out>M</out></e>   <e><in>'</in><out>'</out></e>
<e><in>ḻ (ḻ)</in><out>L</out></e>   <e><in>ḻh</in><out>|</out></e>
<e><in>aï</in><out>ai</out></e>   <e><in>aü</in><out>au</out></e>
<e><in>m̐ (m+candrabindu)</in><out>~</out></e>
<e><in>ẖ (ẖ)</in><out>Z</out></e>   <e><in>ḫ (ḫ)</in><out>V</out></e>
<e><in>́ udatta</in><out>/</out></e>   <e><in>̀ svarita</in><out>^</out></e>
<e><in>̱ anudatta</in><out>\</out></e>
<!-- plain a i u e o k g c j t d n p b m y r l v s h are NOT listed: identity (=SLP1) -->
```
### Port (same file)
```python
def _decode(s):  # decode literal \uXXXX escapes like transcoder.py to_unicode()
    if s is None: return ''
    if '\\u' not in s: return s
    parts = s.split('\\u'); out = parts[0]
    for z in parts[1:]:
        if z == '': continue
        out += chr(int(z[:4], 16)) + z[4:]
    return out

def make_transcoder(mapping_keys_sorted_longest_first):  # keys sorted by len desc
    def transcode(word):
        w = unicodedata.normalize('NFC', word)
        out, i, n = [], 0, len(w)
        while i < n:
            for k in keys:                      # longest-match first
                if w.startswith(k, i):
                    out.append(mapping[k]); i += len(k); break
            else:
                out.append(w[i]); i += 1        # identity pass-through
        return re.sub(r'[^a-zA-Z]', '', ''.join(out))   # keep SLP1 letters only
    return transcode
```

---

## TASK 3 — Sanskrit correctness of the eval gold set

`gold.tsv` maps `query → intended SLP1 headword`. **Flag any `intended` that is NOT the
correct/most-likely MW headword in SLP1**, or where the user's intent is genuinely
ambiguous. (`dict` is always `mw`; `input=default` is fuzzy, `iast`/`slp1` precise;
empty intended = "should return nothing".)

| query | input | intended | author's note |
|---|---|---|---|
| agni | default | `agni` | exact |
| deva | default | `deva` | 5 results (overgeneration) |
| kara | default | `kara` | 11 results |
| nara | default | `nara` | 6 results |
| sana | default | `sana` | 18 results |
| manas | default | `manas` | 20 results |
| yoga | default | `yoga` | 3 results |
| vishvamitra | default | `viSvAmitra` | proper noun |
| rama | default | `rAma` | HARD: intended rāma ranks #2 behind literal `rama` |
| upanishad | default | `upanizad` | folk 'sh' |
| buddha | default | `budDa` | folk |
| moksha | default | `mokza` | folk ksh→kṣ |
| shiva | default | `Siva` | folk sh→ś |
| lakshmi | default | `lakzmI` | folk ksh |
| rishi | default | `fzi` | folk ri+sh → ṛ + ṣ |
| vishnu | default | `vizRu` | folk sh→ṣ |
| jnana | default | `jYAna` | folk jn→jñ |
| atman | default | `Atman` | long A |
| veda | default | `veda` | exact |
| mantra | default | `mantra` | exact |
| guna | default | `guRa` | cerebral ṇ |
| surya | default | `sUrya` | long U |
| ahimsa | default | `ahiMsA` | anusvāra + long A |
| samsara | default | `saMsAra` | anusvāra |
| nirvana | default | `nirvARa` | cerebral ṇ |
| prana | default | `prARa` | cerebral ṇ |
| shanti | default | `SAnti` | folk sh + long A |
| bhakti | default | `Bakti` | aspirate |
| maya | default | `mAyA` | long A |
| purusha | default | `puruza` | folk sh→ṣ |
| prakriti | default | `prakfti` | folk ri→ṛ |
| guru | default | `guru` | exact |
| manas | iast | `manas` | precise → 1 |
| sana | iast | `sana` | precise → 1 |
| kara | iast | `kara` | precise → 1 |
| saMskfta | iast | `saMskfta` | precise → 1 |
| kṛṣṇa | iast | `kfzRa` | precise IAST |
| viṣṇu | iast | `vizRu` | precise IAST |
| dharma | iast | `Darma` | precise |
| gacchati | iast | `gam` | STREAM-A target: inflected 3sg → root (expected MISS in v1.1) |
| rAmasya | slp1 | `rAma` | STREAM-A target: genitive → stem (expected MISS in v1.1) |
| xqzqxq | default | (empty) | zero-result |
| zzqzz | default | (empty) | zero-result |

Questions to answer: is `karma`-style `-an` stem handling correct here (none included —
should it be)? Is `rama→rAma` defensible, or is `rama` (रम) equally valid? Are the
"expected MISS" rows (`gacchati`, `rAmasya`) correctly understood as v1.1 limitations
the engine cannot currently resolve (no lemmatizer)?

## TASK 4 — Are the eval metrics sound?

`eval_search.py` scores each gold row against the engine's ordered `dicthw` list:
- `rank` = 1-based position of the first result equal to `intended`, else 0.
- `P@1` = 1 if rank==1 else 0 ; `recall@K` = 1 if 1≤rank≤K else 0 ; `RR` = 1/rank or 0.
- **Zero-result rows** (empty intended): "correct" iff the engine returns an empty list;
  then RR=P@1=recall=1, else 0.
- `mean #results` = average result-count per row = the **overgeneration** metric.
- Buckets: `precise` = input ∈ {slp1,deva,iast,hk,itrans}; else `default`.
- Reported v1.1 baseline (offline, the 22 fixtured rows): ALL n=22 P@1=0.95 recall@5=1.00
  MRR=0.977 mean#=4.45 ; default n=18 mean#=5.22 ; precise n=4 mean#=1.00. The one P@1
  miss is `rama` (rAma at rank 2). **Re-derive these from the table + the metric defs and
  confirm.** Critique: is `put_user_word_first` making P@1 trivially high (because the
  user's literal spelling is floated first), so that `mean #results` is the only metric
  really exposing the problem? Is recall@5 a sufficient regression gate for the proposed
  hard-drop (would dropping low-score candidates ever drop the intended word)?

## Reported numbers to sanity-check (challenge each)
- wf1 refresh: 12,096 of 50,574 keys refreshed; 1,573 went 0→positive; examples tad
  180→3734, ca 179→3385, kf 163→1083, rAjan 84→588, agni 124→295.
- crosswalk: 15,902 DCS lemmas; 12,946 (81.4%) linked to a CDSL key; 2,956 DCS-only.
- claim: overgeneration is **default-mode only** — precise input is collapsed to the exact
  word by `restrict_to_user_word` (added 2021); `input=iast` for manas/sana/kara → 1 result.
- provenance caveat: `lemmas.csv` token_counts sum to ~134k, far below the 4.57M-token full
  corpus, so it is a filtered slice; relative order is claimed sound.

## Output
Findings by severity **CRITICAL / MAJOR / MINOR / NIT**, each with the exact rule/row/line,
the problem, the evidence, and a fix. Then a **VERDICT**: (a) is the ported
`dalnorm_normalize` + transcoder faithful enough that `wf1/wf.txt` is safe to deploy?
(b) is the gold set Sanskrit-correct enough to gate real changes? (c) any metric flaw that
would let a regression slip through? End with claims you could not verify without the repo
or the live API.

_Dr. Mārcis Gasūns_
