<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
?>
<?php
// salt_common.php — shared helpers for the Salt API endpoints (entries, ids, graphql).
// One search dispatcher + one envelope builder, so the three faces cannot diverge.
// Contract: doc/salt_entries.md ; profile: csl-standards/docs/SALT_API_PROFILE.md
require_once(__DIR__ . '/../parm.php');
// require_once(__DIR__ . '/../getwordClass.php');   // structured record data (doc/getword.md §1.1.8)
// require_once(__DIR__ . '/../dalglob.php');          // headword-index access for the search modes

// ---- allowed values (match C-SALT exactly) ----
function salt_fields()      { return array('id','headword_slp1','sense','re_headwords_slp1','created','xml'); }
function salt_query_types() { return array('term','fuzzy','match','match_phrase','prefix','wildcard','regexp'); }

// ---- repeated query parameter (e.g. ?ids=a&ids=b) -> array ----
// PHP's $_GET keeps only the last value for repeated keys without '[]', so parse the raw
// query string. Also accepts the bracketed form ids[]=a&ids[]=b.
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

// ---- search: return an array of lnum (Cologne record ids) ----
function salt_search($field, $query, $query_type, $size) {
  // TODO: dispatch on $query_type over the HEADWORD field (no ES needed for the pilot):
  //   term -> getword key lookup ; prefix -> listhier ; wildcard/regexp -> headword index ;
  //   fuzzy -> getsuggest. match/match_phrase over the body wait for Phase 4.
  return array();
}

// ---- id <-> lnum ----
// Salt id is lemma-{headword_slp1} or lemma-{headword_slp1}-{n} (homonym). Resolve to lnum.
function salt_id_to_lnum($id) {
  if (strpos($id, 'lemma-') !== 0) { return null; }
  $rest = substr($id, strlen('lemma-'));
  $hom  = null;
  if (preg_match('/^(.*)-(\\d+)$/', $rest, $m)) { $rest = $m[1]; $hom = (int)$m[2]; }
  // TODO: map (key=$rest, homonym=$hom) -> lnum via the existing getword/dal lookup.
  return null;
}

// ---- build one Salt entry object from a Cologne record id ----
function salt_entry_build($lnum) {
  $rec = salt_get_record($lnum);                    // TODO: structured getword record
  $key = isset($rec['key']) ? $rec['key'] : '';
  $hom   = isset($rec['homonym']) ? $rec['homonym'] : null;
  $count = isset($rec['homonymCount']) ? $rec['homonymCount'] : 1;
  $suffix = ($hom && $count > 1) ? "-$hom" : '';
  return array(
    'id'                => "lemma-{$key}{$suffix}",  // matches C-SALT exactly
    'headword_slp1'     => $key,
    'sense'             => isset($rec['sense']) ? $rec['sense'] : array(),               // best-effort pre-TEI
    're_headwords_slp1' => isset($rec['re_headwords']) ? $rec['re_headwords'] : array(),
    'created'           => isset($rec['created']) ? $rec['created'] : null,
    'xml'               => null,                     // TEI: Phase 5 (never display-XML)
    'csl' => array(
      'lnum'         => (string)$lnum,
      'page'         => isset($rec['pageNumber']) ? $rec['pageNumber'] : null,
      'column'       => isset($rec['columnId']) ? $rec['columnId'] : null,
      'scanUrl'      => isset($rec['imgUrl']) ? $rec['imgUrl'] : null,
      'html'         => isset($rec['html']) ? $rec['html'] : null,
      'text'         => isset($rec['text']) ? $rec['text'] : null,
      'xmlCsl'       => isset($rec['xml']) ? $rec['xml'] : null,    // CSL display-XML, now
      'references'   => isset($rec['references']) ? $rec['references'] : array(),
      'headwordDeva' => salt_translit($key, 'deva'),
      'headwordIast' => salt_translit($key, 'roman'),
      'accentedKey'  => isset($rec['key2']) ? $rec['key2'] : null,  // e.g. agn/i
    ),
  );
}

function salt_get_record($lnum) { /* TODO: GetwordClass / getword_data.php by lnum */ return array(); }
function salt_translit($slp1, $to) { /* TODO: transcoder_processString($slp1, 'slp1', $to) */ return $slp1; }
?>
