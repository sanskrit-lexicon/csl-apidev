<?php
require_once(__DIR__ . '/security_headers.php');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
// cleanurl.php — clean-URL (permalink) router: /{DICT}/{ref}, unified with the Salt API
// permalink (doc/cleanurl.md, doc/salt_entries.md §1.3/§1.7).
//
// PILOT SCOPE (SPEC-3): dict whitelist = mw only. The full dict-code whitelist already
// used by parse_uri.php (simple-search/v1.1/parse_uri.php) is the source to extend from
// once Jim approves widening past the MW pilot — see cleanurl_whitelist() below.
//
// Content negotiation (doc/cleanurl.md §0): a production router picks HTML (listview)
// vs JSON (salt_entries.php) on Accept / ?format=. That listview wiring is a separate,
// larger integration (doc/cleanurl.md §5) and is NOT done here. This pilot implements
// only doc/cleanurl.md's own Build & test plan step 1: parse_cleanurl() + the routing
// decision, served as the §7 diagnostic JSON envelope (?format=json contract) regardless
// of Accept, so the routing logic can be verified locally before the HTML face is wired.
//
// Contract: doc/cleanurl.md §2 (scheme), §3 (disambiguation), §7 (diagnostic JSON).

require_once(__DIR__ . '/api1/salt_common.php');   // chdir's to repo root; gives Dal/Parm

function cleanurl_whitelist() { return array('mw'); }   // PILOT — mw only (SPEC-3 scope)

// Parse a request URI into a routing decision, or null if the first segment is not a
// whitelisted dict code (i.e. this path is not ours — caller should pass through untouched).
function parse_cleanurl($uri, $whitelist) {
  $path = parse_url($uri, PHP_URL_PATH);
  $path = preg_replace('|^/|', '', (string)$path);
  $path = preg_replace('|/$|', '', $path);
  $segs = ($path === '') ? array() : explode('/', $path);
  if (count($segs) === 0) { return null; }
  $dict = strtolower($segs[0]);
  if (!in_array($dict, $whitelist, true)) { return null; }   // not a dict route
  $result = array('url' => $uri, 'dict' => $dict);
  if (count($segs) === 1) {
    $result['matched'] = 'front';
    return $result;
  }
  $seg1 = $segs[1];
  if (preg_match('/^\d+(\.\d+)?$/', $seg1)) {
    $result['matched'] = 'id';
    $result['lnum'] = $seg1;
  } else {
    $result['matched'] = 'headword';
    $result['key'] = urldecode($seg1);
    $result['hom'] = (isset($segs[2]) && preg_match('/^\d+$/', $segs[2])) ? (int)$segs[2] : 1;
  }
  return $result;
}

// All exact-key records for $key, sorted by lnum (source order) — the shared homonym walk
// for both the id->hom and headword->lnum directions. Reuses Dal (dal.php get3), the same
// low-level access salt_common's pipeline is built on, so this face cannot diverge either.
function cleanurl_exact_key_records($dict, $key) {
  $dal = new Dal($dict);
  if (!$dal->status) { $dal->close(); return array(); }
  $rows = $dal->get3($key);   // key LIKE 'key%' -- filter to the exact key below
  $dal->close();
  $exact = array();
  foreach ($rows as $r) { if ($r[0] === $key) { $exact[] = $r; } }
  usort($exact, function ($a, $b) { return $a[1] <=> $b[1]; });
  return $exact;
}

function resolve_cleanurl($result) {
  $dict = $result['dict'];
  $base_params = array('dict' => $dict, 'input' => 'slp1', 'output' => 'deva', 'accent' => 'no');

  if ($result['matched'] === 'front') {
    $result['render'] = 'listview';
    $result['params'] = $base_params;
    return $result;
  }

  if ($result['matched'] === 'id') {
    $dal = new Dal($dict);
    $rows = $dal->status ? $dal->get2($result['lnum'], $result['lnum']) : array();
    $dal->close();
    if (count($rows) === 0) {
      $result['key'] = null; $result['hom'] = null;
      $result['render'] = 'listview';
      $result['note'] = 'no record at this lnum; listview shows "not found" in entry pane';
      $result['params'] = $base_params + array('lnum' => $result['lnum']);
      return $result;
    }
    $key = $rows[0][0];
    $exact = cleanurl_exact_key_records($dict, $key);
    $hom = 1;
    foreach ($exact as $i => $r) { if ((string)$r[1] === (string)$result['lnum']) { $hom = $i + 1; break; } }
    $result['key'] = $key;
    $result['hom'] = $hom;
    $result['render'] = 'listview';
    $result['params'] = $base_params + array('key' => $key, 'lnum' => $result['lnum']);
    return $result;
  }

  // headword route
  $key = $result['key'];
  $hom = $result['hom'];
  $exact = cleanurl_exact_key_records($dict, $key);
  $idx = $hom - 1;
  $lnum = isset($exact[$idx]) ? (string)$exact[$idx][1] : null;
  $result['lnum'] = $lnum;
  $result['render'] = 'listview';
  if ($lnum === null) {
    $result['note'] = 'no record; listview shows "not found" in entry pane';
  }
  $result['params'] = $base_params + array('key' => $key, 'lnum' => $lnum);
  return $result;
}

// ---- entry point ---------------------------------------------------------------------
if (php_sapi_name() !== 'cli') {
  header('Access-Control-Allow-Origin: *');
  $uri = $_SERVER['REQUEST_URI'];
  $result = parse_cleanurl($uri, cleanurl_whitelist());
  header('content-type: application/json; charset=utf-8');
  if ($result === null) {
    http_response_code(404);
    echo json_encode(array('error' => 'unknown dict code (pilot whitelist: ' . implode(',', cleanurl_whitelist()) . ')'));
  } else {
    echo json_encode(resolve_cleanurl($result), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  }
}
?>
