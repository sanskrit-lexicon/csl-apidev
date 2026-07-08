<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
?>
<?php
// salt_common.php — shared search + envelope builder for the Salt API (entries, ids, graphql).
//
// PHASE 1 WIRING (MW). Reuses the existing, tested getword pipeline instead of
// reimplementing it: Dal for headword search, Getword_data for per-record rendering,
// transcoder_processString for transliteration. One builder, so the three faces cannot
// diverge.
//
// /!\ NOT RUN-VERIFIED: authored without a PHP runtime or the per-dict *.sqlite databases
//     (downloaded separately, see download_hwnorm1c_sqlite.sh). Test against real data.
//     Each non-obvious assumption is flagged inline with "VERIFY:".
//
// Contract: doc/salt_entries.md ; profile: csl-standards/docs/SALT_API_PROFILE.md

// The codebase resolves its require_once() and sqlite paths relative to the repo root
// (entry points historically live there). An api1/ endpoint must therefore chdir to root
// before loading parm.php / dal.php / getword_data.php.
chdir(dirname(__DIR__));
require_once('parm.php');           // also initializes the transcoder
require_once('dal.php');
require_once('getword_data.php');   // pulls basicadjust.php, basicdisplay.php, dispitem.php

// SPEC-3 parity finding: Parm's global default for the 'input'/transLit transliteration is
// 'hk' (parm.php ~line 55), not 'slp1' -- but doc/salt_entries.md §1.6 documents the Salt
// default as 'slp1' (and the doc's own example URLs omit `input` entirely, relying on that
// default). Left unfixed, a client following the documented contract silently gets zero
// results for any headword containing a letter where HK and SLP1 diverge (capital D/R/S/N/
// T/... -- a large fraction of real SLP1 keys). Measured impact: 233/500 (46.6%) of a
// stratified sample of real MW headwords, see reports/salt_parity_mw_2026-07.md.
//
// Scoped fix: default 'input' to 'slp1' for the THREE Salt controllers only, before they
// construct Parm -- parm.php's own global default is left untouched (other, non-Salt
// consumers of Parm may depend on the 'hk' default for citation-search entry points).
function salt_apply_documented_defaults() {
  if (!isset($_REQUEST['transLit']) && !isset($_REQUEST['input'])) {
    $_REQUEST['input'] = 'slp1';
    $_GET['input'] = 'slp1';
  }
}

// ---- allowed values (match C-SALT exactly) ----
function salt_fields()      { return array('id','headword_slp1','sense','re_headwords_slp1','created','xml'); }
function salt_query_types() { return array('term','fuzzy','match','match_phrase','prefix','wildcard','regexp'); }
function salt_phase1_fields() { return array('headword_slp1'); }
function salt_phase1_field_error($field) {
  return "field '$field' not available until a Phase 4/5 index or resolver is implemented";
}

// ---- repeated query parameter (e.g. ?ids=a&ids=b) -> array ----
function salt_multi_param($name) {
  if (isset($_GET[$name]) && is_array($_GET[$name])) { return $_GET[$name]; }   // name[]=...
  $out = array();
  $qs = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
  foreach (explode('&', $qs) as $pair) {
    $kv = explode('=', $pair, 2);
    if ($kv[0] === $name && isset($kv[1])) { $out[] = urldecode($kv[1]); }
  }
  return $out;
}

// Transcode the Salt `query` (in the input transliteration) to SLP1, via Parm.
function salt_query_to_slp1($parm, $query) {
  list($keyin, $keyin1, $key) = $parm->compute_text($query);   // parm.php compute_text()
  return $key;
}

// ===== search: query -> array of Salt entries =====================================
function salt_search_entries($parm, $field, $query, $query_type, $size) {
  $dict = $parm->dict;
  $entries = array();
  foreach (salt_search_keys($parm, $field, $query, $query_type, $size) as $key) {
    foreach (salt_entries_for_key($key, $dict) as $e) {
      $entries[] = $e;
      if (count($entries) >= $size) { return $entries; }
    }
  }
  return $entries;
}

