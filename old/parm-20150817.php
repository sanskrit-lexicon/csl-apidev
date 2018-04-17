<?php
/* parm.php  Jul 10, 2015  Contains Parm class, which
  converts various $_GET parameters into member attributes. 
  $_GET   Parm attribute   Related attribute
  filter  filter0          filter
  transLit filterin0       filterin
  key     keyin            keyin1, key
  dict    dict             dictinfo
  accent  accent
 Aug 4, 2015 - synonym for $_GET:
  input == transLit
  output == filter
*/
require_once('utilities/transcoder.php'); // initializes transcoder
require_once('dictinfo.php');
require_once('dbgprint.php');
class Parm {
 public $filter0,$filterin0,$keyin,$dict,$accent;
 public $filter,$filerin;
 public $dictinfo,$english;
 public $keyin1,$key;
 public function __construct() {
  $dbg=false;
  dbgprint($dbg,"enter parm construct\n");
  if ($_GET['filter']) {
   $this->filter0 = $_GET['filter'];
  }else{
   $this->filter0 = $_GET['output'];
  }
  if ($_GET['transLit']) {
   $this->filterin0 = $_GET['transLit']; 
  }else {
   $this->filterin0 = $_GET['input']; 
  }
  $this->keyin = $_GET['key'];
  $this->keyin = trim($this->keyin); // remove leading and trailing whitespace
  $this->dict = $_GET['dict'];
  $this->accent = $_GET['accent']; 

  if(!$this->accent) {$this->accent="no";}

  $this->filter = transcoder_standardize_filter($this->filter0);
  $this->filterin = transcoder_standardize_filter($this->filterin0);

  $this->dictinfo = new DictInfo($this->dict);
  $this->english = $this->dictinfo->english;
  if ($this->english) {
   $this->keyin1 = $this->keyin;
   $this->key = $this->keyin1;  
  }else {
   $this->keyin1 = preprocess_unicode_input($this->keyin,$this->filterin);
   $this->key = transcoder_processString($this->keyin1,$this->filterin,"slp1");
  }
 dbgprint($dbg,"parm construct keyin, keyin1, key = {$this->keyin},{$this->keyin1}, {$this->key}\n");
 dbgprint($dbg,"leave parm construct\n");

 }  
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
