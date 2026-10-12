<?php
require_once(__DIR__ . '/security_headers.php');
// H4227 A1 (H3487 audit): report everything; notices/warnings go to the
// error log, never the response body. The old blanket suppression hid real
// defects; display_errors=0 preserves the original 'Peter Scharf Mac'
// concern (no diagnostics in output).
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
?>
<?php
/* servepdf.php  Apr 27, 2015 Multidictionary display of scanned images
  Similar to servepdf for the dictionaries
Parameters:
 dict: one of the dictionary codes (case insensitive)
 page: a specific page of the dictionary.  In the form of the contents
       of a <pc> element
 key: a headword, in SLP1.  
  Only one of 'page' and 'key' should be used.  If both are present, then
  'key' parameter is ignored and 'page' parameter prevails.
*/
if (isset($_GET['callback'])) {
 header('content-type: application/json; charset=utf-8');
}
header("Access-Control-Allow-Origin: *");
require_once('servepdfClass.php');
require_once(__DIR__ . '/jsonp_callback_guard.php');

function servepdfCall() {
  try {
   $temp = new ServepdfClass();
  } catch (Throwable $e) {
   // H3636 A10: data anomalies now throw; serve a truthful 500 page.
   http_response_code(500);
   header('content-type: text/html; charset=utf-8');
   echo "<p>servepdf error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES) . "</p>";
   return;
  }
  $table1 = $temp->html;
  // H3636 A8: page-not-found / dict-error degraded to the error page;
  // surface it as a truthful HTTP status instead of 200.
  if (isset($temp->status) && $temp->status != 200) {
   http_response_code($temp->status);
  }
  if (isset($_GET['callback'])) {
   // Shared whitelist+reply guard (H4212).
   jsonp_reply($_GET['callback'], json_encode($table1));
  }else {
   echo $table1;
  }
}
servepdfCall();
?>

