<?php
// Exclude WARNING messages also, to solve Peter Scharf Mac version.
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cologne Sanskrit Lexicon &mdash; Search</title>
<link rel="stylesheet" type="text/css" href="../css/basic.css">
<link rel="stylesheet" type="text/css" href="app.css">
<script src="theme.js"></script>
<script>window.THEME && THEME.applyEarly();</script>
</head>
<body>
<noscript>This page needs JavaScript to search the dictionaries.</noscript>

<header class="ap-topbar">
 <div class="ap-topbar-inner">
  <a class="ap-brand" href="home.php">
   <span class="ap-seal" aria-hidden="true">UzK</span>
   <span class="ap-brand-text">
    <span class="ap-brand-title">Cologne Sanskrit Lexicon</span>
    <span class="ap-brand-subtitle">Cologne Digital Sanskrit Dictionaries</span>
   </span>
  </a>
  <div class="ap-topbar-controls">
   <nav class="ap-topbar-nav" aria-label="Primary">
    <a class="ap-navlink" href="home.php">Home</a>
    <a class="ap-navlink" href="index.php" aria-current="page">Search</a>
   </nav>
   <div class="ap-field" id="ap-scheme-field">
    <label for="ap-scheme">input</label>
    <select id="ap-scheme">
     <option value="default" selected="selected">Default (forgiving)</option>
     <option value="iast">IAST</option>
     <option value="hk">HK</option>
     <option value="deva">Devanagari</option>
     <option value="slp1">SLP1</option>
     <option value="velthuis">Velthuis</option>
     <option value="itrans">ITRANS</option>
    </select>
   </div>
   <span id="ap-scheme-auto" class="ap-badge" hidden></span>
   <div class="ap-toggle" role="group" aria-label="Display script">
    <button type="button" id="ap-display-roman" aria-pressed="true">IAST</button>
    <button type="button" id="ap-display-deva" aria-pressed="false" lang="sa">&#2342;&#2375;&#2357;</button>
   </div>
   <button type="button" id="ap-theme" class="ap-theme" aria-label="Toggle theme">&#9789;</button>
  </div>
 </div>
</header>

<section class="ap-searchbar">
 <div class="ap-searchbar-inner">
  <form id="ap-form" class="ap-search-shell" autocomplete="off" role="search">
   <div class="ap-search-row">
    <div class="ap-field ap-field-key">
     <label for="ap-key">citation or word</label>
     <input type="text" id="ap-key" name="key" list="ap-suggestions"
            autocapitalize="off" spellcheck="false"
            aria-describedby="ap-status">
     <datalist id="ap-suggestions"></datalist>
    </div>
    <button type="submit" id="ap-submit" class="ap-search-button">Search</button>
    <button type="button" id="ap-copylink" class="ap-copylink" hidden>Copy link</button>
   </div>
   <div class="ap-mode-strip" role="group" aria-label="Search mode">
    <button type="button" class="ap-mode-tab" data-mode="fuzzy" aria-pressed="true">Fuzzy</button>
    <button type="button" class="ap-mode-tab" data-mode="exact" aria-pressed="false">Exact</button>
    <button type="button" class="ap-mode-tab" data-mode="prefix" aria-pressed="false">Prefix</button>
    <button type="button" class="ap-mode-tab" data-mode="suffix" aria-pressed="false"
            disabled title="Suffix search is coming in slice 2">Suffix</button>
    <button type="button" id="ap-advanced-toggle" class="ap-mode-tab ap-advanced-toggle"
            aria-expanded="false" aria-controls="ap-advanced">Advanced</button>
   </div>
   <div id="ap-advanced" class="ap-advanced" hidden>
    <div class="ap-field">
     <label for="ap-accent">accents</label>
     <select id="ap-accent">
      <option value="no" selected="selected">plain</option>
      <option value="yes">with accents</option>
     </select>
    </div>
    <div class="ap-field ap-field-dictfilter">
     <label>dictionaries</label>
     <div id="ap-dictfilter" class="ap-dictfilter" role="group"
          aria-label="Filter dictionaries"></div>
    </div>
   </div>
  </form>
 </div>
</section>

<p id="ap-status" class="ap-status" role="status" aria-live="polite"></p>

<main class="ap-main">
 <div id="ap-empty" class="ap-empty">
  <p>Search all Cologne dictionaries at once. Type in any scheme &mdash;
     Devanagari and IAST are detected automatically. Try:</p>
  <div class="ap-examples">
   <button type="button" class="ap-example" lang="sa">&#2309;&#2327;&#2381;&#2344;&#2367;</button>
   <button type="button" class="ap-example">m&#257;na</button>
   <button type="button" class="ap-example">manas</button>
  </div>
 </div>
 <div id="ap-workbench" class="ap-workbench" hidden>
  <aside id="ap-results" class="ap-results" aria-label="Results across dictionaries"></aside>
  <section id="ap-reader-col" class="ap-reader-col">
   <div id="ap-reader" class="ap-reader" aria-label="Entry reader">
    <p class="ap-reader-hint">Select a headword to read its entry.</p>
   </div>
  </section>
 </div>
</main>

<script>
/* GET-parameter prefill, reflected the same way as lookup/index.php and
   sample/dalglob1.php: is_string() blocks array-injection (?key[]=x);
   htmlspecialchars() neutralises HTML metachars and is the sanitizer
   Semgrep's echoed-request rule recognises; json_encode() supplies the
   quoted, escaped JS literal. */
window.APP_PREFILL = {
 key:
  <?php echo json_encode(htmlspecialchars(isset($_GET['key'])    && is_string($_GET['key'])    ? $_GET['key']    : '')) ?>,
 input:
  <?php echo json_encode(htmlspecialchars(isset($_GET['input'])  && is_string($_GET['input'])  ? $_GET['input']  : '')) ?>,
 output:
  <?php echo json_encode(htmlspecialchars(isset($_GET['output']) && is_string($_GET['output']) ? $_GET['output'] : '')) ?>,
 dict:
  <?php echo json_encode(htmlspecialchars(isset($_GET['dict'])   && is_string($_GET['dict'])   ? $_GET['dict']   : '')) ?>,
 mode:
  <?php echo json_encode(htmlspecialchars(isset($_GET['mode'])   && is_string($_GET['mode'])   ? $_GET['mode']   : '')) ?>
};
</script>
<script src="../lookup/dictmeta.js" defer></script>
<script src="vendor/sanskrit-util.global.js" defer></script>
<script src="app.js" defer></script>
</body>
</html>
