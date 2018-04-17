<?php
//getword.php
// Jul 11 2015
if (isset($_GET['callback'])) {
 header('content-type: application/json; charset=utf-8');
 header("Access-Control-Allow-Origin: *");
}
require_once('utilities/transcoder.php'); // initializes transcoder
require_once("dal.php");  
include("disp.php");

require_once('parm.php');
$getParms = new Parm();
// June 11, 2015.  options controls some aspects of basicDisplay.
// July 11, 2015. Should be considered obsolete.
// Some sample displays require adjustment
$options = $_GET['options'];  
$getParms->options=$options;  // temporary?

$dict = $getParms->dict;
$dal = new Dal($dict);

$meta = '<meta charset="UTF-8">';

/* $matches is array. each element is 3-element array
  list($key1,$lnum1,$data1)
*/
$key = $getParms->key;
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
$dictinfo = $getParms->dictinfo;
$dictup = $dictinfo->dictupper;
$accent = $getParms->accent;
for($i=0;$i<count($matches);$i++) {
 $matches[$i] = accent_adjust($matches[$i],$accent,$dictup);
}

$dictinfo = $getParms->dictinfo;
$dictup  = $dictinfo->dictupper;

$table = basicDisplay($getParms,$matches); // from disp.php
//$table = basicDisplay($key,$matches,$dictup,$options); // from disp.php

$filter = $getParms->filter;
$table1 = transcoder_processElements($table,"slp1",$filter,"SA");
if (isset($_GET['callback'])) {
 $json = json_encode($table1);
 echo "{$_GET['callback']}($json)";
}else {
 echo $table1;
}


?>
