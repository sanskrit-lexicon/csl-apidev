<?php
// H1523: baseline headers + CSP-Report-Only (parity with other api0 entry points)
require_once(__DIR__ . '/../security_headers.php');
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
try {
 $temp = new api0_hws0Class();
} catch (Throwable $e) {
 // H3636 A10: data anomalies now throw; serve a truthful 500 envelope.
 http_response_code(500);
 header('content-type: application/json; charset=utf-8');
 echo json_encode(array('status' => 500, 'error' => $e->getMessage()));
 return;
}
$result = $temp->result;
// H3636 A19: a not-found result must not masquerade as HTTP 200.
if (isset($result['status']) && $result['status'] != 200) {
 http_response_code($result['status']);
}
if (! isset($_REQUEST['pretty'])){
 $json = json_encode($result);
 echo $json;
}else {
 hws0_prettyPrint($result);
}
function hws0_prettyPrint($result) {
 // H1523: pretty mode emits HTML; htmlspecialchars covers <>&"' (replaces
 // the prior partial preg_replace of only < and > on match fields).
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
 foreach($matches as $match0) {
  $imatch = $imatch + 1;
  $match = $match0;  # this is also an object
  echo "<br/>Match # " . $esc($imatch) . ": \n";
  foreach($match as $k => $v) {
   $out = "  '" . $esc($k) . "' : '" . $esc($v) . "'";
   $out = "<br/>&nbsp;&nbsp; $out";
   echo "$out\n";
  }
 }
}
?>
