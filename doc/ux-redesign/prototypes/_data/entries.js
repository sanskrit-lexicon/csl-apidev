/* entries.js -- shared REAL dictionary content for the H225 design-direction
   prototypes (doc/ux-redesign/prototypes/).

   Every sense below is actual public-domain dictionary text:
     - Monier-Williams, A Sanskrit-English Dictionary (1899)
     - Apte, The Practical Sanskrit-English Dictionary (1890)
   extracted from the Cologne csl-orig source (v02/mw/mw.txt, v02/ap90/ap90.txt)
   and transliterated from the MW-internal scheme to IAST for display.
   Long entries are abridged (noted per entry); no sense is paraphrased.

   The prototype pages BAKE this content into static semantic HTML; this file
   exists as the single canonical copy so the four directions cannot drift.
   It is also loaded at runtime for the mock search interactions. */
(function () {
 'use strict';

 /* 45-dictionary metadata -- verbatim copy of lookup/dictmeta.js
    [code, title, versionYear, olderVersionYear] */
 window.PROTO_DICTS = [
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

 /* Same mechanical language classification as lookup/lookup.js */
 window.protoClassifyLang = function (title) {
  if (/english/i.test(title)) { return 'English'; }
  if (/fran[çc]ais|french/i.test(title)) { return 'French'; }
  if (/wörterbuch|woerterbuch/i.test(title)) { return 'German'; }
  if (/glossarium/i.test(title)) { return 'Latin'; }
  return 'Other';
 };

 window.PROTO_PROVENANCE =
  'Entry text is the actual public-domain dictionary text ' +
  '(Monier-Williams 1899 · Apte 1890), from the Cologne csl-orig source.';

 /* ---- the four real headwords ---- */
 window.PROTO_ENTRIES = [
  {
   id: 'agni', plain: 'agni', iast: 'agní', deva: 'अग्नि', slp1: 'agni', hk: 'agni',
   gloss: 'fire; the god of fire',
   dicts: [
    {
     code: 'MW', label: 'MW 1899', hw: 'agní', gram: 'm.',
     etym: '(√ <span lang="sa">ag</span>, <cite>Uṇ.</cite>)',
     pageCol: '5,1', lnum: '890',
     citation: 'Monier-Williams, A Sanskrit-English Dictionary (1899), s.v. agní, p. 5, col. 1. Cologne Digital Sanskrit Dictionaries.',
     scan: 'https://www.sanskrit-lexicon.uni-koeln.de/scans/MWScan/2020/web/webtc/servepdf.php?page=5,1',
     abridged: null,
     senses: [
      'fire, sacrificial fire (of three kinds, <span lang="sa">Gārhapatya</span>, <span lang="sa">Āhavanīya</span>, and <span lang="sa">Dakṣiṇa</span>)',
      'the number three, <cite>Sūryas.</cite>',
      'the god of fire, the fire of the stomach, digestive faculty, gastric fluid',
      'bile, <cite>L.</cite>',
      'gold, <cite>L.</cite>',
      '<abbr title="name">N.</abbr> of various plants — <em>Semecarpus Anacardium</em>, <cite>Suśr.</cite>; <em>Plumbago Zeylanica</em> and <em>Rosea</em>; <em>Citrus Acida</em>',
      'a mystical substitute for the letter <span lang="sa">r</span>',
      'in the <span lang="sa">Kātantra</span> grammar, <abbr title="name">N.</abbr> of noun-stems ending in <span lang="sa">i</span> and <span lang="sa">u</span>'
     ]
    },
    {
     code: 'AP90', label: 'AP90 1890', hw: 'agniḥ', gram: 'm.',
     etym: '[<cite>Uṇ. 4.50</cite>]',
     pageCol: null, lnum: null,
     citation: 'Apte, The Practical Sanskrit-English Dictionary (1890), s.v. agniḥ. Cologne Digital Sanskrit Dictionaries.',
     scan: null,
     abridged: 'English gloss senses shown; the inline Sanskrit usage examples of the print entry are omitted in this preview.',
     senses: [
      'Fire',
      'the God of fire',
      'sacrificial fire of three kinds (<span lang="sa">Gārhapatya</span>, <span lang="sa">Āhavanīya</span> and <span lang="sa">Dakṣiṇa</span>)',
      'the fire of the stomach, digestive faculty, gastric fluid',
      'bile',
      'cauterization',
      'gold',
      'the number three',
      '<abbr title="name">N.</abbr> of various plants (<em>Plumbago Zeylanica</em>; <em>Semecarpus Anacardium</em>; <em>Citrus Acida</em>)',
      'a mystical substitute for the letter <span lang="sa">r</span>'
     ]
    }
   ]
  },
  {
   id: 'dharma', plain: 'dharma', iast: 'dhárma', deva: 'धर्म', slp1: 'Darma', hk: 'dharma',
   gloss: 'law, duty, virtue, nature',
   dicts: [
    {
     code: 'MW', label: 'MW 1899', hw: 'dhárma', gram: 'm.',
     etym: '(rarely <em>n.</em> <abbr title="gaṇa">g.</abbr> <span lang="sa">ardharcādi</span>; the older form of the <cite>RV.</cite> is <span lang="sa">dhárman</span>, <abbr title="quod vide">q.v.</abbr>)',
     pageCol: '510,3', lnum: '99903',
     citation: 'Monier-Williams, A Sanskrit-English Dictionary (1899), s.v. dhárma, p. 510, col. 3. Cologne Digital Sanskrit Dictionaries.',
     scan: 'https://www.sanskrit-lexicon.uni-koeln.de/scans/MWScan/2020/web/webtc/servepdf.php?page=510,3',
     abridged: 'Senses 1–19 of the entry; the remaining senses (proper names) are omitted in this static preview.',
     senses: [
      'that which is established or firm, steadfast decree, statute, ordinance, law',
      'usage, practice, customary observance or prescribed conduct, duty',
      'right, justice (often as a synonym of punishment)',
      'virtue, morality, religion, religious merit, good works (<span lang="sa">dhármeṇa</span> <em>ind.</em> or <span lang="sa">°māt</span> <em>ind.</em> according to right or rule, rightly, justly, according to the nature of anything; <span lang="sa">°me sthita</span> <em>mfn.</em> holding to the law, doing one’s duty), <cite>AV.</cite> &amp;c. &amp;c.',
      'Law or Justice personified (as <span lang="sa">Indra</span>, <cite>ŚBr.</cite> &amp;c.; as <span lang="sa">Yama</span>, <cite>MBh.</cite>; as born from the right breast of <span lang="sa">Yama</span> and father of <span lang="sa">Śama</span>, <span lang="sa">Kāma</span> and <span lang="sa">Harṣa</span>, <cite>ib.</cite>; as <span lang="sa">Viṣṇu</span>, <cite>Hariv.</cite>; as <span lang="sa">Prajā-pati</span> and son-in-law of <span lang="sa">Dakṣa</span>, <cite>Hariv.</cite>; <cite>Mn.</cite> &amp;c.; as one of the attendants of the Sun, <cite>L.</cite>; as a Bull, <cite>Mn. viii, 16</cite>; as a Dove, <cite>Kathās. vii, 89</cite>, &amp;c.)',
      'the law or doctrine of Buddhism (as distinguished from the <span lang="sa">saṅgha</span> or monastic order, <cite>MWB. 70</cite>)',
      'the ethical precepts of Buddhism (or the principal <span lang="sa">dharma</span> called <span lang="sa">sūtra</span>, as distinguished from the <span lang="sa">abhi-dharma</span> or ‘further <span lang="sa">dharma</span>’ and from the <span lang="sa">vinaya</span> or ‘discipline’, these three constituting the canon of Southern Buddhism, <cite>MWB. 61</cite>)',
      'the law of Northern Buddhism (in 9 canonical scriptures, <abbr title="videlicet">viz.</abbr> <span lang="sa">Prajñā-pāramitā</span>, <span lang="sa">Gaṇḍa-vyūha</span>, <span lang="sa">Daśa-bhūmīśvara</span>, <span lang="sa">Samādhirāja</span>, <span lang="sa">Laṅkāvatāra</span>, <span lang="sa">Saddharma-puṇḍarīka</span>, <span lang="sa">Tathāgata-guhyaka</span>, <span lang="sa">Lalita-vistara</span>, <span lang="sa">Suvarṇa-prabhāsa</span>, <cite>MWB. 69</cite>)',
      'nature, character, peculiar condition or essential quality, property, mark, peculiarity (= <span lang="sa">sva-bhāva</span>, <cite>L.</cite>; <abbr title="confer">cf.</abbr> <span lang="sa">daśa-dharma-gata</span>, <cite>ŚBr.</cite> &amp;c. &amp;c.; <span lang="sa">upamānopameyayor dharma</span>, the tertium comparationis, <cite>Pāṇ. ii, 1, 55</cite>, <abbr title="Scholiast">Sch.</abbr>)',
      'a particular ceremony, <cite>MBh. xiv, 2623</cite>',
      'sacrifice, <cite>L.</cite>',
      'the ninth mansion, <cite>Var.</cite>',
      'an <span lang="sa">Upaniṣad</span>, <cite>L.</cite>',
      'associating with the virtuous, <cite>L.</cite>',
      'religious abstraction, devotion, <cite>L.</cite>',
      '= <span lang="sa">upamā</span>, <cite>L.</cite> (<abbr title="confer">cf.</abbr> above)',
      'a bow, <cite>Dharmaś.</cite>',
      'a <span lang="sa">Soma</span>-drinker, <cite>L.</cite>',
      '<abbr title="name">N.</abbr> of the 15th <span lang="sa">Arhat</span> of the present <span lang="sa">Ava-sarpiṇī</span>, <cite>L.</cite>'
     ]
    }
   ]
  },
  {
   id: 'manas', plain: 'manas', iast: 'mánas', deva: 'मनस्', slp1: 'manas', hk: 'manas',
   gloss: 'mind, intellect, spirit',
   dicts: [
    {
     code: 'MW', label: 'MW 1899', hw: 'mánas', gram: 'n.',
     etym: '',
     pageCol: '783,3', lnum: '156776',
     citation: 'Monier-Williams, A Sanskrit-English Dictionary (1899), s.v. mánas, p. 783, col. 3. Cologne Digital Sanskrit Dictionaries.',
     scan: 'https://www.sanskrit-lexicon.uni-koeln.de/scans/MWScan/2020/web/webtc/servepdf.php?page=783,3',
     abridged: 'The long list of verbal idioms under sense 3 is abridged in this static preview (full entry MW p. 783, col. 3 – p. 784, col. 1).',
     senses: [
      'mind (in its widest sense as applied to all the mental powers), intellect, intelligence, understanding, perception, sense, conscience, will, <cite>RV.</cite> &amp;c. &amp;c. (in <abbr title="philosophy">phil.</abbr> the internal organ or <span lang="sa">antaḥ-karaṇa</span> of perception and cognition, the faculty or instrument through which thoughts enter or by which objects of sense affect the soul, <cite>IW. 53</cite>; in this sense <span lang="sa">manas</span> is always regarded as distinct from <span lang="sa">ātman</span> and <span lang="sa">puruṣa</span>, ‘spirit or soul’, and belonging only to the body, like which it is — except in the <span lang="sa">Nyāya</span> — considered perishable; in <cite>RV.</cite> it is sometimes joined with <span lang="sa">hṛd</span> or <span lang="sa">hṛdaya</span>, the heart, <cite>Mn. vii, 6</cite> with <span lang="sa">cakṣus</span>, the eye)',
      'the spirit or spiritual principle, the breath or living soul which escapes from the body at death (called <span lang="sa">asu</span> in animals; <abbr title="confer">cf.</abbr> above), <cite>ib.</cite>',
      'thought, imagination, excogitation, invention, reflection, opinion, intention, inclination, affection, desire, mood, temper, spirit, <cite>ib.</cite> (<span lang="sa">manaḥ √ kṛ</span>, to make up one’s mind; <span lang="sa">mánasā</span> <em>ind.</em> in the mind; in thought or imagination; with all the heart, willingly; <span lang="sa">manasi</span> with <span lang="sa">√ kṛ</span>, to bear or ponder in the mind, meditate on, remember; with <span lang="sa">ni-√ dhā</span>, to impress on the mind, consider; with <span lang="sa">√ vṛt</span>, to be passing in one’s mind)',
      '<abbr title="name">N.</abbr> of the 26th <span lang="sa">Kalpa</span> (<abbr title="sub voce">s.v.</abbr>), <cite>Cat.</cite>',
      'of the lake <span lang="sa">Mānasa</span>, <cite>BhP.</cite>',
      '<span lang="sa">manaso dohaḥ</span> <abbr title="name">N.</abbr> of a <span lang="sa">Sāman</span>, <cite>ĀrṣBr.</cite>'
     ],
     tail: '[<abbr title="confer">cf.</abbr> Gk. μένος; Lat. <em>Miner-va</em>.]'
    }
   ]
  },
  {
   id: 'indra', plain: 'indra', iast: 'índra', deva: 'इन्द्र', slp1: 'indra', hk: 'indra',
   gloss: 'the god of the atmosphere and sky',
   dicts: [
    {
     code: 'MW', label: 'MW 1899', hw: 'índra', gram: 'm.',
     etym: '(according to <cite>BRD.</cite> <abbr title="from">fr.</abbr> <span lang="sa">in</span> = √ <span lang="sa">inv</span> with <abbr title="suffix">suff.</abbr> <span lang="sa">ra</span> preceded by inserted <span lang="sa">d</span>, meaning ‘to subdue, conquer’; more probably from √ <span lang="sa">ind</span>, ‘to drop’, <abbr title="quod vide">q.v.</abbr>, and connected with <span lang="sa">indu</span>)',
     pageCol: '166,1', lnum: '28925',
     citation: 'Monier-Williams, A Sanskrit-English Dictionary (1899), s.v. índra, p. 166, col. 1. Cologne Digital Sanskrit Dictionaries.',
     scan: 'https://www.sanskrit-lexicon.uni-koeln.de/scans/MWScan/2020/web/webtc/servepdf.php?page=166,1',
     abridged: 'The etymological discussion of the print head is condensed; senses 1–17 shown, compounds omitted.',
     senses: [
      'the god of the atmosphere and sky',
      'the Indian Jupiter Pluvius or lord of rain (who in Vedic mythology reigns over the deities of the intermediate region or atmosphere; he fights against and conquers with his thunder-bolt [<span lang="sa">vajra</span>] the demons of darkness, and is in general a symbol of generous heroism; <span lang="sa">indra</span> was not originally lord of the gods of the sky, but his deeds were most useful to mankind, and he was therefore addressed in prayers and hymns more than any other deity, and ultimately superseded the more lofty and spiritual <span lang="sa">Varuṇa</span>; in the later mythology <span lang="sa">indra</span> is subordinated to the triad <span lang="sa">Brahman</span>, <span lang="sa">Viṣṇu</span>, and <span lang="sa">Śiva</span>, but remained the chief of all other deities in the popular mind), <cite>RV.</cite>; <cite>AV.</cite>; <cite>ŚBr.</cite>; <cite>Mn.</cite>; <cite>MBh.</cite>; <cite>R.</cite> &amp;c. &amp;c.',
      '(he is also regent of the east quarter, and considered one of the twelve <span lang="sa">Āditya</span>s), <cite>Mn.</cite>; <cite>R.</cite>; <cite>Suśr.</cite> &amp;c.',
      'in the <span lang="sa">Vedānta</span> he is identified with the supreme being',
      'a prince',
      '<abbr title="in fine compositi">ifc.</abbr> best, excellent, the first, the chief (of any class of objects; <abbr title="confer">cf.</abbr> <span lang="sa">surendra</span>, <span lang="sa">rājendra</span>, <span lang="sa">parvatendra</span>, &amp;c.), <cite>Mn.</cite>; <cite>Hit.</cite>',
      'the pupil of the right eye (that of the left being called <span lang="sa">Indrāṇī</span> or <span lang="sa">Indra</span>’s wife), <cite>ŚBr.</cite>; <cite>BṛĀrUp.</cite>',
      'the number fourteen, <cite>Sūryas.</cite>',
      '<abbr title="name">N.</abbr> of a grammarian',
      'of a physician',
      'the plant <em>Wrightia Antidysenterica</em> (see <span lang="sa">kuṭaja</span>), <cite>L.</cite>',
      'a vegetable poison, <cite>L.</cite>',
      'the twenty-sixth <span lang="sa">Yoga</span> or division of a circle on the plane of the ecliptic',
      'the <span lang="sa">Yoga</span> star in the twenty-sixth <span lang="sa">Nakṣatra</span>, γ Pegasi',
      'the human soul, the portion of spirit residing in the body',
      'night, <cite>L.</cite>',
      'one of the nine divisions of <span lang="sa">Jambu-dvīpa</span> or the known continent, <cite>L.</cite>'
     ]
    }
   ]
  }
 ];
})();
