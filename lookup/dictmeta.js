/* dictmeta.js -- static per-dictionary metadata for lookup/lookup.js
   (doc/roadmap_lookup.md Wave 2, item 1: "dictionary result cards").

   Deliberately a plain static table, not a new PHP endpoint: title/year
   are static data (roadmap Wave 2 offers this as the lighter option when a
   new endpoint "feels like overreach"). Sourced from two existing static
   tables so titles/years are prior art, not invented here:
    - title       <- sample/dictnames.js (CologneDisplays.dictionaries),
                     cross-checked against sample/basic04a/dictnames.js.
                     Three codes present in DictInfo::$dictyear but absent
                     from both dictnames.js copies were filled in:
                       AE     -- title from sample/basic04a/dictnames.js
                                 (present there, missing in sample/dictnames.js)
                       PD     -- dictnames.js has it but comments it out;
                                 uncommented here, title unchanged
                       PWKVN  -- no title found anywhere in the repo; every
                                 reference (basicadjust.php) always groups
                                 it with pw/pwg, so it's given a title that
                                 says so explicitly rather than guessing a
                                 real title
    - year/yearOlder <- dictinfo.php's DictInfo::$dictyear / $dictyear_older
                     (kept in sync manually; DictInfo has no JSON endpoint,
                     see roadmap Wave 2 item 1)

   Language (`lang`) is NOT hand-classified per dictionary -- that would be
   a scholarly claim this table can't verify. Instead lookup.js derives it
   mechanically from the title string at render time (see classifyLang() in
   lookup.js): "English" -> en, "Français" -> fr, "Wörterbuch" -> de,
   "Glossarium" -> la, anything else -> other. Treat the "other" bucket as
   genuinely unclassified, not mislabeled. */
(function () {
 'use strict';

 window.LOOKUP_DICTMETA = [
  ['ABCH', 'Abhidhānacintāmaṇi of Hemacandrācārya', '2023', null],
  ['ACC', 'Aufrecht Catalogus Catalogorum', '2020', '2014'],
  ['ACPH', 'Abhidhānacintāmaṇipariśiṣṭa of Hemacandrācārya', '2023', null],
  ['ACSJ', 'Abhidhānacintāmaṇiśiloñcha of Jinadeva', '2023', null],
  ['AE', 'Apte Student English-Sanskrit Dictionary', '2020', '2014'],
  ['AP', 'Apte Practical Sanskrit-English Dictionary, revised', '2020', '2014'],
  ['AP90', 'Apte Practical Sanskrit-English Dictionary', '2020', '2014'],
  ['ARMH', 'Abhidhānaratnamālā of Halāyudha', '2020', '2020'],
  ['BEN', 'Benfey Sanskrit-English Dictionary', '2020', '2014'],
  ['BHS', 'Edgerton Buddhist Hybrid Sanskrit Dictionary', '2020', '2014'],
  ['BOP', 'Bopp Glossarium Sanscritum', '2020', '2014'],
  ['BOR', 'Borooah English-Sanskrit Dictionary', '2020', '2014'],
  ['BUR', 'Burnouf Dictionnaire Sanscrit-Français', '2020', '2013'],
  ['CAE', 'Cappeller Sanskrit-English Dictionary', '2020', '2014'],
  ['CCS', 'Cappeller Sanskrit Wörterbuch', '2020', '2014'],
  ['FRI', 'Frisch Sanskrit Reader Vocabulary, 1956', '2025', null],
  ['GRA', 'Grassmann Wörterbuch zum Rig Veda', '2020', '2014'],
  ['GST', 'Goldstücker Sanskrit-English Dictionary', '2020', '2014'],
  ['IEG', 'Indian Epigraphical Glossary', '2020', '2014'],
  ['INM', 'Index to the Names in the Mahabharata', '2020', '2013'],
  ['KRM', 'Kṛdantarūpamālā', '2020', '2014'],
  ['LAN', 'Lanman Sanskrit Reader Dictionary', '2020', '2019'],
  ['LRV', 'Vaidya Standard Sanskrit-English Dictionary', '2022', '2022'],
  ['MCI', 'Mahabharata Cultural Index', '2020', '2014'],
  ['MD', 'Macdonell Sanskrit-English Dictionary', '2020', '2014'],
  ['MW', 'Monier-Williams Sanskrit-English Dictionary', '2020', '2014'],
  ['MW72', 'Monier-Williams Sanskrit-English Dictionary', '2020', '2014'],
  ['MWE', 'Monier-Williams English-Sanskrit Dictionary', '2020', '2013'],
  ['NMMB', 'Nāmamālikā of Bhoja', '2026', null],
  ['PD', 'An Encyclopedic Dictionary of Sanskrit on Historical Principles', '2020', '2014'],
  ['PE', 'Puranic Encyclopedia', '2020', '2014'],
  ['PGN', 'Personal and Geographical Names in the Gupta Inscriptions', '2020', '2014'],
  ['PUI', 'The Purana Index', '2020', '2014'],
  ['PW', 'Böhtlingk Sanskrit-Wörterbuch in kürzerer Fassung', '2020', '2014'],
  ['PWG', 'Böhtlingk and Roth Grosses Petersburger Wörterbuch', '2020', '2013'],
  ['PWKVN', 'Böhtlingk Sanskrit-Wörterbuch (PW/PWG cross-reference variant)', '2020', '2020'],
  ['SCH', 'Schmidt Nachträge zum Sanskrit-Wörterbuch', '2020', '2014'],
  ['SHS', 'Shabda-Sagara Sanskrit-English Dictionary', '2020', '2014'],
  ['SKD', 'Sabda-kalpadruma', '2020', '2013'],
  ['SNP', 'Meulenbeld Sanskrit Names of Plants', '2020', '2014'],
  ['STC', 'Stchoupak Dictionnaire Sanscrit-Français', '2020', '2013'],
  ['VCP', 'Vacaspatyam', '2020', '2019'],
  ['VEI', 'The Vedic Index of Names and Subjects', '2020', '2014'],
  ['WIL', 'Wilson Sanskrit-English Dictionary', '2020', '2014'],
  ['YAT', 'Yates Sanskrit-English Dictionary', '2020', '2014']
 ];
})();
