<?php
 if (isset($argv[1])) {
  $dict = $argv[1];
 }else {
  $dict = "mw";
 }
 if (isset($argv[2])) {
  $keyparmin = $argv[2];
 }else {
  $keyparmin = "devi";
 }
 if (isset($argv[3])) {
  $inputparmin = $argv[3];
 }else {
  $inputparmin = "default";
 }

require_once('simple_search.php');
$ssobj = new Simple_Search($keyparmin,$inputparmin,$dict);
$ans = $ssobj->normkeys;
// print_r($ans);
$json = json_encode($ans);
echo $json . "\n";
?>
