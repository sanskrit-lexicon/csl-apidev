<?php
// H1523: baseline headers + CSP-Report-Only (parity with root servepdf.php)
require_once(__DIR__ . '/../security_headers.php');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
?>
<?php
/* servepdf.php  api0
Parameters:
 dict: one of the dictionary codes (case insensitive)
 page: a specific page of the dictionary.  In the form of the contents
       of a <pc> element
 key: a headword, in SLP1.  
  Only one of 'page' and 'key' should be used.  If both are present, then
  'key' parameter is ignored and 'page' parameter prevails.
*/
$_REQUEST['api'] = true; // communicate this to servepdfClass
require_once('../servepdfClass.php');
$temp = new ServepdfClass();
$json = $temp->json;
if (! isset($_REQUEST['pretty'])){
 echo $json;
}else {
 // H1523: pretty mode emits HTML; escape every dynamic field (request
 // values / errorinfo / link fields are user- or path-influenced).
 $esc = function ($v) {
  return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
 };
 // H1523: json_encode failure / empty payload → null; do not index null
 $a = json_decode($json,true);  // true indicates associative
 if (!is_array($a)) {
  echo "\n<br/>status = 500\n";
  echo "<br/>errorinfo = " . $esc('servepdf pretty: invalid JSON payload') . "\n";
  return;
 }

 echo "\n";
 echo "<br/>status = " . $esc(isset($a['status']) ? $a['status'] : '') . "\n";
 echo "<br/>errorinfo = " . $esc(isset($a['errorinfo']) ? $a['errorinfo'] : '') . "\n";
 echo "<br/>request: ";
 $request = (isset($a['request']) && is_array($a['request'])) ? $a['request'] : array();
 foreach($request as $k => $v) {
   $out = "  '" . $esc($k) . "' : '" . $esc($v) . "'";
   $out = "<br/>&nbsp;&nbsp; $out";
   echo "$out\n";
 }
 $links = (isset($a['links']) && is_array($a['links'])) ? $a['links'] : array();
 $nlinks = count($links);
 echo "<br/>links: (" . $esc($nlinks) . ")\n";
 $ilink = 0;
 foreach($links as $link) {
  $ilink = $ilink + 1;
  echo "<br/>Link # " . $esc($ilink) . ": ";
  if (!is_array($link)) { continue; }
  foreach($link as $k => $v) {
   $out = "  '" . $esc($k) . "' : '" . $esc($v) . "'";
   $out = "<br/>&nbsp;&nbsp; $out";
   echo "$out\n";
  }
 }

}
?>
