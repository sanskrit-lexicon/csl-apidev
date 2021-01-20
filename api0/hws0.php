<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
?>
<?php
require_once('../api0_hws0Class.php');
/* hws0.php  api0
Parameters:
 dict: one of the dictionary codes (case insensitive)
 key: a partial sanskrit headword.  The spelling is determined by 'input'
 input: the spelling system used for 'key'. Default slp1
 output: the spelling system used for <SA>x</SA> in html
*/
// communicate  that we are in an 'api0' mode.
$_REQUEST['api0'] = true; 
require_once('../api0_hws0Class.php');
$temp = new api0_hws0Class();
$result = $temp->result;
if (! isset($_REQUEST['pretty'])){
 $json = json_encode($result);
 echo $json;
}else {
 hws0_prettyPrint($result);
}
function hws0_prettyPrint($result) {
 $a = $result;
 $status = $a['status'];
 echo "\n";
 echo "<br/>status: ${a['status']}\n";
 echo "<br/>errorinfo: ${a['errorinfo']}\n";
 echo "<br/>request: ";
 $request = $a['request'];
 foreach($request as $k => $v) {
   $out = "  '$k' : '$v'";
   $out = "<br/>&nbsp;&nbsp; $out";
   echo "$out\n";
 }
 $nmatches = $a['nmatches'];
 echo "<br/> nmatches: $nmatches\n";

 $matches = $a['matches'];
 echo "<br/> matches: \n";
 $imatch = 0;
 foreach($matches as $match0) {
  $imatch = $imatch + 1;
  $match = $match0;  # this is also an object
  echo "<br/>Match # $imatch: \n";
  foreach($match as $k => $v) {
   $out = "  '$k' : '$v'";
   # handle <>
   $out = preg_replace('/>/','&gt;',$out);
   $out = preg_replace('/</','&lt;',$out);
   $out = "<br/>&nbsp;&nbsp; $out";
   echo "$out\n";
  }
 }
}
?>
