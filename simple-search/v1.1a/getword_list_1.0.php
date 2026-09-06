<?php
require_once(__DIR__ . '/../../security_headers.php');
/*
 getword_list_1.0.php  Begun 06-01-2017.
 Used by Javascript 'simpleFunction' in list-0.2s_rw.php.
*/
require_once('getword_list_1.0_main.php');
require_once(__DIR__ . '/../../jsonp_callback_guard.php');
$ans = getword_list_processone(); // Gets arguments from $_REQUEST
header("Access-Control-Allow-Origin: *");
header('content-type: application/json; charset=utf-8');

$json = json_encode($ans);
if (isset($_REQUEST['callback'])) {
 // Shared whitelist+reply guard (H4212); htmlspecialchars became a no-op
 // on the whitelisted charset, so consolidating to the helper's htmlentities
 // is behavior-identical.
 if (!jsonp_reply($_REQUEST['callback'], $json)) {
  exit;
 }
}else {
 echo $json;
}

?>
