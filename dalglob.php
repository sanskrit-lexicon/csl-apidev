<?php
require_once(__DIR__ . '/security_headers.php');
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
   $callback = $_GET['callback'];
   // Only allow a safe JSONP callback identifier. Echoing the raw callback
   // is a reflected-XSS / JSONP-injection vector, so reject anything else.
   if (!preg_match('/^[A-Za-z_$][A-Za-z0-9_$.]{0,127}$/',$callback)) {
    header('content-type: text/plain; charset=utf-8');
    http_response_code(400);
    echo "invalid callback";
    return;
   }
   echo htmlentities($callback) . "($json)";
   //dbgprint(true,"dalglobCall: Returned json\n");
  }else {
   echo $json;
   //dbgprint(true,"dalglobCall: returned php\n");
  }
 }
 dalglobCall();

?>
