<?php
/*
 getword_xml_v1.php
  06-01-2017. based on apidev/getword_xml.php
  'key' is treated as a comma-delimited list of keys
  Retrieves info for a given headword; retrieves from web/sqlite/<dict>.xml
  Enhancement:  retrieve multiple headwords
  Enhancement:  retrieve based on normalized spelling
*/
header("Access-Control-Allow-Origin: *");
header('content-type: application/json; charset=utf-8');
//if (isset($_GET['callback'])) {
//}
$dirpfx = "../../";
$dirpfx = "";
require_once($dirpfx . "utilities/transcoder.php"); // initializes transcoder
require_once($dirpfx . "dal.php");  

require_once($dirpfx . "parm.php");
$getParms = new Parm();

$dict = $getParms->dict;
$dal = new Dal($dict);


/* $matches is array. each element is 3-element array
  list($key1,$lnum1,$data1)
*/
$keyparm = $getParms->key;
$keyparmin = $getParms->keyin;
$ans = array();  // return associative array
$ans['dict']=$dict;
$ans['input']=$getParms->filterin;  //use of filterin is awkward
$ans['output']=$getParms->filter;
$ans['accent']=$getParms->accent;   # yes or no
$keys = explode(',',$keyparm); // slp1
$keysin = explode(',',$keyparmin);  // as per 'input' (e.g., hk)
$result = [];
for($ikey=0;$ikey<count($keys);$ikey++) {
 $key = $keys[$ikey];
 $keyin = $keysin[$ikey];
 $ans1 = [];
 $ans1['key'] = $key;
 $ans1['keyin'] = $keyin;
 // initialize xml and status for failure
 $ans1['xml'] = array("NOT FOUND");
 // ref: https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
 $ans1['status']=404;  // NOT FOUND use HTTP status codes. 404
 $matches= $dal->get1_xml($key); 
 $nmatches = count($matches);
 if ($nmatches > 0) {
  // reset xml, and status
  # accent-adjustment
  require_once($dirpfx . "accent_adjust.php");
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
  $ans1['xml']=$table1;
  $ans1['status']= 200; // OK
 }
 $result[] = $ans1;
}
$ans['result']=$result;
$json = json_encode($ans);
if (isset($_GET['callback'])) {
 echo "{$_GET['callback']}($json)";
}else {
 echo $json;
}


?>
