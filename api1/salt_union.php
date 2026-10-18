<?php
require_once(__DIR__ . '/../security_headers.php');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
?>
<?php
// salt_union.php — Union headword index endpoint (A55v2, H6065).
//
//   GET /api1/salt_union.php                -> index meta (members, versions)
//   GET /api1/salt_union.php?key=aNga       -> union membership for a headword
//
// Contract: doc/salt_union.md
header("Access-Control-Allow-Origin: *");
header('content-type: application/json; charset=utf-8');
require_once(__DIR__ . '/salt_unionClass.php');
require_once(__DIR__ . '/../jsonp_callback_guard.php');

function saltUnionCall() {
  $temp = new SaltUnionClass();
  $json = $temp->json;
  if (isset($_GET['callback'])) {
    // Shared whitelist+reply guard (H4212).
    jsonp_reply($_GET['callback'], $json, true);
  } else {
    echo $json;
  }
}
saltUnionCall();
?>
