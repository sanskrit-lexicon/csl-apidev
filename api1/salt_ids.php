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
  // JS identifier, else the reflected name is a cross-site scripting vector.
  $callback = isset($_GET['callback']) ? (string)$_GET['callback'] : '';
  if ($callback !== '' && preg_match('/^[A-Za-z_$][A-Za-z0-9_$.]*$/', $callback)) {
    header('content-type: application/javascript; charset=utf-8');
    echo "{$callback}($json)";
  } else {
    echo $json;
  }
}
saltIdsCall();
?>
