<?php
/* link_targets.php — H7948 data-driven link-target table for
 * BasicAdjust::ls_callback_pwg_href (v02 basicadjust.php).
 *
 * ONE table, walked in order by the single loop in ls_callback_pwg_href:
 * first row match wins, exactly as the historic copy-pasted blocks did.
 *
 * Row shapes:
 *   array(array(<abbr regex-fragments>), array(array(<pattern>, <url>), ...))
 *     <pattern> is the historic preg_match pattern with {A} where the
 *     abbreviation fragment goes (the fragments are regex fragments and are
 *     inserted verbatim — RAGH. \(Calc\) etc. keep their escapes). <url> is
 *     the historic href template; {N} is preg capture N, {N:roman} runs
 *     romanToInt, {N:lower} runs strtolower. Variants are tried in order
 *     (multi-shape works like KĀTY. ŚR. N,N,N vs N,N), abbreviations outer.
 *   array('fn' => <BasicAdjust method>)
 *     a kept-in-code case (non-URL logic; Stop-budget of H7948): the method
 *     returns BasicAdjust::LINK_MISS to keep walking the table, null to stop
 *     the chain with no href (historic `return $href` with $href = null),
 *     or a string href.
 *
 * Every pattern and URL is transcribed byte-identically from the pre-H7948
 * basicadjust.php (base d090a10); tests/golden_link_targets/ proves parity.
 * Adding an abbreviation = one row here, no code.
 */
