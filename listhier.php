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
//listhier.php
if (isset($_GET['callback'])) {
 header('content-type: application/json; charset=utf-8');
}
header("Access-Control-Allow-Origin: *");
require_once("listhierClass.php");
require_once(__DIR__ . '/jsonp_callback_guard.php');
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
    // Shared whitelist+reply guard (H4212).
    jsonp_reply($_GET['callback'], json_encode($table1));
   }else {
    echo $table1;
   }
 } listhierCall();
?> 
