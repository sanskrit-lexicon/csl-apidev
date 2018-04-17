<?php
require_once("dal.php"); 
require_once("dispitem.php");
//require_once("getword_mwalt.php");
$key = $argv[1];
$dict='mw';
$dal = new Dal($dict);
//$recs = get1_mwalt($dal,$key);
$recs = $dal->get1_mwalt($key);
// display results
$nrecs=count($recs);
for($i=0;$i<$nrecs;$i++) {
 $rec = $recs[$i];
 $dispitem = new DispItem($dict,$rec);
 $key1 = $dispitem->key;
 $lnum = $dispitem->lnum;
 $hcode = $dispitem->hcode;
 /*
 if($dispitem->newrec) {
  $new = " NEW";
 }else {
  $new = "";
 }
 */
 $new = "";
 $j = $i+1;
 echo "$j $hcode $lnum $key1$new\n";
}

?>
