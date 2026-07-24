<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
?>
<?php
require_once('../api0_getsuggestClass.php');
/* getsuggest.php  api0
Parameters:
 dict: one of the dictionary codes (case insensitive)
 term: a partial sanskrit headword.  The spelling is determined by 'input'
 input: the spelling system used for 'term'.
*/
// communicate to api0_getsuggestClass and other classes that we
// are in an 'api0' mode.
$_REQUEST['api0'] = true; 
require_once('../api0_getsuggestClass.php');
$temp = new api0_getsuggestClass();
$result = $temp->result;
if (! isset($_REQUEST['pretty'])){
 $json = json_encode($result);
 echo $json;
}else {
 // H1523: pretty mode emits HTML; escape request/errorinfo/matches.
 $esc = function ($v) {
  return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
 };
 $a = $result;
 $status = $a['status'];
 echo "\n";
 echo "<br/>status: " . $esc($a['status']) . "\n";
 echo "<br/>errorinfo: " . $esc($a['errorinfo']) . "\n";
 echo "<br/>request: ";
 $request = $a['request'];
 foreach($request as $k => $v) {
   $out = "  '" . $esc($k) . "' : '" . $esc($v) . "'";
   $out = "<br/>&nbsp;&nbsp; $out";
   echo "$out\n";
 }
 $nmatches = $a['nmatches'];
 echo "<br/> nmatches: " . $esc($nmatches) . "\n";

 $matches = $a['matches'];
 echo "<br/> matches: \n";
 $imatch = 0;
 foreach($matches as $match) {
  $imatch = $imatch + 1;
  echo "<br/>Match # " . $esc($imatch) . ": " . $esc($match) . "\n";
 }

}
?>
