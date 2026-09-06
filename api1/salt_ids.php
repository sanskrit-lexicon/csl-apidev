<?php
require_once(__DIR__ . '/../security_headers.php');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
?>
<?php
// salt_ids.php — Salt API: ids endpoint (batch fetch by id). Contract: doc/salt_ids.md
header("Access-Control-Allow-Origin: *");
header('content-type: application/json; charset=utf-8');
require_once(__DIR__ . '/salt_idsClass.php');
require_once(__DIR__ . '/../jsonp_callback_guard.php');
function saltIdsCall() {
  $temp = new SaltIdsClass();
  $json = $temp->json;
  if (isset($_GET['callback'])) {       // JSONP, like getsuggest.php
    // Shared whitelist+reply guard (H4212).
    jsonp_reply($_GET['callback'], $json, true);
  } else {
    echo $json;
  }
}
saltIdsCall();
?>
