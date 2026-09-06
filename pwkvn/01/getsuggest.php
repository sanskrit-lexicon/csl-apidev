<?php
// H1523: baseline headers + CSP-Report-Only (parity with root getsuggest.php)
require_once(__DIR__ . '/../../security_headers.php');
// Exclude WARNING messages also, to solve Peter Scharf Mac version.
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
?>
<?php
//getsuggest.php
header("Access-Control-Allow-Origin: *");
header('content-type: application/json; charset=utf-8');
require_once('getsuggestClass.php');
require_once(__DIR__ . '/../../jsonp_callback_guard.php');
function getsuggestCall() {
 $temp = new GetsuggestClass();
 $json = $temp->json;
 /* Next for JSONP
  Ref: //www.geekality.net/2010/06/27/php-how-to-easily-provide-json-and-jsonp/
 */
 if (isset($_GET['callback'])) {
  // Shared whitelist+reply guard (H4212).
  jsonp_reply($_GET['callback'], $json);
 }else {
  echo $json;
 }
}
getsuggestCall();
?>
