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
  // JSONP, like getsuggest.php — but only wrap when the callback name is a safe
  // JS identifier. The whitelist is the real control: a JSONP body is served as
  // JavaScript, so an arbitrary $_GET['callback'] is a reflected-XSS vector.
  // htmlentities() adds defence-in-depth (and clears the Semgrep taint sink);
  // it is a no-op on the whitelisted charset.
  $callback = isset($_GET['callback']) ? (string)$_GET['callback'] : '';
  if ($callback !== '' && preg_match('/^[A-Za-z_$][A-Za-z0-9_$.]*$/', $callback)) {
    header('content-type: application/javascript; charset=utf-8');
    echo htmlentities($callback) . "($json)";
  } else {
    echo $json;
  }
}
saltIdsCall();
?>
