<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
// salt_multidictClass.php — Multi-dictionary lookup: resolve a headword
// across ALL Cologne dictionaries in one call.
//
// Uses Dalglob (keydoc_glob1) to find which dicts contain the headword,
// then salt_entries_for_key() (from salt_common) to fetch each entry.
// Response is JSON with dict codes as keys, Salt-format entries as values.
//
// Parameters:
//   key     (required) Headword in the input transliteration scheme
//   input   (optional) Input scheme: slp1 (default), deva, hk, roman, itrans, velthuis
//   output  (optional) Output scheme: deva (default), slp1, hk, roman
//   size    (optional) Max entries per dictionary; 0 = unlimited (default 0)
//   field   (optional) Comma-separated response fields: id, headword_slp1, csl
//                      (default: all fields)
require_once(__DIR__ . '/salt_common.php');
require_once(__DIR__ . '/../dalglobClass.php');
require_once(__DIR__ . '/../app/dictmeta.php');

class SaltMultidictClass {
  public $json;

  public function __construct() {
    salt_apply_documented_defaults();

    $json_flags = JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;

    $keyin = isset($_REQUEST['key']) ? $_REQUEST['key'] : '';
    if ($keyin === '') {
      http_response_code(400);
      $this->json = json_encode(array('error' => "Missing parameter: 'key'"), $json_flags);
      return;
    }

    // Clean key (same strip as Parm::init_inputs_key)
    $invalid = array('$', '#', '<', '>', '=', '(', ')', '"', '\\');
    $keyin = str_replace($invalid, '', $keyin);

    // Determine input scheme and transcode to SLP1
    $input = isset($_REQUEST['input']) ? $_REQUEST['input'] : 'slp1';
    $filterin = transcoder_standardize_filter($input);
    $slp1 = ($filterin === 'slp1')
      ? $keyin
      : transcoder_processString($keyin, $filterin, 'slp1');

    if ($slp1 === '') {
      http_response_code(400);
      $this->json = json_encode(array('error' => 'Empty key after transliteration'), $json_flags);
      return;
    }

    // Resolve headword to dictionaries via Dalglob (keydoc_glob1)
    $_REQUEST['dbglob'] = 'keydoc_glob1';
    $_REQUEST['key'] = $slp1;
    $_REQUEST['input'] = 'slp1';
    $_GET['key'] = $slp1;
    $_GET['input'] = 'slp1';

    $dal = new Dalglob();

    if ($dal->ans['status'] !== 200 || empty($dal->ans['dicts'])) {
      $this->json = json_encode(array(
        'status' => 404,
        'key' => $slp1,
        'input' => $input,
        'dicts' => new stdClass(),
      ), $json_flags);
      $dal->close();
      return;
    }

    $dictlist = $dal->ans['dicts'];
    $dal->close();

    // Read parameters
    $output = isset($_REQUEST['output']) ? $_REQUEST['output'] : 'deva';
    $size = isset($_REQUEST['size']) ? max(0, (int)$_REQUEST['size']) : 0;
    $field = isset($_REQUEST['field']) ? $_REQUEST['field'] : '';

    // Field filtering
    $allowed_fields = ($field !== '')
      ? array_flip(array_map('trim', explode(',', $field)))
      : null;

    // Build dictmeta map for ordering and human-readable names
    global $APP_DICTMETA;
    $dict_order = array_flip(array_keys($APP_DICTMETA));
    $dictmeta_info = array();

    // Fetch Salt-format entries for each dictionary, with output transliteration
    $dicts = array();
    foreach ($dictlist as $rec) {
      $dict = $rec['dict'];
      $entries = salt_entries_for_key($slp1, $dict, $output);

      // Apply size cap
      if ($size > 0 && count($entries) > $size) {
        $entries = array_slice($entries, 0, $size);
      }

      // Apply field filtering
      if ($allowed_fields !== null && !empty($entries)) {
        foreach ($entries as $i => $e) {
          $entries[$i] = array_intersect_key($e, $allowed_fields);
        }
      }

      if (!empty($entries)) {
        $dicts[$dict] = $entries;
      }

      // Record metadata for every dict found by Dalglob (even if empty after filter)
      if (isset($APP_DICTMETA[$dict])) {
        $dictmeta_info[$dict] = array(
          'name' => $APP_DICTMETA[$dict][0],
          'year' => $APP_DICTMETA[$dict][1],
        );
      } else {
        $dictmeta_info[$dict] = array(
          'name' => $dict,
          'year' => null,
        );
      }
    }

    if (empty($dicts)) {
      $this->json = json_encode(array(
        'status' => 404,
        'key' => $slp1,
        'input' => $input,
        'dicts' => new stdClass(),
      ), $json_flags);
      return;
    }

    // Sort by dictmeta canonical order (unknown dicts sort last)
    uksort($dicts, function ($a, $b) use ($dict_order) {
      $oa = isset($dict_order[$a]) ? $dict_order[$a] : 999;
      $ob = isset($dict_order[$b]) ? $dict_order[$b] : 999;
      return $oa - $ob;
    });

    $this->json = json_encode(array(
      'status' => 200,
      'key' => $slp1,
      'input' => $input,
      'output' => $output,
      'dictmeta' => $dictmeta_info,
      'dicts' => $dicts,
    ), $json_flags);
  }
}
