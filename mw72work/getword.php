<?php
//web/webtc/getword.php
$dir = dirname(__FILE__); //directory containing this php file
// Note: $dir does not end in '/'
$dir = "$dir/";
$dir1 = $dir . '../utilities/';
$dirphp = realpath($dir1);
$pathutil = $dirphp ."/". 'transcoder.php';
require_once($pathutil); // initializes transcoder
require_once("dal_sqlite.php");  
include("disp.php");

$filter0 = $_GET['filter'];
$filterin0 = $_GET['transLit']; // transLit
$keyin = $_GET['key'];
if (! $keyin) {$keyin=$argv[1];}
if (! $keyin) {$keyin='a';};
if (! $filterin0) {$filterin0 = $argv[2];}
if (! $filter0) {$filter0 = $argv[3];}

$filter = transcoder_standardize_filter($filter0);
$filterin = transcoder_standardize_filter($filterin0);
//echo "filterin0 = $filterin0, filterin = $filterin <br>\n";
//$key = transcoder_processString($keyin,$filterin,"slp1");
$keyin1 = preprocess_unicode_input($keyin,$filterin);
$key = transcoder_processString($keyin1,$filterin,"slp1");
$meta = "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">";
echo "$meta\n";
echo "<!--filter=$filter<br>,filterin=$filterin-->\n";
$more = True;
$origkey = $key;
while ($more) {
 $results = dal_mw721($key); 
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
 //echo "DBG: cmd1 = $cmd1\n";
 echo "<h2>not found: $keyin</h2>\n";
 //echo "<h3> dbg: key = $key</h3>\n";
  $out1 = "<SA>$key</SA>";
  $out = transcoder_processElements($out1,"slp1",$filter,"SA");
 echo "<h1>&nbsp;$out</h1>\n";
 exit;
}
$dbg=False;
// transcoder_set_htmlentities(True); // TEST Dec 5, 2013
$table = basicDisplay($key,$matches,$filter); // from disp.php
$table1 = transcoder_processElements($table,"slp1",$filter,"SA");
//$table2 = preg_replace('|#x|','&#x',$table1);
if ($dbg) {
echo $table1;
echo "<hr/>";
echo "<p>DBG</p>";
}
echo $table1;
if ($dbg) {
 echo "<!-- getword dbg:\n";
 echo "table = \n$table\n\n";
 echo "table1, with filter=$filter, = \n$table1\n\n";
 echo "-->\n";
}
exit;
function preprocess_unicode_input($x,$filterin) {
 // when a unicode form is input in the citation field, for instance
 // rAma (where the unicode roman for 'A' is used), then,
 // the value present as 'keyin' is 'r%u0101ma' (a string with 9 characters!).
 // The transcoder functions assume a true unicode string, so keyin must be
 // altered.  This is what this function aims to accomplish.
 $hex = "0123456789abcdefABCDEF";
 $x1 = $x;
 if ($filterin == 'roman') {
  $x1 = preg_replace("/\xf1/","%u00f1",$x);
 }
 $ans = preg_replace_callback("/(%u)([$hex][$hex][$hex][$hex])/",
     "preprocess_unicode_callback_hex",$x1);
 return $ans;
}
function preprocess_unicode_callback_hex($matches) {
 $x = $matches[2]; // 4 hex digits
 $y = unichr(hexdec($x));
 return $y;
}

?>
