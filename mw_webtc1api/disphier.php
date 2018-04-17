<?php
$dir = dirname(__FILE__); //directory containing this php file
require_once('../utilities/transcoder.php');
require_once('../webtc/dal_sqlite.php');
require_once('displistCommon.php');
include("../webtc/disp.php");
disphier_main();

function disphier_main() {
// use relative pathnames for the sqlite databases (see $db=.. below)
// interpret GET parameters.
$accent = $_GET['accent'];  // June 2014
// 'key'
$keyin = $_GET['key'];
if (! $keyin) {$keyin='a';};
// new style
 list($filter ,$filterin ) = getParameters_keyboard();

$keyin1 = preprocess_unicode_input($keyin,$filterin);
$key = transcoder_processString($keyin1,$filterin,"slp1");

$meta = "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">";
echo "$meta\n";
//echo "<p>DBG: keyin=$keyin, filter=$filter, filterin=$filterin, keyin1=$keyin1, key=$key</p>";

$more = True;
$origkey = $key;
while ($more) {
 $results = dal_mw1($key); 
 $matches=array();
 $nmatches=0;
  foreach($results as $line) {
  list($key1,$lnum1,$data1) = $line;
  $matches[$nmatches]=$data1;
  $nmatches++;
 }
 if($nmatches > 0) {$more=False;break;}
 // try next shorter key
 $n = strlen($key);
 if ($n > 1) {
  $key = substr($key,0,-1); // remove last character
 } else {
  $more=False;
 }
}
if ($nmatches == 0) {
 echo "<h2>not found: $keyin</h2>\n";
 //echo "<h3> dbg: key = $key</h3>\n";

  $out1 = "<SA>$key</SA>";
  $out = transcoder_processElements($out1,"slp1",$filter,"SA");
 echo "<h1>&nbsp;$out</h1>\n";
 exit;
}

basicDisplaySetAccent($accent);
$table = basicDisplay($key,$matches,$filter );
$table1 = transcoder_processElements($table,"slp1",$filter,"SA");
echo $table1;
}

?>