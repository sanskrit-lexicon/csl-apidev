<?php
error_reporting(E_ALL & ~E_NOTICE );
?>
<?php
/* servepdf.php  Apr 27, 2015 Multidictionary display of scanned images
  Similar to servepdf for the dictionaries
Parameters:
 dict: one of the dictionary codes (case insensitive)
 page: a specific page of the dictionary.  In the form of the contents
       of a <pc> element
 key: a headword, in SLP1.  
  Only one of 'page' and 'key' should be used.  If both are present, then
  'key' parameter is ignored and 'page' parameter prevails.
*/
if (isset($_GET['callback'])) {
 header('content-type: application/json; charset=utf-8');
}
header("Access-Control-Allow-Origin: *");
require_once('servepdfClass.php');

function servepdfCall() {
  $temp = new ServepdfClass();
  $table1 = $temp->html;
  if (isset($_GET['callback'])) {
   $json = json_encode($table1);
   echo "{$_GET['callback']}($json)";
  }else {
   echo $table1;
  }
}
servepdfCall();
?>

