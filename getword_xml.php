<?php
require_once(__DIR__ . '/security_headers.php');
// Exclude WARNING messages also, to solve Peter Scharf Mac version.
// H4227 A1 (H3487 audit): report everything; notices/warnings go to the
// error log, never the response body. The old blanket suppression hid real
// defects; display_errors=0 preserves the original 'Peter Scharf Mac'
// concern (no diagnostics in output).
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
?>
<?php
//getword_xml.php
// Retrieves info for a given headword; retrieves from web/sqlite/<dict>.xml

if (isset($_GET['callback'])) {
 header('content-type: application/json; charset=utf-8');
}
header("Access-Control-Allow-Origin: *");
require_once("getwordXmlClass.php");
require_once(__DIR__ . '/jsonp_callback_guard.php');

function getwordXmlCall() {
  try {
   $temp = new GetwordXmlClass();
  } catch (Throwable $e) {
   // H3636 A10: record-level data anomalies now throw; serve a truthful
   // 500 envelope instead of a blank HTTP 200.
   http_response_code(500);
   header('content-type: application/json; charset=utf-8');
   echo json_encode(array('status' => 500, 'error' => $e->getMessage()));
   return;
  }
  $json = $temp->json;
  // H3636 A19: a not-found (or dict-error) payload must not masquerade as
  // HTTP 200. Body shape is unchanged (the status field stays in the body).
  if (isset($temp->status) && $temp->status != 200) {
   http_response_code($temp->status);
  }
  if (isset($_GET['callback'])) {
   // Shared whitelist+reply guard (H4212).
   jsonp_reply($_GET['callback'], $json);
  }else {
   echo $json;
  }
}
getwordXmlCall();
