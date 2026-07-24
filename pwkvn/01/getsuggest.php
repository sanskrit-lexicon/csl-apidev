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
function getsuggestCall() {
 $temp = new GetsuggestClass();
 $json = $temp->json;
 /* Next for JSONP
  Ref: //www.geekality.net/2010/06/27/php-how-to-easily-provide-json-and-jsonp/
 */
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
getsuggestCall();
?>
