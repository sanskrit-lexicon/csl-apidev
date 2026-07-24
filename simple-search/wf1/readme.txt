wf1/  -- ranking frequencies refreshed from the Digital Corpus of Sanskrit (2026)

Generated 2026-06-11.  Roadmap: simple-search/roadmap_v1.2.md  Fix I (sec. 12).

WHAT
  wf1/wf.txt is a drop-in replacement for wf0/wf.txt -- the `slp1_key  count`
  table loaded by init_word_frequency() and used by order_by_wf() to order
  simple-search results.  Same 50,574-LINE / 50,474-distinct-KEY universe as
  wf0 (wf0 has 100 duplicate-key lines, 87 distinct duplicated keys --
  pre-existing quirk, faithfully preserved); only the COUNTS are refreshed,
  using 2026 corpus frequencies.

SOURCE
  VisualDCS/src/DCS-data-2026/exports/clean/lemmas.csv
    15,902 DCS lemmas: lemma_id, lemma (IAST), grammar, preverbs, token_count

PIPELINE (build_wf_from_dcs.py)
  1. lemma (IAST) --> SLP1, via the repo table utilities/transcoder/roman_slp1.xml
     (longest-match substitution; same data the PHP transcoder uses).
  2. SLP1 --> normkey, via dalnorm.normalize() ported line-for-line from
     v1.1/dalnorm.php (pure regex: anusvara->homorganic nasal, r-doubling,
     aH/uH/iH endings, ttr->tr, ant->at, cC handling).
  3. Sum token_count per normkey.
  4. MERGE over wf0: refreshed count where the normkey is DCS-attested, else the
     legacy wf0 count (keeps full coverage; this is the Q12 "refresh-in-place"
     policy -- flip MERGE in the script for DCS-only).

RESULT (this build)
  - 12,096 of 50,574 lines refreshed (12,055 of 50,474 distinct keys -- the
    12,096 count includes 41 duplicate-key lines; the high-frequency core,
    ~94% of token mass)
  - 1,573 keys that were 0/neg in wf0 now carry positive 2026 counts
  - Bonus: wf0 has two `ca` lines (179, then 1); PHP's last-wins assoc array
    means the live engine has been ranking `ca` at wf=1 -- wf1 heals this
    incidentally (both lines now read 3385).
  - 2,921 DCS normkeys are NOT in wf0 (corpus forms outside the headword set:
    causative/derived stems kAray, darSay, cintay; sandhi kaScit, kadAcid) -- not
    written into wf.txt (they are never looked up), but they are the raw material
    for a future "corpus attestation" pruning signal.
  Examples (wf0 -> wf1): tad 180->3734, ca 179->3385, kf 163->1083,
                         rAjan 84->588, agni 124->295.

REGENERATE
  cd simple-search/wf1
  python build_wf_from_dcs.py [lemmas.csv] [../wf0/wf.txt] [wf.txt] [roman_slp1.xml]
  (all four args optional; defaults resolve relative to this folder. Requires the
   VisualDCS repo as a sibling of csl-apidev for the default lemmas.csv path.)

ACTIVATED  (2026-07-24, Fix I)
  v1.1 and v1.1a getword_list_1.0_main.php default to simple-search/wf1/wf.txt.
  Rollback: pass ?freqsrc=wf0 on the getword_list API.
  Response JSON includes "freq_source": "wf1" | "wf0".
  (Live Cologne server must deploy this csl-apidev commit for the public API.)

CAVEAT (roadmap Q11)
  lemmas.csv(clean) token_count sums to ~134k -- a filtered slice, not the full
  4.57M-token corpus (dcs_full.sqlite).  Relative order is sound (tad, ca, na,
  iti, api on top); pull from dcs_full.sqlite if absolute counts are wanted.
