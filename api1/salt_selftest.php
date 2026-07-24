<?php
require_once(__DIR__ . '/../security_headers.php');
// salt_selftest.php — CLI smoke test for the Salt API controllers (developer harness).
//
//   php api1/salt_selftest.php  [dict]  [headword ...]
//   php api1/salt_selftest.php  mw  agni  indra  ka
//
// Exercises SaltEntriesClass, SaltIdsClass and SaltGraphqlClass directly (no web server),
// printing each JSON envelope so the wiring can be eyeballed against real *.sqlite data.
// Not part of the request path. See doc/salt_api_handoff.md.

if (php_sapi_name() !== 'cli') { die("Run this from the command line.\n"); }
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

require_once(__DIR__ . '/salt_entriesClass.php');   // pulls salt_common.php (which chdir's to repo root)
require_once(__DIR__ . '/salt_idsClass.php');
require_once(__DIR__ . '/salt_graphqlClass.php');

$args  = array_slice($argv, 1);
$dict  = count($args) ? array_shift($args) : 'mw';
$words = count($args) ? $args : array('agni', 'indra', 'ka');

function reset_request($pairs) {
  $_GET = array(); $_POST = array(); $_REQUEST = array();
  foreach ($pairs as $k => $v) { $_GET[$k] = $v; $_REQUEST[$k] = $v; }
}
function show($label, $json) {
  echo "----- $label -----\n";
  $obj = json_decode($json, true);
  echo ($obj === null) ? $json : json_encode($obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  echo "\n\n";
}

echo "# Salt API self-test   dict=$dict   words=" . implode(',', $words) . "\n\n";

// 1. entries (term) for each headword; collect the ids returned
$ids_seen = array();
foreach ($words as $w) {
  reset_request(array('dict' => $dict, 'field' => 'headword_slp1', 'query' => $w,
                      'query_type' => 'term', 'input' => 'slp1', 'size' => '5'));
  $c = new SaltEntriesClass();
  show("entries  term  '$w'", $c->json);
  $d = json_decode($c->json, true);
  if (isset($d['data']['entries'])) {
    foreach ($d['data']['entries'] as $e) { if (isset($e['id'])) { $ids_seen[] = $e['id']; } }
  }
}

// 2. entries (prefix) on the first headword
reset_request(array('dict' => $dict, 'field' => 'headword_slp1', 'query' => $words[0],
                    'query_type' => 'prefix', 'input' => 'slp1', 'size' => '8'));
$c = new SaltEntriesClass();
show("entries  prefix  '{$words[0]}'  (size 8)", $c->json);

// 3. ids — fetch back the first couple of ids seen above
$ids = array_slice(array_values(array_unique($ids_seen)), 0, 2);
if (count($ids)) {
  reset_request(array('dict' => $dict));
  $_GET['ids'] = $ids; $_REQUEST['ids'] = $ids;        // bracketed-form path in salt_multi_param()
  $c = new SaltIdsClass();
  show("ids  " . implode(' , ', $ids), $c->json);
} else {
  echo "----- ids -----\n(no ids returned by entries; skipping)\n\n";
}

// 4. graphql — entries query via $_REQUEST['query'] (CLI has no php://input body).
//    Note: graphql ids() needs an array variable, i.e. php://input — web only, not tested here.
reset_request(array('dict' => $dict,
  'query' => '{ entries(field: headword_slp1, query: "' . $words[0] . '", queryType: term, size: 2)'
           . ' { id headwordSlp1 csl { lnum page column } } }'));
$c = new SaltGraphqlClass();
show("graphql  entries  '{$words[0]}'", $c->json);

echo "# done.\n";
echo "# Empty 'entries' usually means the {$dict}.sqlite database was not found from the repo\n";
echo "# root, or a 'VERIFY:' assumption in salt_common.php needs adjusting. The constructors\n";
echo "# reuse the getword pipeline, so a malformed record can exit(1) inside getword_data.php.\n";
?>
