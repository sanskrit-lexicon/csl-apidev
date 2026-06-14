<?php
/*
 getword_list_1.0.php  Begun 06-01-2017.
 Used by Javascript 'simpleFunction' in list-0.2s_rw.php.
*/
require_once('getword_list_1.0_main.php');
$ans = getword_list_processone(); // Gets arguments from $_REQUEST
header("Access-Control-Allow-Origin: *");
header('content-type: application/json; charset=utf-8');

$json = json_encode($ans);
if (isset($_REQUEST['callback'])) {
 $callback = $_REQUEST['callback'];
 // Validate the JSONP callback before reflecting it: an unrestricted value is
 // a reflected-XSS / JSONP-injection vector. Mirrors the guard in
 // csl-websanlexicon webtc/getword.php (issue #27).
 if (!preg_match('/^[A-Za-z_$][A-Za-z0-9_$.]{0,127}$/',$callback)) {
  header('content-type: text/plain; charset=utf-8');
  http_response_code(400);
  echo "invalid callback";
  exit;
 }
 echo "{$callback}($json)";
}else {
 echo $json;
}

?>
