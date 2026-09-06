<?php
require_once(__DIR__ . '/security_headers.php');
// Exclude WARNING messages also, to solve Peter Scharf Mac version.
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
?>
<?php
//getsuggest.php
header("Access-Control-Allow-Origin: *");
header('content-type: application/json; charset=utf-8');
require_once('getsuggestClass.php');
require_once(__DIR__ . '/jsonp_callback_guard.php');
function getsuggestCall() {
 try {
  $temp = new GetsuggestClass();
 } catch (Throwable $e) {
  // H3636 A10: data anomalies now throw; serve a truthful 500 envelope.
  http_response_code(500);
  echo json_encode(array('status' => 500, 'error' => $e->getMessage()));
  return;
 }
 $json = $temp->json;
 // H3636 A19: the "$key??" lone marker is the class's not-found shape;
 // keep the body unchanged but serve a truthful 404.
 if (count($temp->matches) === 1 && preg_match('/\?\?$/u',(string)$temp->matches[0])) {
  http_response_code(404);
 }
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
