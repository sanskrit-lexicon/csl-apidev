<?php
//getword.php
#$dir = dirname(__FILE__); //directory containing this php file
# Note: $dir does not end in '/'
$dirutil = "utilities";
$transcoder = $dirutil ."/". "transcoder.php";
require_once($transcoder); // initializes transcoder
require_once("dal.php");  
include("disp.php");
require_once('dictinfo.php');

$filter0 = $_GET['filter'];
$filterin0 = $_GET['transLit']; // transLit
$keyin = $_GET['key'];
$keyin = trim($keyin); // remove leading and trailing whitespace
$dict = $_GET['dict'];
$accent = $_GET['accent']; 
if(!$accent) {$accent="no";}
if (! $keyin) { //Get args from command-line, for debugging
# php getword.php rAma slp1 slp1 mw72
 $keyin=$argv[1];
 if (! $keyin) {$keyin='a';};
 $filterin0 = $argv[2];
 $filter0 = $argv[3];
 $dict = $argv[4];
}

$filter = transcoder_standardize_filter($filter0);
$filterin = transcoder_standardize_filter($filterin0);

$dictinfo = new DictInfo($dict);
$webpath = $dictinfo->get_webPath();
$dal = new Dal($dict);
$dictup = $dictinfo->dictupper;

$english = in_array($dictup,array("AE","MWE","BOR")); // boolean flag

//echo "filterin0 = $filterin0, filterin = $filterin <br>\n";
//$key = transcoder_processString($keyin,$filterin,"slp1");
if ($english) {
 $keyin1 = $keyin;
 $key = $keyin;
}else {
 $keyin1 = preprocess_unicode_input($keyin,$filterin);
 $key = transcoder_processString($keyin1,$filterin,"slp1");
}
// $meta = "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">";
$meta = '<meta charset="UTF-8">';

$origkey = $key;

$more = True;
while ($more) {
 $results = $dal->get1($key); 
 $matches=array();
 $nmatches=0;
 foreach($results as $line) {
  list($key1,$lnum1,$data1) = $line;
  $matches[] = $line; # June 4, 2015
  $nmatches++;
 }
 if($nmatches > 0) {$more=False;break;}
 // try next shorter key:  Not sure this is good idea
  break;
 $n = strlen($key);
 if ($n > 1) {
  $key = substr($key,0,-1); // remove last character
 } else {
  $more=False;
 }
}
if ($nmatches == 0) {
 //echo "DBG: cmd1 = $cmd1\n";
 echo "$meta\n";
 echo "<h2>not found: '$key'</h2>\n";
 exit;
}
# accent-adjustment
for($i=0;$i<count($matches);$i++) {
 $matches[$i] = accent_adjust($matches[$i],$accent);
}
$table = basicDisplay($key,$matches,$filter,$dictup); // from disp.php
$table1 = transcoder_processElements($table,"slp1",$filter,"SA");
echo $table1;

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
function accent_adjust($line,$accent) {
 list($key,$lnum,$data) = $line;
 # in MW at least, <span class='sdata'><SA>$x</SA></span> we 
 if($accent == 'yes') {
  $new = preg_replace_callback("|<span class='sdata'><SA>(.*?)</SA></span>|",
         "accent_yes",$data);
 }else {
  //echo "<p>accent_no begins</p>";
  $new = preg_replace_callback("|<span class='sdata'><SA>(.*?)</SA></span>|",
         "accent_no",$data);
  //echo "<p>accent_no ends</p>";
  //$newx = preg_replace("|<|","&lt;",$new);
  //$newx = preg_replace("|>|","&gt;",$newx);
  //echo "<p>$newx</p>";
 }
 return array($key,$lnum,$new);
}
function accent_yes($matches) {
 $old = $matches[0];
 $new = preg_replace("|class='sdata'|","class='sdata_siddhanta'",$old);
 return $new;
}
function accent_no($matches) {
 $olddata = $matches[1];
 $newdata = preg_replace('|[\/\^\\\]|','',$olddata);
 $new = "<span class='sdata'><SA>$newdata</SA></span>";
 return $new;
}
?>
