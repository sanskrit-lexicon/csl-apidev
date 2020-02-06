<?php
/* dalglob.php  Feb 2, 2020.  sqlite files that are 'global', i.e. not
   tied to a dictionary
*/
#require_once('dictinfo.php');
require_once('dbgprint.php');
if (isset($_GET['callback'])) {
 header('content-type: application/json; charset=utf-8');
}
header("Access-Control-Allow-Origin: *");
require_once("dalglobClass.php");
function dalglobCall() {
  $dal = new Dalglob();
  $ans = $dal->ans;
  $json = json_encode($ans);
  if (isset($_GET['callback'])) {
   echo "{$_GET['callback']}($json)";
   //dbgprint(true,"dalglobCall: Returned json\n");
  }else {
   echo $json;
   //dbgprint(true,"dalglobCall: returned php\n");
  }
 }
 dalglobCall();

?>
