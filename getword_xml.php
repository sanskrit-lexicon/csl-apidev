<?php
// Exclude WARNING messages also, to solve Peter Scharf Mac version.
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
?>
<?php
//getword_xml.php
// Retrieves info for a given headword; retrieves from web/sqlite/<dict>.xml

if (isset($_GET['callback'])) {
 header('content-type: application/json; charset=utf-8');
}
header("Access-Control-Allow-Origin: *");
require_once("getwordXmlClass.php");

function getwordXmlCall() {
  $temp = new GetwordXmlClass();
  $json = $temp->json;
  if (isset($_GET['callback'])) {
   echo "{$_GET['callback']}($json)";
  }else {
   echo $json;
  }
}
getwordXmlCall();
