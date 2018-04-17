<?php
//getword.php
if (isset($_GET['callback'])) {
 header('content-type: application/json; charset=utf-8');
 header("Access-Control-Allow-Origin: *");
}
$dirutil = "utilities";
$transcoder = $dirutil ."/". "transcoder.php";
require_once($transcoder); // initializes transcoder
require_once("dal.php");  
include("disp.php");
require_once('dictinfo.php');
// June 11, 2015.  options controls some aspects of basicDisplay.
$options = $_GET['options'];  
require_once('getCommon.php');


$meta = '<meta charset="UTF-8">';

/* $matches is array. each element is 3-element array
  list($key1,$lnum1,$data1)
*/
$matches= $dal->get1($key); 
$nmatches = count($matches);
if ($nmatches == 0) {
 //echo "DBG: cmd1 = $cmd1\n";
 echo "$meta\n";
 echo "<h2>not found: '$key'</h2>\n";
 exit;
}
# accent-adjustment
require_once("accent_adjust.php");
for($i=0;$i<count($matches);$i++) {
 $matches[$i] = accent_adjust($matches[$i],$accent,$dictup);
}

$table = basicDisplay($key,$matches,$dictup,$options); // from disp.php

$table1 = transcoder_processElements($table,"slp1",$filter,"SA");
if (isset($_GET['callback'])) {
 $json = json_encode($table1);
 echo "{$_GET['callback']}($json)";
}else {
 echo $table1;
}


?>
