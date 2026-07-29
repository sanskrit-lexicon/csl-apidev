<?php
require_once(__DIR__ . '/../security_headers.php');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
// salt_multidict.php — Multi-dictionary lookup endpoint.
//
// Resolves an SLP1 (or any input-scheme) headword across all Cologne
// dictionaries in a single JSON response, using the Salt entry format.
//
//   GET ?key=Davala&input=slp1
//
// Response envelope:
//   { status: 200, key: "davala", input: "slp1",
//     dicts: { "mw": [SaltEntry, ...], "ap90": [...], ... } }
header("Access-Control-Allow-Origin: *");
header('content-type: application/json; charset=utf-8');
require_once(__DIR__ . '/salt_multidictClass.php');

function saltMultidictCall() {
  $temp = new SaltMultidictClass();
  $json = $temp->json;
  if (isset($_GET['callback'])) {
    $callback = $_GET['callback'];
    if (!preg_match('/^[A-Za-z_$][A-Za-z0-9_$.]{0,127}$/', $callback)) {
      header('content-type: text/plain; charset=utf-8');
      http_response_code(400);
      echo "invalid callback";
      return;
    }
    header('content-type: application/javascript; charset=utf-8');
    echo htmlentities($callback) . "($json)";
  } else {
    echo $json;
  }
}
saltMultidictCall();
