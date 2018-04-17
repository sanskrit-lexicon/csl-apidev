<?php
/* June 12, 2015getCommon.php */

$filter0 = $_GET['filter'];
$filterin0 = $_GET['transLit']; // transLit
$keyin = $_GET['key'];
$keyin = trim($keyin); // remove leading and trailing whitespace
$dict = $_GET['dict'];
$accent = $_GET['accent']; 
if(!$accent) {$accent="no";}
$dbg=false;
if ($dbg and (! $keyin)) { //Get args from command-line, for debugging
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

if ($english) {
 $keyin1 = $keyin;
 $key = $keyin1;  
}else {
 $keyin1 = preprocess_unicode_input($keyin,$filterin);
 $key = transcoder_processString($keyin1,$filterin,"slp1");
}

function preprocess_unicode_input($x,$filterin) {
 // when a unicode form is input in the citation field, for instance
 // rAma (where the unicode roman for 'A' is used), then,
 // the value present as 'keyin' is 'r%u0101ma' (a string with 9 characters!).
 // The transcoder functions assume a true unicode string, so keyin must be
 // altered.  This is what this function aims to accomplish.
 /* June 15, 2015 - try php urldecode */
// return urldecode($x);
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
