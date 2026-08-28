<?php
require_once(__DIR__ . '/security_headers.php');
// Exclude WARNING messages also, to solve Peter Scharf Mac version.
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
?>
<?php
//listhier.php
if (isset($_GET['callback'])) {
 header('content-type: application/json; charset=utf-8');
}
header("Access-Control-Allow-Origin: *");
require_once("listhierClass.php");
function listhierCall() {
  try {
   $temp = new ListhierClass();
  } catch (Throwable $e) {
   // H3636 A9/A10: no record (or malformed data) now throws; serve a
   // truthful 404 envelope instead of a blank HTTP 200.
   http_response_code(404);
   header('content-type: text/html; charset=utf-8');
   echo "<p>listhier error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES) . "</p>";
   return;
  }
  $table1 = $temp->table1;
  if (isset($_GET['callback'])) {
   $json = json_encode($table1);
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
   echo $table1;
  }
 } listhierCall();
?> 
