<?php
// Exclude WARNING messages also, to solve Peter Scharf Mac version.
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
?>
<?php
// salt_entries.php — Salt API: entries endpoint (C-SALT-compatible search).
// Entry point; mirrors getsuggest.php. Contract: doc/salt_entries.md
//
// This is the JSON REST face (/dicts/{id}/restful/entries). The human permalink
// face (/{DICT}/{ref}) and content negotiation are handled by cleanurl.php — see
// doc/cleanurl.md §0 and the reconciliation note in doc/salt_entries.md §1.7.
header("Access-Control-Allow-Origin: *");
header('content-type: application/json; charset=utf-8');
require_once(__DIR__ . '/salt_entriesClass.php');
function saltEntriesCall() {
  $temp = new SaltEntriesClass();
  $json = $temp->json;                  // {"data":{"entries":[...]}}
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
saltEntriesCall();
?>