return array(
 /******* Spr. — Indische Sprüche: 2nd ed. for pw/pwkvn/ben, 1st for pwg
  * (dict-conditional target — kept in code) ***********/
 array('fn' => 'href_spr'),
 /******* Spr. (II) — Indische Sprüche 2nd edition ***********/
 array(array('Spr[.]'),
  array(array('|^({A}) \(II\) ([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/boesp2/web1/boesp.html?{2}'))),
 /******* Spr. (I) — Indische Sprüche 1st edition: pwg only (gate kept in
  * code; non-pwg match stops the chain) ***********/
 array('fn' => 'href_spr1'),
 /******* Mahabharata Bombay edition - 3 parms ***********/
 array(array('MBH[.]'),
  array(array('|^({A}) *([0-9]+) *, *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/mbhbomb/app1?{2},{3},{4}'))),
 array(array('MBH[.] ed. Bomb.'),
  array(array('|^({A}) *([0-9]+) *, *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/mbhbomb/app1?{2},{3},{4}'))),
 /******* Mahabharata Calcutta edition - 2 parms ***********/
 array(array('MBH[.]'),
  array(array('|^({A}) *([0-9]+) *, *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/mbhcalc?{2}.{3}'))),
 array(array('MBH[.] ed. Calc.'),
  array(array('|^({A}) *([0-9]+) *, *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/mbhcalc?{2}.{3}'))),
 /******* Harivamsa ***********/
 array(array('HARIV[.]'),
  array(array('|^({A}) *([0-9]+)[.]?|',
   'https://sanskrit-lexicon-scans.github.io/hariv?{2}'))),
 /******* Verz. D. Oxf. H. ***********/
 array(array('Verz. d. Oxf. H[.]', 'Verz. der Oxf. H[.]'),
  array(array('|^({A}) *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/Oxf_Cat_Aufrecht/index.html?{2}'))),
 /******* kaTAsaritsAgara ***********/
 array(array('KATHĀS.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/kss/index.html?{2},{3}'))),
 /******* Vājasaneyisaṃhitā ***********/
 array(array('VS.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/vajasasa/app1?{2},{3}'))),
 /******* RĀJATARAṄGIṆĪ Troyer — taranga 7/8 branch kept in code ***********/
 array('fn' => 'href_rajatar'),
 /******* RĀJATARAṄGIṆĪ, Calcutta ***********/
 array(array('RĀJA-TAR. ed. Calc.', 'RĀJAT. ed. Calc.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/rajatarcalc/app1?{2},{3}'))),
 /******* Ṛgveda Prātiśākhya (numeric patala,sutra) ***********/
 array(array('ṚV. PRĀT.', 'ṚV. PRĀTIŚ.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/rvps/app1?{2},{3}'))),
 /******* ṚV. PRĀT. Roman numeral — kept in code ***********/
 array('fn' => 'href_rvprat_roman'),
 /******* YĀJÑAVALKYA'S Gesetzbuch ***********/
 array(array('YĀJÑ.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/yajnavalkya/app1?{2},{3}'))),
 /******* Raghuvaṃśa ***********/
 array(array('RAGH.', 'RAGH. ed. ST.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/raghuvamsa/app1?{2},{3}'))),
 /******* Raghuvaṃśa (calcutta edition) ***********/
 array(array('RAGH. ed. Calc.', 'RAGH. \(ed. Calc.\)', 'RAGH. \(Calc.\)'),
  array(array('|^({A}) *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/raghuvamsacalc/app1?{2},{3}'))),
 /******* Mārkāṇḍeyapurāṇa ***********/
 array(array('MĀRK. P.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/markandeyapurana/app1?{2},{3}'))),
 /******* Bhagavadgītā ***********/
 array(array('BHAG.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/bhagavadgita/app1?{2},{3}'))),
 /******* Anekārthasaṃgraha of Hemacandra ***********/
 array(array('H. an.', 'an.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/anekarthasamgraha/app1?{2},{3}'))),
 /******* ŚĀKUNTALA (Bohtlingk edition) ***********/
 array(array('ŚĀK.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/shakuntala/app2?{2},{3}'),
   array('|^({A}) *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/shakuntala/app1?{2}'))),
 /******* AITAREYABRĀHMAṆA ***********/
 array(array('AIT. BR.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/aitbr_auf/app1?{2},{3},{4}'),
   array('|^({A}) *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/aitbr/app1?{2},{3}'))),
 /******* malavikagni ***********/
 array(array('MĀLAV.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/malavikagni/app2?{2},{3}'),
   array('|^({A}) *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/malavikagni/app1?{2}'))),
 /******* PAÑCATANTRA, Kosegarten, 1849 ***********/
 array(array('PAÑCAT.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/pantankose/app2?{2},{3}'),
   array('|^({A}) ([VI]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/pantankose/app1?{2:roman},{3}'),
   array('|^({A}) *(Pr\.) *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/pantankose/app1?0,{3}'))),
 /******* PAÑCATANTRA, Kosegarten ed. orn., 1859 ***********/
 array(array('PAÑCAT. ed. orn.', 'ed. orn.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/pantankoseorn/app1?{2},{3}'),
   array('|^({A}) ([VI]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/pantankoseorn/app2?{2:roman},{3}'))),
 /******* Hitopadeśa, ed. Schlegel und Lassen, 1829 ***********/
 array(array('HIT.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/hitopadesha/app2?{2},{3}'),
   array('|^({A}) ([IV]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/hitopadesha/app1?{2:roman},{3}'),
   array('|^({A}) (Pr\.) *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/hitopadesha/app1?0,{3}'))),
 /******* amarakosha, deslongchamp, 1839 ***********/
 array(array('AK.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+), *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/amara_dlc/app1?{2},{3},{4},{5}'),
   array('|^({A}) *([0-9]+), *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/amara_dlc/app1?{2},{3},{4}'))),
 /******* amarakosha, Colebrooke 1808 ***********/
 array(array('COL.', 'COLEBR.', 'AK. ed. COLEBR.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+), *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/amara_col/app1?{2},{3},{4},{5}'),
   array('|^({A}) *([0-9]+), *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/amara_col/app1?{2},{3},{4}'))),
 /******* gitagovinda ***********/
 array(array('GĪT.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/gitagov/app1?{2},{3}'))),
 /******* nirukta ***********/
 array(array('NIR.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/nirukta/app1?{2},{3}'),
   array('|^({A}) *([IVXL]+)|',
   'https://sanskrit-lexicon-scans.github.io/nirukta/app0?{2:lower}'))),
 /******* nighantuka ***********/
 array(array('NAIGH.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/nirukta/app2?{2},{3}'))),
 /******* Mugdhabodha of Vopadeva ***********/
 array(array('VOP.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/mugdhabodha/app1?{2},{3}'))),
 /******* bhattikavya ***********/
 array(array('BHAṬṬ.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/bhattikavya/app1?{2},{3}'))),
 /******* kumara sambhava ***********/
 array(array('KUMĀRAS.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/kumaras/app1?{2},{3}'))),
 /******* shatapathabr ***********/
 array(array('ŚAT. BR.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+), *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/shatapathabr/app1?{2},{3},{4},{5}'))),
 /******* taittiriyas ***********/
 array(array('TS.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+), *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/taittiriyas/app1?{2},{3},{4},{5}'))),
 /******* taittiriyabr ***********/
 array(array('TBR.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+), *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/taittiriyabr/app1?{2},{3},{4},{5}'))),
 /******* katyasr ***********/
 array(array('KĀTY. ŚR.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/katyasr/app1?{2},{3},{4}'),
   array('|^({A}) *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/katyasr/app2?{2},{3}'))),
 /******* pancaratra ***********/
 array(array('PAÑCAR.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/pancar/app1?{2},{3},{4}'),
   array('|^({A}) +S\. +([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/pancar/app0?{2}'))),
 /******* vikramor ***********/
 array(array('VIKR.', 'VIKRAM.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/vikramor/app2?{2},{3}'),
   array('|^({A}) +([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/vikramor/app1?{2}'))),
 /******* Nalopakhyana in bchrest1 ***********/
 array(array('N.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/bchrest1/app1?{2},{3}'))),
 /******* Dasharatha's death in bchrest1 ***********/
 array(array('DAŚ.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/bchrest1/app2?{2},{3}'))),
 /******* story of vidushaka in bchrest1 ***********/
 array(array('VID.'),
  array(array('|^({A}) *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/bchrest1/app3?{2}'))),
 /******* CAURAPAÑCĀŚIKĀ ***********/
 array(array('CAURAP.'),
  array(array('|^({A}) *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/bhartrhari/app1?{2}'))),
 /******* Vishvamitra's battle in bchrest1 ***********/
 array(array('VIŚV.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/bchrest1/app4?{2},{3}'))),
 /******* Bhartṛhariśataka ***********/
 array(array('BHARTṚ.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/bhartrhari/app2?{2},{3}'))),
 /******* Bhartṛhariśataka Suppl. ***********/
 array(array('BHARTṚ. Suppl.'),
  array(array('|^({A}) *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/bhartrhari/app3?{2}'))),
 /******* meghaduta ***********/
 array(array('MEGH.'),
  array(array('|^({A}) *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/meghasrnga/app1?{2}'))),
 /******* srngaratilaka ***********/
 array(array('ŚṚṄGĀRAT.'),
  array(array('|^({A}) *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/meghasrnga/app2?{2}'))),
 /******* Medinikosha — letter-class + bare-MED cases kept in code ***********/
 array('fn' => 'href_med'),
 /******* Trikandashesha of Purushottamadeva ***********/
 array(array('TRIK.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/medini/app2?{2},{3},{4}'))),
 /******* Haravali of Purushottamadeva ***********/
 array(array('HĀR.'),
  array(array('|^({A}) *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/medini/app3?{2}'))),
 /******* Abhidhānacintāmaṇipariśiṣṭa of Hemacandra ***********/
 array(array('H. ś.', 'ś.'),
  array(array('|^({A}) *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/abch2/app2?{2}'))),
 /******* Abhidhānacintāmaṇi Hemacandra ***********/
 array(array('H.'),
  array(array('|^({A}) *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/abch2/app1?{2}'))),
 /******* Abhidhānaratnamālā of Halāyudha ***********/
 array(array('HALĀY.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/armh2/app1?{2},{3}'))),
 /******* Manava DharmaSastra ***********/
 array(array('M.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/manu/index.html?{2},{3}'))),
 /******* VARĀHAMIHIRA'S BṚHATSAM̃HITĀ ***********/
 array(array('VARĀH. BṚH. S.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/brihatsam/app1?{2},{3}'))),
 /******* sāhityadarpaṇa ***********/
 array(array('SĀH. D.'),
  array(array('|^({A}) *([0-9]+), *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/sahityadarpana/app1?{2},{3}'),
   array('|^({A}) *([0-9]+)|',
   'https://sanskrit-lexicon-scans.github.io/sahityadarpana/app1?{2}'))),
 /******* B. Chrestomathie — pw-only gate kept in code ***********/
 array('fn' => 'href_chr'),
 /******* Westergaard Dhatupatha — section guard kept in code ***********/
 array('fn' => 'href_dhatup'),
 /******* Bhagavata Purana — skandha-dependent edition kept in code ***********/
 array('fn' => 'href_bhagp'),
 /******* Bhagavata Purana, Bombay ed. ***********/
 array(array('BHĀG\. P\. ed\. Bomb\.'),
  array(array('|^{A} *([0-9]+)[ ,]+([0-9]+)[ ,]+([0-9]+)(.*)$|',
   'https://sanskrit-lexicon-scans.github.io/bhagp_bom/app1/?{1},{2},{3}'))),
 /******* Rāmāyaṇa, Bombay ed. ***********/
 array(array('R\. ed\. Bomb.'),
  array(array('|^{A} *([0-9])[ ,]+([0-9]+)[ ,]+([0-9]+)[ ,]+([0-9]+)(.*)$|i',
   'https://sanskrit-lexicon-scans.github.io/ramayanabom/app1/?{1},{2},{3},{4}'),
   array('|^{A} *([0-9])[ ,]+([0-9]+)[ ,]+([0-9]+)(.*)$|i',
   'https://sanskrit-lexicon-scans.github.io/ramayanabom/app1/?{1},{2},{3}'))),
 /******* Rāmāyaṇa 7th kanda ***********/
 array(array('R\.'),
  array(array('|^{A} *(7)[ ,]+([0-9]+)[ ,]+([0-9]+)[ ,]+([0-9]+)(.*)$|i',
   'https://sanskrit-lexicon-scans.github.io/ramayanabom/app1/?{1},{2},{3},{4}'),
   array('|^{A} *(7)[ ,]+([0-9]+)[ ,]+([0-9]+)(.*)$|i',
   'https://sanskrit-lexicon-scans.github.io/ramayanabom/app1/?{1},{2},{3}'))),
 /******* Ṛgveda, Atharvaveda, Panini, Rāmāyaṇa Gorr./Schl. — computed
  * hrefs kept in code (hymn-file sprintf, rschl->rgorr switch) ***********/
 array('fn' => 'href_vedic'),
);
