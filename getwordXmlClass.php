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
require_once("basic_xml_html.php");
require_once("dbgprint.php"); 
class GetwordXmlClass {
 public $json;
 public $dbg;
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
  $ans['xml'] = array(); #array("NOT FOUND"); changed 12/31/2019
  $ans['html'] = array();
  // ref: https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  $ans['status']=404;  // NOT FOUND use HTTP status codes. 404
  if(isset($_REQUEST['lnum'])) { # 12-31-2019. for api
   $lnum = $_REQUEST['lnum']; 
   $matches = $dal->get2($lnum,$lnum);
  }else if (isset($_REQUEST['regex'])) { 
   $regex = $_REQUEST['regex']; 
   # for sqlite regex
   $sqlite_regex = str_replace("*","%",$regex);
   $sqlite_regex = preg_replace('/\?/','_',$sqlite_regex);
   $max = 100;   ## throttle
   $matches = $dal->get3b($sqlite_regex,$max);
  }else {
   $matches= $dal->get1_xml($key); 
  }
  $nmatches = count($matches);
  
  dbgprint($this->dbg,"getwordXmlClass: dict=$dict, key=$key, #matches=$nmatches\n");
  if ($nmatches > 0) {
   // reset xml, and status
   $dictinfo = $getParms->dictinfo;
   $dictup = $dictinfo->dictupper;
   $accent = $getParms->accent;
   $dictinfo = $getParms->dictinfo;
   $dictup  = $dictinfo->dictupper;
   
   $filter = $getParms->filter;
   $table1 = array(); // xml
   $table2 = array(); // html
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
    $temp = new Basic_xml_html($rec,$dict,$getParms);
    $html = $temp->html;
    $htmla = transcoder_processElements($html,"slp1",$filter,"SA");
    $table2[$i] = $htmla;
   }
   $ans['xml']=$table1;
   $ans['html']=$table2;
   $ans['status']= 200; // OK
  }
  $json = json_encode($ans);
  $this->json = $json;
 }
 public function get_html($rec,$dict,$getParms) {
  // rec: ($m['key'],$m['lnum'],$m['data'])
  list($key0,$lnum0,$data0) = $rec;
  $html = $this->getword_data_html_adapter($key0,$lnum0,$data0,$dict,$getParms);
  return $html;
 }
}
?>
 