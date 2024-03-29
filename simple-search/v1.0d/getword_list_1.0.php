<?php
/*
 getword_list_0.1.php  
 Variant of getword_list_1.0.php of fetching_v0.3b.
 Designed to work with list-0.2s.html
  06-01-2017. based on apidev/getword_xml.php
  'key' is treated as a comma-delimited list of keys
  Retrieves info for a given headword; retrieves from web/sqlite/<dict>.xml
  Enhancement:  retrieve multiple headwords
  Enhancement:  retrieve based on normalized spelling
  06-02-2017. In this version, the variants are generated by php, rather
        than being pregenerated by javascript
  06-05-2017. Compute variants with SLP
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
