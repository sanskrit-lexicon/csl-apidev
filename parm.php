<?php
/* parm.php  Jul 10, 2015  Contains Parm class, which
  converts various $_REQUEST parameters into class attributes. 
  $_REQUEST   Parm attribute 
  filter,output  filter0     
  transLit,input filterin0    
  key     keyin (trimmed)     
  dict    dict  (lowercase)  
  accent  accent (values are 'yes' or 'no')
  dispcss dispcss (values are 'yes' or 'no'):
      Sep 2, 2018. Add 'dispcss' optional parm. If value = 'no', then
      disp.php does NOT output a <link> css statement.
      Default value is 'yes', meaning this link is included.
  Additional Parm attributes:
  filter   standardized from filter0
  filterin standardized from filterin0
  dictinfo instance of DictInfo class
  english  copy of dictinfo's english attribute
  keyin1   from keyin   
  key      from keyin1 
 
*/
require_once('utilities/transcoder.php'); // initializes transcoder
require_once('dictinfo.php');
require_once('dbgprint.php');
class Parm {
 public $filter0,$filterin0,$keyin,$dict,$accent;
 public $filter,$filterin;
 public $dictinfo,$english;
 public $keyin1,$key;
 public function __construct() {
  $dbg=false;
  dbgprint($dbg,"enter parm construct\n");
  if ($_REQUEST['filter']) {
   $this->filter0 = $_REQUEST['filter'];
  }else{
   $this->filter0 = $_REQUEST['output'];
  }
  if ($_REQUEST['transLit']) {
   $this->filterin0 = $_REQUEST['transLit']; 
  }else {
   $this->filterin0 = $_REQUEST['input']; 
  }
  $this->dict = $_REQUEST['dict'];
  // some places expect dict to be lower case.
  $this->dict = strtolower($this->dict);
  $this->accent = $_REQUEST['accent']; 

  if(!$this->accent) {$this->accent="no";}

  $this->filter = transcoder_standardize_filter($this->filter0);
  $this->filterin = transcoder_standardize_filter($this->filterin0);
  dbgprint($dbg,"parm.php. filter0={$this->filter0}, filter={$this->filter}\n");

  $this->dictinfo = new DictInfo($this->dict);
  $this->english = $this->dictinfo->english;
  list($this->keyin,$this->keyin1,$this->key) = $this->compute_text('key');
  
  $this->compute_dispcss(); 

  dbgprint($dbg,"parm construct keyin = {$this->keyin}\n");
  dbgprint($dbg,"parm construct keyin1 = {$this->keyin1}\n");
  dbgprint($dbg,"parm construct key = {$this->key}\n");
  dbgprint($dbg,"leave parm construct\n");
 }  

 public function compute_text($code) {
  // uses public variables $this->english, $this->filterin
  $keyin = $_REQUEST[$code];
  $keyin = trim($keyin); // remove leading and trailing whitespace
  if ($this->english) {
   $keyin1 = $keyin;
   $key = $keyin1;  
  }else {
   $keyin1 = $this -> preprocess_unicode_input($keyin,$this->filterin);
   $key = transcoder_processString($keyin1,$this->filterin,"slp1");
  }
  return array($keyin,$keyin1,$key);
 }

 public function compute_dispcss() {
  $this->dispcss = $_REQUEST['dispcss'];
  if (!$this->dispcss) {
   $this->dispcss = 'yes';
  }else if ($this->dispcss != 'no') {
   $this->dispcss = 'yes';
  }
 }
 public function preprocess_unicode_input($x,$filterin) {
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
      "Parm::preprocess_unicode_callback_hex",$x1);
  return $ans;
 }
 public function preprocess_unicode_callback_hex($matches) {
  $x = $matches[2]; // 4 hex digits
  $y = unichr(hexdec($x));
  return $y;
 }
} 
?>
 