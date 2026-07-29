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
//   output  (optional) Output scheme: deva (default), slp1, hk, roman (for reference)
//   accent  (optional) yes/no
require_once(__DIR__ . '/salt_common.php');
require_once(__DIR__ . '/../dalglobClass.php');

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

    // Fetch Salt-format entries for each dictionary
    $dicts = array();
    foreach ($dictlist as $rec) {
      $dict = $rec['dict'];
      $entries = salt_entries_for_key($slp1, $dict);
      if (!empty($entries)) {
        $dicts[$dict] = $entries;
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

    $this->json = json_encode(array(
      'status' => 200,
      'key' => $slp1,
      'input' => $input,
      'dicts' => $dicts,
    ), $json_flags);
  }
}
