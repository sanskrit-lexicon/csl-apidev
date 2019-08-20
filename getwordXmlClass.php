<?php
// Exclude WARNING messages also, to solve Peter Scharf Mac version.
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
?>
<?php
//getwordXmlClass.php

require_once("utilities/transcoder.php");
require_once("dal.php");  
require_once("parm.php");
require_once("accent_adjust.php");

class GetwordXmlClass {
 public $json;
 public function __construct() {
  $getParms = new Parm();
  
  $dict = $getParms->dict;
  $dal = new Dal($dict);
  
  /* $matches is array. each element is 3-element array
    list($key1,$lnum1,$data1)
  */
  $key = $getParms->key;
  $ans = array();  // returned associative array
  $ans['dict']=$dict;
  $ans['input']=$getParms->filterin; 
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
  $this->json = $json;
 }
}
?>
 