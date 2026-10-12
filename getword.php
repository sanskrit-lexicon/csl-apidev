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
//getword.php
if (isset($_GET['callback'])) {
 header('content-type: application/json; charset=utf-8');
}
header("Access-Control-Allow-Origin: *");
require_once("getwordClass.php");
require_once(__DIR__ . '/jsonp_callback_guard.php');
function getwordCall() {
  try {
   $temp = new GetwordClass();
  } catch (Throwable $e) {
   // H3636 A10/A12: record-level data anomalies now throw; serve a
   // truthful 500 envelope instead of a blank HTTP 200.
   http_response_code(500);
   header('content-type: application/json; charset=utf-8');
   echo json_encode(array('status' => 500, 'error' => $e->getMessage()));
   return;
  }
  $table1 = $temp->table1;
   if ($temp->getParms->keyMissing) {
    // H4227 A15: missing required parameter — a real 400, not the 'guru'
    // entry the old default silently served.
    http_response_code(400);
    header('content-type: application/json; charset=utf-8');
    echo json_encode(array('status' => 400,
      'error' => "required parameter 'key' is missing"));
    return;
   }
    if (isset($_GET['callback'])) {
    // Shared whitelist+reply guard (H4212; contract identical to the inline
    // idiom this replaces — see jsonp_callback_guard.php header).
    jsonp_reply($_GET['callback'], json_encode($table1));
   }else {
    echo $table1;
   }
 }
 getwordCall();
?>