// Distinct SLP1 keys matching the query under the given mode, via real Dal methods.
function salt_search_keys($parm, $field, $query, $query_type, $size) {
  $dal = new Dal($parm->dict);
  if (!$dal->status) {
    $dal->close();
    return array();
  }
  $slp = salt_query_to_slp1($parm, $query);
  $max = ($size > 0) ? $size : 25;
  $keys = array();
  switch ($query_type) {
    case 'term':                                   // exact headword (the transcoded query)
      $keys = array($slp);
      break;
    case 'prefix':                                 // headword LIKE slp%   (distinct keys)
      $keys = $dal->get3c($slp, $max);
      break;
    case 'wildcard':                               // glob * ? -> SQL LIKE % _
      $like = strtr($slp, array('*' => '%', '?' => '_'));
      foreach ($dal->get3b($like, $max) as $r) { $keys[] = $r[0]; }
      break;
    case 'fuzzy':                                  // VERIFY: approximated by starts-with
      $keys = $dal->get3c($slp, $max);             //   (matches getsuggest's behaviour)
      break;
    case 'regexp':                                 // SQLite LIKE has no regex; body/phrase
    case 'match':                                  // search needs a Phase-4 index (FTS).
    case 'match_phrase':                           // -> empty for the MW pilot.
      break;
  }
  $dal->close();
  // distinct, capped at size
  $seen = array(); $out = array();
  foreach ($keys as $k) {
    if ($k === null || $k === '') { continue; }
    if (!isset($seen[$k])) { $seen[$k] = 1; $out[] = $k; if (count($out) >= $size) { break; } }
  }
  return $out;
}

// ===== id -> Salt entries (lemma-{key} | lemma-{key}-{n} | lemma-{key}-L{lnum}) =====
function salt_entries_for_id($parm, $id) {
  if (strpos($id, 'lemma-') !== 0) { return array(); }
  $rest = substr($id, strlen('lemma-'));
  // Recover the SLP1 key by stripping a disambiguator suffix (mirror of the mint side):
  //   -L{lnum}  (lnum fallback; lnum may carry a decimal, e.g. -L41336.05)
  //   -{n}      (C-SALT homonym number)
  $key  = $rest;
  $disambiguated = false;
  if (preg_match('/^(.*)-L[0-9.]+$/', $rest, $m))      { $key = $m[1]; $disambiguated = true; }
  elseif (preg_match('/^(.*)-[0-9]+$/', $rest, $m))    { $key = $m[1]; $disambiguated = true; }
  $all = salt_entries_for_key($key, $parm->dict);
  if (!$disambiguated) { return $all; }                 // bare lemma-{key}: whole headword
  $out = array();
  foreach ($all as $e) { if ($e['id'] === $id) { $out[] = $e; } }   // pick the exact record
  return $out;
}

// ===== build every Salt entry for one SLP1 key, via the tested getword pipeline ====
function salt_entries_for_key($key, $dict) {
  // Getword_data is driven by Parm, which reads $_REQUEST (the codebase convention).
  // VERIFY: a cleaner refactor would give Getword_data a direct (dict, key) constructor.
  $save = $_REQUEST;
  $_REQUEST['dict']  = $dict;
  $_REQUEST['key']   = $key;
  $_REQUEST['input'] = 'slp1';        // $key is already SLP1
  $gd = new Getword_data(false);      // false: keep the homonym number in <info>
  $matches    = $gd->matches;         // [key, lnum, "<info>...</info><body>...</body>"]
  $xmlmatches = $gd->xmlmatches;      // [key, lnum, displayXml]
  $_REQUEST = $save;

  $n = count($matches);
  $entries = array();
  for ($i = 0; $i < $n; $i++) {
    $xmlmatch = isset($xmlmatches[$i]) ? $xmlmatches[$i] : array($key, $matches[$i][1], '');
    $entries[] = salt_entry_from_record($dict, $matches[$i], $xmlmatch, $n);
  }
  return $entries;
}

