<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
?>
<?php
// salt_ids.php — Salt API: ids endpoint (batch fetch by id). Contract: doc/salt_ids.md
header("Access-Control-Allow-Origin: *");
header('content-type: application/json; charset=utf-8');
require_once(__DIR__ . '/salt_idsClass.php');
function saltIdsCall() {
  $temp = new SaltIdsClass();
  $json = $temp->json;
  if (isset($_GET['callback'])) {       // JSONP, like getsuggest.php
    $callback = $_GET['callback'];
    // Only allow a safe JSONP callback identifier. Echoing the raw callback
    // is a reflected-XSS / JSONP-injection vector, so reject anything else.
    // (The whitelist is the real control: a JSONP body is served as JavaScript,
    // where htmlentities alone would not neutralise an arbitrary callback.)
    if (!preg_match('/^[A-Za-z_$][A-Za-z0-9_$.]{0,127}$/', $callback)) {
      header('content-type: text/plain; charset=utf-8');
      http_response_code(400);
      echo "invalid callback";
      return;
    }
    // htmlentities() is a no-op on the whitelisted charset; it adds
    // defence-in-depth and clears the Semgrep echoed-request taint sink.
    header('content-type: application/javascript; charset=utf-8');
    echo htmlentities($callback) . "($json)";
  } else {
    echo $json;
  }
}
saltIdsCall();
?>
