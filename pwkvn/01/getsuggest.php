<?php
// Exclude WARNING messages also, to solve Peter Scharf Mac version.
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
?>
<?php
//getsuggest.php
header("Access-Control-Allow-Origin: *");
header('content-type: application/json; charset=utf-8');
require_once('getsuggestClass.php');
function getsuggestCall() {
 $temp = new GetsuggestClass();
 $json = $temp->json;
 /* Next for JSONP
  Ref: //www.geekality.net/2010/06/27/php-how-to-easily-provide-json-and-jsonp/
 */
 if (isset($_GET['callback'])) {
  echo "{$_GET['callback']}($json)";
 }else {
  echo $json;
 }
}
getsuggestCall();
?>
