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
  if (isset($_GET['callback'])) {       // JSONP, like getsuggest.php
    echo "{$_GET['callback']}($json)";
  } else {
    echo $json;
  }
}
saltEntriesCall();
?>
