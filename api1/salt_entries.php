<?php
require_once(__DIR__ . '/../security_headers.php');
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
saltEntriesCall();
?>
