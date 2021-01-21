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
 echo "{$_REQUEST['callback']}($json)";
}else {
 echo $json;
}

?>
