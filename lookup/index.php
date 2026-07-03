<?php
// Exclude WARNING messages also, to solve Peter Scharf Mac version.
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cologne Sanskrit Lexicon &mdash; Lookup</title>
<link rel="stylesheet" type="text/css" href="../css/basic.css">
<link rel="stylesheet" type="text/css" href="lookup.css">
</head>
<body>
<noscript>This page needs JavaScript to look up citations.</noscript>

<header class="lk-header">
 <a class="lk-logo" href="//www.sanskrit-lexicon.uni-koeln.de/">
  <img src="//www.sanskrit-lexicon.uni-koeln.de/images/cologne_univ_seal.gif"
       width="48" height="48" alt="University of Cologne">
 </a>
 <h1>Sanskrit Lexicon Lookup</h1>
</header>

<form id="lk-form" class="lk-form" autocomplete="off">
 <div class="lk-field lk-field-key">
  <label for="lk-key">citation</label>
  <input type="text" id="lk-key" name="key" list="lk-suggestions"
         value="<?php echo (isset($_GET['key']) && is_string($_GET['key'])) ? htmlspecialchars($_GET['key']) : ''; ?>"
         autocapitalize="off" spellcheck="false"
         aria-describedby="lk-preview">
  <datalist id="lk-suggestions"></datalist>
 </div>

 <div class="lk-field lk-field-scheme" id="lk-scheme-field">
  <label for="lk-scheme">script</label>
  <select id="lk-scheme">
   <option value="hk" selected="selected">HK</option>
   <option value="slp1">SLP1</option>
   <option value="itrans">ITRANS</option>
  </select>
  <span id="lk-scheme-auto" class="lk-badge" hidden></span>
 </div>

 <div class="lk-field lk-field-output">
  <label for="lk-output">output</label>
  <select id="lk-output" name="output">
   <option value="deva">Devanagari</option>
   <option value="hk">HK</option>
   <option value="slp1">SLP1</option>
   <option value="itrans">ITRANS</option>
   <option value="roman" selected="selected">IAST</option>
  </select>
 </div>

 <button type="submit" id="lk-submit">Search</button>
 <button type="button" id="lk-copylink" class="lk-copylink" hidden>Copy link</button>
</form>

<p id="lk-preview" class="lk-preview"></p>
<p id="lk-status" class="lk-status" role="status" aria-live="polite"></p>

<div id="lk-filterbar" class="lk-filterbar" role="group" aria-label="Filter dictionaries" hidden></div>
<div id="lk-dictlist" class="lk-dictlist" role="group" aria-label="Dictionaries containing this citation"></div>

<div id="lk-tabs" class="lk-tabs" role="tablist" aria-label="Homonyms" hidden></div>
<div id="lk-panels" class="lk-panels"></div>

<script>
/* GET-parameter prefill, reflected the same way as sample/dalglob1.php:
   is_string() blocks array-injection (?key[]=x); htmlspecialchars()
   neutralises HTML metachars and is the sanitizer Semgrep's echoed-request
   rule recognises; json_encode() supplies the quoted, escaped JS literal. */
window.LOOKUP_PREFILL = {
 key:
  <?php echo json_encode(htmlspecialchars(isset($_GET['key'])    && is_string($_GET['key'])    ? $_GET['key']    : '')) ?>,
 input:
  <?php echo json_encode(htmlspecialchars(isset($_GET['input'])  && is_string($_GET['input'])  ? $_GET['input']  : '')) ?>,
 output:
  <?php echo json_encode(htmlspecialchars(isset($_GET['output']) && is_string($_GET['output']) ? $_GET['output'] : '')) ?>
};
</script>
<script src="dictmeta.js" defer></script>
<script src="lookup.js" defer></script>
</body>
</html>
