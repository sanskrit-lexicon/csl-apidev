<?php
require_once(__DIR__ . '/../security_headers.php');
// Exclude WARNING messages also, to solve Peter Scharf Mac version.
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
/* app/entry.php -- server-rendered stacked headword permalink (H227 P0).

   The crawlable counterpart to app/index.php: where index.php fetches
   entries client-side (a bot with JS off sees an empty shell), this page
   renders the COMPLETE stacked entry -- every dictionary that has the
   headword, full entry text, attribution, scan link -- in the initial
   PHP response. JS (entry.js) is progressive enhancement only.

   URL contract:
     entry.php?key=<word>[&input=<scheme>][&dict=<code>][&fixtures=1]
     clean form (app/.htaccess):  entry/<key>  and  entry/<dict>/<key>
   Canonical is ALWAYS the stacked query form entry.php?key=<slp1> --
   one canonical per headword (the H227 thin-content gate), so the
   single-dict view and every input-scheme spelling variant canonicalize
   to it.

   Server-side reuse (no new data paths): dictionary resolution =
   Dalglob (dalglobClass.php, keydoc_glob1), entry HTML = GetwordClass
   (getwordClass.php) with dispopt=3 (bare fragment, no <html> wrapper),
   exactly the objects behind dalglob.php / getword.php. Both read
   $_REQUEST, so we set it per call the same way getword_batch.php does.

   ?fixtures=1 serves both from app/fixtures/fixtures.json (same keys as
   app.js uses) for offline development while the Cologne host is down;
   fixture pages are always noindex.

   Reflected-parameter safety: every echoed value goes through h()
   (htmlspecialchars ENT_QUOTES); JSON-LD is built as a PHP array and
   emitted with json_encode (default flags escape '/' so '</script>'
   cannot terminate the block), per the index.php pattern + the
   SEO playbook. */

chdir(__DIR__ . '/..');            // repo root: class includes + ../<dict>/web/sqlite +
                                   // ../hwnorm2 paths resolve as for the root endpoints
require_once('utilities/transcoder.php');
require_once('app/dictmeta.php');  // $APP_DICTMETA: code => [title, cdslYear, olderYear]

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function get_str($name) {
 return (isset($_GET[$name]) && is_string($_GET[$name])) ? trim($_GET[$name]) : '';
}

/* ---- input handling ------------------------------------------------- */

$SCHEMES = array('slp1','iast','hk','deva','velthuis','itrans','roman');
$fixtures = (get_str('fixtures') === '1');

$keyin = get_str('key');
// Same invalid-character strip as Parm::init_inputs_key().
$keyin = str_replace(array('$','#','<','>','=','(',')','"',"'",'\\'), '', $keyin);
$input = strtolower(get_str('input'));
if (!in_array($input, $SCHEMES)) {
 // auto-detect: Devanagari / IAST diacritics / plain ASCII-as-SLP1
 if (preg_match('/[\x{0900}-\x{097F}]/u', $keyin)) { $input = 'deva'; }
 else if (preg_match('/[āīūṛṝḷḹṃṅṇṣṭḍśĀĪŪṚṜḶḸṂṄṆṢṬḌŚ]/u', $keyin)) { $input = 'iast'; }
 else { $input = 'slp1'; }
}
$dictparam = strtolower(get_str('dict'));
if ($dictparam !== '' && !isset($APP_DICTMETA[$dictparam])) { $dictparam = ''; }

$slp1 = ($keyin === '') ? '' : transcoder_processString($keyin, $input, 'slp1');
// SLP1 keys are pure ASCII; anything else survived transcoding un-normalized.
if (!preg_match('/^[a-zA-Z]+[0-9]*$/', $slp1)) { $slp1 = ''; }

$iast = $slp1 === '' ? '' : transcoder_processString($slp1, 'slp1', 'roman');
$deva = $slp1 === '' ? '' : transcoder_processString($slp1, 'slp1', 'deva');

/* ---- base + canonical URLs ------------------------------------------ */

