<?php
//getword_xml.php
// Retrieves info for a given headword; retrieves from web/sqlite/<dict>.xml
// Enhancement:  retrieve multiple headwords
// Enhancement:  retrieve based on normalized spelling

header('content-type: application/json; charset=utf-8');
if (isset($_GET['callback'])) {
 header("Access-Control-Allow-Origin: *");
}
require_once('utilities/transcoder.php'); // initializes transcoder
require_once("dal.php");  

require_once('parm.php');
$getParms = new Parm();

$dict = $getParms->dict;
$dal = new Dal($dict);


/* $matches is array. each element is 3-element array
  list($key1,$lnum1,$data1)
*/
$key = $getParms->key;
$ans = array();  // return associative array
$ans['dict']=$dict;
$ans['input']=$getParms->filterin;  //use of filterin is awkward
$ans['output']=$getParms->filter;
$ans['key'] = $key;
$ans['accent']=$getParms->accent;   # yes or no
// initialize xml and status for failure
$ans['xml'] = array("NOT FOUND");
// ref: https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
$ans['status']=404;  // NOT FOUND use HTTP status codes. 404
$matches= $dal->get1_xml($key); 
$nmatches = count($matches);
if ($nmatches > 0) {
 // reset xml, and status
 # accent-adjustment
 require_once("accent_adjust.php");
 $dictinfo = $getParms->dictinfo;
 $dictup = $dictinfo->dictupper;
 $accent = $getParms->accent;
 $dictinfo = $getParms->dictinfo;
 $dictup  = $dictinfo->dictupper;
 
 $filter = $getParms->filter;
 $table1 = array();
 for($i=0;$i<count($matches);$i++) {
  $rec = $matches[$i]; //($m['key'],$m['lnum'],$m['data'])
  # it is awkward to have accent_adjust operate on $rec. better on $rec[2]
  $rec1 = accent_adjust($rec,$accent,$dictup);
  $d = $rec1[2]; // data: the xml record for this entry, adjusted for accent
  if (! ($getParms->english)) {
   $d = preg_replace('|<key1>(.*?)</key1>|',"<key1><s>$1</s></key1>",$d);
   $d = preg_replace('|<key2>(.*?)</key2>|',"<key2><s>$1</s></key2>",$d);
   $x = transcoder_processElements($d,"slp1",$filter,"s");
  }else {
   $x = $d;
  }
  $table1[$i]=$x;
 }
 $ans['xml']=$table1;
 $ans['status']= 200; // OK
}
$json = json_encode($ans);
if (isset($_GET['callback'])) {
 echo "{$_GET['callback']}($json)";
}else {
 echo $json;
}


?>