// One getword record (matches row + xmlmatches row) -> Salt entry object.
function salt_entry_from_record($dict, $match, $xmlmatch, $homCount) {
  list($k, $lnum, $htmlinfo) = $match;
  $xml = isset($xmlmatch[2]) ? $xmlmatch[2] : '';

  // matches[i][2] = "<info>INFO</info><body>BODY</body>"  (getword_data adapter)
  $info = ''; $body = $htmlinfo;
  if (preg_match('|<info>(.*?)</info><body>(.*)</body>|s', $htmlinfo, $m)) {
    $info = $m[1]; $body = $m[2];
  }
  $page = null; $col = null; $key2 = null; $hom = null;
  if (strtolower($dict) === 'mw') {
    // MW info = "page,col:hcode:key2:hom:hui"  (getword_data.php $infoval)
    $parts   = explode(':', $info);
    $pageref = isset($parts[0]) ? $parts[0] : '';
    $key2    = (isset($parts[2]) && $parts[2] !== '') ? $parts[2] : null;
    $hom     = (isset($parts[3]) && $parts[3] !== '') ? $parts[3] : null;
    $pc = explode(',', $pageref);
    $page = (isset($pc[0]) && $pc[0] !== '') ? $pc[0] : null;
    $col  = (isset($pc[1]) && $pc[1] !== '') ? $pc[1] : null;
  } else {
    // VERIFY: for non-MW, info is the page reference (format varies by dictionary).
    $page = ($info !== '') ? $info : null;
  }
  // id disambiguator for multi-record headwords (single-record keys stay bare):
  //   <hom> present  -> "-{hom}"        (matches C-SALT's lemma-{key}-{n})
  //   no <hom>       -> "-L{lnum}"      (fallback: the Cologne lnum is per-record unique,
  //                                      so the `ids` face can still address each record;
  //                                      a sanctioned divergence from the C-SALT id scheme
  //                                      for sub-records the source does not number)
  if ($homCount > 1) {
    $suffix = ($hom !== null) ? "-$hom" : "-L$lnum";
  } else {
    $suffix = '';
  }

  return array(
    'id'                => "lemma-{$k}{$suffix}",          // C-SALT: -{n}; else -L{lnum} fallback
    'headword_slp1'     => $k,
    'sense'             => array(),                        // TODO: TEI-grade sense split (Phase 5)
    're_headwords_slp1' => array(),                        // TODO: run-ons (<k2> / sub-entries)
    'created'           => null,
    'xml'               => null,                           // TEI: Phase 5 (never display-XML)
    'csl' => array(
      'lnum'         => (string)$lnum,
      'page'         => $page,
      'column'       => $col,
      'scanUrl'      => ($page !== null) ? salt_scan_url($dict, $page) : null,
      'html'         => $body,                             // VERIFY: SLP1-tagged; apply transcoder_processElements for final display
      'text'         => trim(strip_tags($body)),
      'xmlCsl'       => $xml,                              // CSL display-XML, available now
      'references'   => salt_extract_refs($xml),           // best-effort (see below)
      'headwordDeva' => salt_translit($k, 'deva'),
      'headwordIast' => salt_translit($k, 'roman'),
      'accentedKey'  => $key2,                             // e.g. agn/i (MW key2)
    ),
  );
}

// best-effort flat reference list: the <ls> source labels in the display XML.
// VERIFY: tag/structure varies by dictionary; refine per dict if needed.
function salt_extract_refs($xml) {
  $refs = array();
  if (preg_match_all('|<ls[^>]*>(.*?)</ls>|', $xml, $m)) { $refs = $m[1]; }
  return $refs;
}

// clean Salt permalink to the scanned page (cf. servepdf / doc/salt_entries.md §1.7 cleanurl).
function salt_scan_url($dict, $page) {
  return '/' . strtoupper($dict) . '/page/' . rawurlencode($page);
}

function salt_translit($slp1, $to) {
  if ($slp1 === null || $slp1 === '') { return $slp1; }
  return transcoder_processString($slp1, 'slp1', $to);
}
?>
