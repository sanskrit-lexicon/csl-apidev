<?php
require_once(__DIR__ . '/security_headers.php');
// Exclude WARNING messages also, to solve Peter Scharf Mac version.
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
?>
<?php
//getword_xml.php
// Retrieves info for a given headword; retrieves from web/sqlite/<dict>.xml

if (isset($_GET['callback'])) {
 header('content-type: application/json; charset=utf-8');
}
header("Access-Control-Allow-Origin: *");
require_once("getwordXmlClass.php");

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
   $callback = $_GET['callback'];
   // Only allow a safe JSONP callback identifier. Echoing the raw callback
   // is a reflected-XSS / JSONP-injection vector, so reject anything else.
   if (!preg_match('/^[A-Za-z_$][A-Za-z0-9_$.]{0,127}$/',$callback)) {
    header('content-type: text/plain; charset=utf-8');
    http_response_code(400);
    echo "invalid callback";
    return;
   }
   echo htmlentities($callback) . "($json)";
  }else {
   echo $json;
  }
}
getwordXmlCall();