// Set to a fixed absolute base (no trailing slash) at deploy time if the
// host/path detection below is ever wrong, e.g.
// define('ENTRY_CANONICAL_BASE','https://www.sanskrit-lexicon.uni-koeln.de/csl-apidev/app');
function entry_base_url() {
 if (defined('ENTRY_CANONICAL_BASE')) { return ENTRY_CANONICAL_BASE; }
 $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
 $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'www.sanskrit-lexicon.uni-koeln.de';
 if (!preg_match('/^[A-Za-z0-9.\-]+(:[0-9]+)?$/', $host)) {
  $host = 'www.sanskrit-lexicon.uni-koeln.de'; // Host-header injection guard
 }
 $dir = str_replace('\\', '/', dirname(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/app/entry.php'));
 return ($https ? 'https' : 'http') . '://' . $host . rtrim($dir, '/');
}
$base = entry_base_url();
$canonical = $base . '/entry.php?key=' . rawurlencode($slp1);
$site = preg_replace('|(://[^/]+).*$|', '$1', $base); // scheme://host

/* ---- resolve dictionaries (Dalglob / fixtures) ----------------------- */

function entry_fixtures() {
 static $fx = null;
 if ($fx === null) {
  $raw = @file_get_contents(__DIR__ . '/fixtures/fixtures.json');
  $fx = $raw === false ? array() : json_decode($raw, true);
  if (!is_array($fx)) { $fx = array(); }
 }
 return $fx;
}

$dicts = array();   // [{dict, dockeys[]}]
if ($slp1 !== '') {
 if ($fixtures) {
  $fx = entry_fixtures();
  if (isset($fx['dalglob|' . $slp1]) && $fx['dalglob|' . $slp1]['status'] == 200) {
   $dicts = $fx['dalglob|' . $slp1]['dicts'];
  }
 } else {
  require_once('dalglobClass.php');
  $_REQUEST['dbglob'] = 'keydoc_glob1';
  $_REQUEST['key'] = $slp1;
  $_REQUEST['input'] = 'slp1';
  $_GET['key'] = $slp1;        // Parm reads $_REQUEST; keep both aligned
  $dal = new Dalglob();
  if ($dal->ans['status'] == 200) { $dicts = $dal->ans['dicts']; }
  $dal->close();
 }
}

// Canonical dictmeta order (same rule as app.js orderDicts).
global $APP_DICTMETA;
$DICT_ORDER = array_flip(array_keys($APP_DICTMETA));
usort($dicts, function ($a, $b) use ($DICT_ORDER) {
 $oa = isset($DICT_ORDER[$a['dict']]) ? $DICT_ORDER[$a['dict']] : 999;
 $ob = isset($DICT_ORDER[$b['dict']]) ? $DICT_ORDER[$b['dict']] : 999;
 return $oa - $ob;
});

$alldicts = $dicts;
if ($dictparam !== '') {
 $dicts = array_values(array_filter($dicts, function ($d) use ($dictparam) {
  return $d['dict'] === $dictparam;
 }));
}

/* ---- fetch entry HTML per dictionary (GetwordClass / fixtures) ------- */

function entry_fetch_block($dict, $dockey, $fixtures, $alldockeys = null) {
 if ($fixtures) {
  $fx = entry_fixtures();
  // The batch fixture is keyed by the FULL dockey list (how app.js
  // requests homonym sets), so probe that key as well as per-key forms.
  $batchall = 'batch|' . $dict . '|roman|no|' . implode(',', $alldockeys ? $alldockeys : array($dockey));
  foreach (array($batchall,
                 'batch|' . $dict . '|roman|no|' . $dockey,
                 'getword|' . $dict . '|roman|no|' . $dockey) as $k) {
   if (isset($fx[$k])) {
    $items = $fx[$k];
    if (isset($items['key'])) { $items = array($items); } // getword shape
    foreach ($items as $it) {
     if ($it['key'] === $dockey && $it['status'] == 200) { return $it['html']; }
    }
   }
  }
  return null;
 }
 require_once('getwordClass.php');
 $_REQUEST['dict'] = $dict;
 $_REQUEST['key'] = $dockey;
 $_REQUEST['input'] = 'slp1';
 $_REQUEST['output'] = 'roman';   // IAST body: the crawlable default (R5)
 $_REQUEST['accent'] = 'no';
 $_REQUEST['dispopt'] = '3';      // bare fragment: no <html> wrapper, no <h1>
 $_REQUEST['dispcss'] = 'no';
 $gw = new GetwordClass();
 return $gw->status ? $gw->table1 : null;
}

$blocks = array();  // [{dict, dockey, hom, html}]
if ($slp1 !== '') {
 foreach ($dicts as $rec) {
  $multi = count($rec['dockeys']) > 1;
  foreach ($rec['dockeys'] as $i => $dockey) {
   $html = entry_fetch_block($rec['dict'], $dockey, $fixtures, $rec['dockeys']);
   if ($html === null) { continue; }
   // Entry fragments link css/basic.css relative to the repo root; this
   // page sits in app/ (same correction app.js fixCssPath applies).
   $html = str_replace("href='css/basic.css'", "href='../css/basic.css'", $html);
   $blocks[] = array(
    'dict' => $rec['dict'],
    'dockey' => $dockey,
    'hom' => $multi ? ($i + 1) : 0,
    'html' => $html,
   );
  }
 }
}

/* ---- DCS corpus frequency (L4, from simple-search/wf1/wf.txt) -------- */

function entry_frequency($slp1) {
 $f = @fopen('simple-search/wf1/wf.txt', 'r');
 if (!$f) { return null; }
 $needle = $slp1 . ' ';
 $count = null;
 while (($line = fgets($f)) !== false) {
  if (strncmp($line, $needle, strlen($needle)) === 0) {
   $count = (int)substr($line, strlen($needle));
   break;
  }
 }
 fclose($f);
 return $count;
}
function entry_freq_band($count) {
 if ($count === null || $count <= 0) { return null; }
 if ($count >= 1000) { return 'very frequent'; }
 if ($count >= 100)  { return 'frequent'; }
 if ($count >= 10)   { return 'well attested'; }
 return 'rare';
}
$freq = ($slp1 !== '' && count($blocks)) ? entry_frequency($slp1) : null;
$freqband = entry_freq_band($freq);

/* ---- head metadata ---------------------------------------------------- */

$found = count($blocks) > 0 || count($alldicts) > 0;
$ndicts = count($alldicts);

if (!$found && $slp1 !== '') { http_response_code(404); }

if ($slp1 === '') {
 $title = 'Entry permalinks · Cologne Sanskrit Lexicon';
} else {
 // Headline dictionary: MW when present (the handoff's example form,
 // 'agni — Monier-Williams & 7 more'), else the first in canonical order;
 // shown by its author name (first word of the dictmeta title).
 $headdict = $ndicts ? $alldicts[0]['dict'] : '';
 foreach ($alldicts as $rec) { if ($rec['dict'] === 'mw') { $headdict = 'mw'; break; } }
 $headname = '';
 if ($headdict !== '') {
  $headtitle = isset($APP_DICTMETA[$headdict]) ? $APP_DICTMETA[$headdict][0] : strtoupper($headdict);
  $words = explode(' ', $headtitle);
  $headname = $words[0];
 }
 $more = $ndicts > 1 ? ' & ' . ($ndicts - 1) . ' more' : '';
 $title = $found
  ? ($iast . ' — ' . $headname . $more . ' · Cologne Sanskrit Lexicon')
  : ($iast . ' — not found · Cologne Sanskrit Lexicon');
}

// Meta description: first gloss = first rendered entry, tags stripped
// (drop the block's own dictitle heading; pad tags so words don't fuse).
$description = '';
if (count($blocks)) {
 $glosshtml = preg_replace('|<h2 class="dictitle">.*?</h2>|su', '', $blocks[0]['html']);
 $description = trim(preg_replace('/\s+/u', ' ', strip_tags(str_replace('<', ' <', $glosshtml))));
 if (function_exists('mb_substr') && mb_strlen($description, 'UTF-8') > 158) {
  $description = mb_substr($description, 0, 155, 'UTF-8') . '…';
 }
}
if ($description === '' && $found) {
 $description = $iast . ' in ' . $ndicts . ' Sanskrit ' .
  ($ndicts === 1 ? 'dictionary' : 'dictionaries') .
  ' of the Cologne Digital Sanskrit Dictionaries.';
}

// Thin-content gate: index only a real stacked page reached at its
// canonical address that actually rendered entry text. Fixture pages,
// single-dict views, non-canonical spellings, misses, and the
// "attested but no block rendered" degraded case (Dalglob matched but
// every GetwordClass render failed -- a page with no definition body)
// are all noindex,follow.
$is_canonical_view = (count($blocks) > 0 && $dictparam === '' && !$fixtures);
$robots = $is_canonical_view ? 'index,follow' : 'noindex,follow';

/* ---- JSON-LD (SEO playbook: @id spine; DefinedTerm/DefinedTermSet) --- */

$jsonld = null;
if ($found && $slp1 !== '') {
 $sets = array();
 $setrefs = array();
 foreach ($alldicts as $rec) {
  $code = $rec['dict'];
  $meta = isset($APP_DICTMETA[$code]) ? $APP_DICTMETA[$code] : array(strtoupper($code), '', null);
  $setid = $base . '/entry.php#dictset-' . $code;
  $sets[] = array(
   '@type' => 'DefinedTermSet',
   '@id' => $setid,
   'name' => $meta[0],
   'description' => 'Cologne Digital Sanskrit Dictionaries edition, version ' . $meta[1] . '.',
  );
  $setrefs[] = array('@id' => $setid);
 }
 $termid = $canonical . '#term';
 $jsonld = array(
  '@context' => 'https://schema.org',
  '@graph' => array(
   array(
    '@type' => 'Organization',
    '@id' => $site . '/#org',
    'name' => 'Cologne Digital Sanskrit Dictionaries, University of Cologne',
    'url' => $site . '/',
   ),
   array(
    '@type' => 'WebSite',
    '@id' => $site . '/#website',
    'name' => 'Cologne Sanskrit Lexicon',
    'url' => $site . '/',
    'inLanguage' => array('sa', 'en'),
    'publisher' => array('@id' => $site . '/#org'),
   ),
   array(
    '@type' => 'WebPage',
    '@id' => $canonical,
    'url' => $canonical,
    'name' => $title,
    'description' => $description,
    'isPartOf' => array('@id' => $site . '/#website'),
    'mainEntity' => array('@id' => $termid),
    'breadcrumb' => array('@id' => $canonical . '#breadcrumb'),
   ),
   array(
    '@type' => 'BreadcrumbList',
    '@id' => $canonical . '#breadcrumb',
    'itemListElement' => array(
     array('@type' => 'ListItem', 'position' => 1, 'name' => 'Cologne Sanskrit Lexicon', 'item' => $site . '/'),
     array('@type' => 'ListItem', 'position' => 2, 'name' => 'Dictionary search', 'item' => $base . '/index.php'),
     array('@type' => 'ListItem', 'position' => 3, 'name' => $iast),
    ),
   ),
   array_merge(array(
    '@type' => 'DefinedTerm',
    '@id' => $termid,
    'name' => $iast,
    'alternateName' => array($deva, $slp1),
    'identifier' => $slp1,   // stable SLP1 key: kosha's {dict}.{key} spine uses the same keys
    'inLanguage' => 'sa',
    'url' => $canonical,
   ), count($setrefs) ? array('inDefinedTermSet' => count($setrefs) === 1 ? $setrefs[0] : $setrefs) : array()),
  ),
 );
 foreach ($sets as $s) { $jsonld['@graph'][] = $s; }
}

/* ---- citation (L4: versioned Cite, kosha-compatible shape) ----------- */

$today = date('Y-m-d');
$citetext = '';
$bibtex = '';
if ($found && $slp1 !== '') {
 $vers = array();
 foreach ($alldicts as $rec) {
  $meta = isset($APP_DICTMETA[$rec['dict']]) ? $APP_DICTMETA[$rec['dict']] : null;
  $vers[] = strtoupper($rec['dict']) . ($meta ? '@' . $meta[1] : '');
 }
 $citetext = '“' . $iast . '”, Cologne Digital Sanskrit Dictionaries (' .
  implode(', ', $vers) . '). ' . $canonical . ' (accessed ' . $today . ').';
 $bibtex = "@misc{cdsl_" . $slp1 . ",\n" .
  "  title = {" . $iast . "},\n" .
  "  howpublished = {Cologne Digital Sanskrit Dictionaries, " . implode(', ', $vers) . "},\n" .
  "  url = {" . $canonical . "},\n" .
  "  note = {Accessed " . $today . "}\n" .
  "}";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo h($title); ?></title>
<?php if ($description !== '') { ?>
<meta name="description" content="<?php echo h($description); ?>">
<?php } ?>
<meta name="robots" content="<?php echo h($robots); ?>">
<link rel="canonical" href="<?php echo h($canonical); ?>">
<meta property="og:type" content="article">
<meta property="og:site_name" content="Cologne Sanskrit Lexicon">
<meta property="og:title" content="<?php echo h($title); ?>">
<?php if ($description !== '') { ?>
<meta property="og:description" content="<?php echo h($description); ?>">
<?php } ?>
<meta property="og:url" content="<?php echo h($canonical); ?>">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="<?php echo h($title); ?>">
<?php if ($description !== '') { ?>
<meta name="twitter:description" content="<?php echo h($description); ?>">
<?php } ?>
<link rel="icon" href="//www.sanskrit-lexicon.uni-koeln.de/favicon.ico">
<link rel="stylesheet" type="text/css" href="../css/basic.css">
<link rel="stylesheet" type="text/css" href="entry.css">
<?php if ($jsonld !== null) { ?>
<script type="application/ld+json">
<?php echo json_encode($jsonld, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); ?>
</script>
<?php } ?>
</head>
<body>

<header class="ep-topbar">
 <div class="ep-topbar-inner">
  <a class="ep-brand" href="//www.sanskrit-lexicon.uni-koeln.de/">
   <span class="ep-seal" aria-hidden="true">UzK</span>
   <span class="ep-brand-text">
    <span class="ep-brand-title">Cologne Sanskrit Lexicon</span>
    <span class="ep-brand-subtitle">Cologne Digital Sanskrit Dictionaries</span>
   </span>
  </a>
  <!-- L3: plain GET form; index.php auto-detects the input scheme, so no
       scheme picker here. Works as a link even before its JS runs. -->
  <form class="ep-search" action="index.php" method="get" role="search">
   <label class="ep-visually-hidden" for="ep-q">Search all dictionaries</label>
   <input type="text" id="ep-q" name="key" placeholder="Search all dictionaries…"
          autocapitalize="off" spellcheck="false">
   <?php if ($fixtures) { ?><input type="hidden" name="fixtures" value="1"><?php } ?>
   <button type="submit">Search</button>
  </form>
 </div>
</header>

<nav class="ep-breadcrumb" aria-label="Breadcrumb">
 <a href="//www.sanskrit-lexicon.uni-koeln.de/">Home</a> ›
 <a href="index.php">Dictionary search</a> ›
 <span aria-current="page" lang="sa"><?php echo h($iast !== '' ? $iast : 'entry'); ?></span>
</nav>

<main class="ep-main">
<?php if ($slp1 === '') { ?>
 <h1>Entry permalinks</h1>
 <p>This page serves one stable, citable URL per Sanskrit headword, with the
    entry text of every Cologne dictionary that contains it. Look a word up
    from the <a href="index.php">dictionary search</a>, or link directly:
    <a href="entry.php?key=agni"><code>entry.php?key=agni</code></a>.</p>
<?php } else if (!$found) { ?>
 <h1 lang="sa"><?php echo h($iast); ?></h1>
 <p class="ep-notfound">“<?php echo h($keyin); ?>” was not found as a headword in any
    of the Cologne dictionaries. Spelling variants often differ — try the
    <a href="index.php?key=<?php echo h(rawurlencode($keyin)); ?>">fuzzy search</a>.</p>
<?php } else { ?>
 <article class="ep-entry-page">
  <header class="ep-headword-head">
   <h1 class="ep-headword"><span lang="sa"><?php echo h($iast); ?></span>
    <span class="ep-headword-deva" lang="sa"><?php echo h($deva); ?></span></h1>
   <p class="ep-keyline">SLP1 key: <code><?php echo h($slp1); ?></code>
    · found in <?php echo $ndicts; ?> <?php echo $ndicts === 1 ? 'dictionary' : 'dictionaries'; ?>
<?php if ($freqband !== null) { ?>
    · <span class="ep-freqband" title="Token count <?php echo (int)$freq; ?> in the DCS corpus sample (Digital Corpus of Sanskrit, 2026 export)">corpus: <?php echo h($freqband); ?></span>
<?php } ?>
   </p>
<?php if ($dictparam !== '') { ?>
   <p class="ep-scopenote">Showing <?php echo h(strtoupper($dictparam)); ?> only —
      <a href="entry.php?key=<?php echo h(rawurlencode($slp1)); ?><?php echo $fixtures ? '&amp;fixtures=1' : ''; ?>">all dictionaries</a>.</p>
<?php } ?>
  </header>

  <!-- L1: the stacked multi-source view (Wisdom Library / meyer lesson):
       every dictionary's full entry, server-rendered, per-dictionary
       attribution. This markup IS the initial response - no client fetch. -->
<?php foreach ($blocks as $b) {
  $code = $b['dict'];
  $meta = isset($APP_DICTMETA[$code]) ? $APP_DICTMETA[$code] : array(strtoupper($code), '', null);
  $anchor = $code . ($b['hom'] ? '-' . $b['hom'] : '');
?>
  <section class="ep-dictblock" id="<?php echo h($anchor); ?>">
   <header class="ep-dictblock-head">
    <h2><?php echo h(strtoupper($code)); ?><?php if ($b['hom']) { ?><sup><?php echo (int)$b['hom']; ?></sup><?php } ?>
     <span class="ep-dicttitle"><?php echo h($meta[0]); ?></span></h2>
    <p class="ep-attribution">CDSL version <?php echo h($meta[1]); ?>
     · <a href="../servepdf.php?dict=<?php echo h(rawurlencode($code)); ?>&amp;key=<?php echo h(rawurlencode($b['dockey'])); ?>"
          rel="noopener" target="_blank">☞ scan</a>
     · <a href="#<?php echo h($anchor); ?>">¶ <?php echo h($code . '.' . $b['dockey']); ?></a></p>
   </header>
   <div class="ep-entry-body"><?php echo $b['html'];  /* trusted markup from GetwordClass / fixtures, not user input */ ?></div>
  </section>
<?php } ?>

<?php if (count($blocks) === 0) { ?>
  <p class="ep-notfound">The headword is attested in the dictionaries listed
     below, but their entry text could not be rendered
     <?php echo $fixtures ? '(offline fixtures cover only a subset)' : '(temporary server error)'; ?>.</p>
<?php } ?>

<?php if ($ndicts > count($blocks) && $dictparam === '') {
  $shown = array();
  foreach ($blocks as $b) { $shown[$b['dict']] = true; }
?>
  <p class="ep-alsoin">Also attested in:
<?php foreach ($alldicts as $rec) { if (isset($shown[$rec['dict']])) { continue; }
   $meta = isset($APP_DICTMETA[$rec['dict']]) ? $APP_DICTMETA[$rec['dict']] : array(strtoupper($rec['dict']), '', null); ?>
   <a href="entry.php?key=<?php echo h(rawurlencode($slp1)); ?>&amp;dict=<?php echo h($rec['dict']); ?><?php echo $fixtures ? '&amp;fixtures=1' : ''; ?>"
      title="<?php echo h($meta[0]); ?>"><?php echo h(strtoupper($rec['dict'])); ?></a>
<?php } ?>
  </p>
<?php } ?>

  <!-- L4: versioned Cite block (kosha citation shape: pinned versions,
       host-independent id {dict}.{key}, machine form in <details>). -->
  <section class="ep-cite">
   <h2>Cite this entry</h2>
   <p class="ep-citetext" id="ep-citetext"><?php echo h($citetext); ?></p>
   <details>
    <summary>BibTeX</summary>
    <pre id="ep-bibtex"><?php echo h($bibtex); ?></pre>
   </details>
  </section>
 </article>
<?php } ?>
</main>

<footer class="ep-footer">
 <p>Cologne Digital Sanskrit Dictionaries · University of Cologne ·
    <a href="//www.sanskrit-lexicon.uni-koeln.de/">sanskrit-lexicon.uni-koeln.de</a></p>
</footer>

<script src="entry.js" defer></script>
</body>
</html>
