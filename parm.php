<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
?>
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
  basicOption Set to False, for apidev display. (GetwordClass)
  lnumin, direction  Computed upon request by listhierParm method
  status   200 or 404.
*/
require_once('utilities/transcoder.php'); // initializes transcoder
require_once('dictinfo.php');
require_once('dbgprint.php');
class Parm {
 public $filter0,$filterin0,$keyin,$dict,$accent;
 public $filter,$filterin;
 public $dictinfo,$english;
 public $keyin1,$key;
 public $status,$errorinfo;
 public $getsuggestTerm,$basicOption,$dispcss,$lnumin,$direction;
 public function __construct() {
  $this->status = 200;  // assume all is ok.
  $this->basicOption = false;
  $dbg=false;
  dbgprint($dbg,"enter parm construct\n");
  if (isset($_REQUEST['filter'])) {
   $this->filter0 = $_REQUEST['filter'];
  }else if(isset($_REQUEST['output'])){
   $this->filter0 = $_REQUEST['output'];
  }else {
   $this->filter0 = 'deva';
  }
  if (isset($_REQUEST['transLit'])) {
   $this->filterin0 = $_REQUEST['transLit']; 
  }else if (isset($_REQUEST['input'])){
   $this->filterin0 = $_REQUEST['input']; 
  }else {
   $this->filterin0 = 'hk';
  }
  if (isset($_REQUEST['dict'])) {
   $this->dict = $_REQUEST['dict'];
  } else {
   $this->dict = 'mw';
  }
  // some places expect dict to be lower case.
  $this->dict = strtolower($this->dict);
  if (isset($_REQUEST['accent'])) {
   $accent = $_REQUEST['accent'];
  }else{
   $accent = '';
  }
  $accent = strtolower($accent);
  if ($accent != 'yes') {
   $accent = 'no';
  }
  $this->accent = $accent;

  if(!$this->accent) {$this->accent="no";}

  $this->filter = transcoder_standardize_filter($this->filter0);
  $this->filterin = transcoder_standardize_filter($this->filterin0);
  dbgprint($dbg,"parm.php. filter0={$this->filter0}, filter={$this->filter}\n");

  $this->dictinfo = new DictInfo($this->dict);
  $this->english = $this->dictinfo->english;
  if (isset($_REQUEST['key'])) {
   $tempkey = $_REQUEST['key'];
   $tempkey = $this->init_inputs_key($tempkey);
  }else {
   $tempkey = 'guru';  // arbitrary
  }
  dbgprint($dbg,"parm.php. tempkey=$tempkey\n");
  //dbgprint($dbg,"  REQUEST['key']= " . $_REQUEST['key'] . "\n");
  list($this->keyin,$this->keyin1,$this->key) = $this->compute_text($tempkey);
  // check for validity of 'dict'
  #if (! isset($this->dictinfo->dictyear[$this->dictinfo->dictupper])) {
  if ($this->dictinfo->dictstatus != 200) {
   $this->status = 404; // error
   $this->errorinfo = "ERROR: " . $this->dictinfo->dicterr;
  }
  $this->compute_dispcss(); 

  dbgprint($dbg,"parm construct keyin = {$this->keyin}\n");
  dbgprint($dbg,"parm construct keyin1 = {$this->keyin1}\n");
  dbgprint($dbg,"parm construct key = {$this->key}\n");
  dbgprint($dbg,"leave parm construct\n");
 }
 public function init_inputs_key($x) {
 // word = citation.
 $ans = "";
 // $invalid_characters = array("$", "%", "#", "<", ">", "=", "(", ")", '"');
 /* keys with extended ascii (e.g. roman or deva) are, at this point in the
   code, strings with '%' included. Thus, "%" is NOT an invalid character
 */
 $invalid_characters = array("$", "#", "<", ">", "=", "(", ")", '"');
 $ans = str_replace($invalid_characters, "", $x);
 return $ans;
}
 public function getsuggestParms() {
  if (! isset($_REQUEST['term'])) {
   $this->getsuggestTerm = '';
  } else {
   $this->getsuggestTerm = $_REQUEST['term'];
  }
  $term = $this->getsuggestTerm;
  return $this->compute_text($term);
 }

 public function servepdfParms() {
  if (isset($_REQUEST['page'])) {
   $page = $_REQUEST['page'];
  } else {
   //$page = '1'; // arbitrary
   $page = false; // 03-12-2021. Used oly by serepdfdClass.
  }
  return array($page,$this->keyin);
 }

 public function compute_text($request_value) {
  // uses public variables $this->english, $this->filterin
  // Computes and returns three forms from $request_value
  // Known usages: 
  // $_REQUEST['key'] (from Parm constructor)
  // $_REQUEST['term'] (from GetsuggestClass constructor)

  $keyin = $request_value;
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
  if (isset($_REQUEST['dispcss'])) {
   $dispcss = $_REQUEST['dispcss'];
  }else {
   $dispcss = 'yes';
  }
  if ($dispcss != 'no') {
   $dispcss = 'yes';
  }
  $this->dispcss = $dispcss;
 }
 public function listhierParms() {
  /* extensions for listhier parameters. See listhierClass*/
  $lnumin = $_REQUEST['lnum'];  
  $this->lnumin=$lnumin;

  // direction: either 'UP', 'DOWN', or 'CENTER' (default)
  $direction = $_REQUEST['direction'];
  if (($direction != 'UP') && ($direction != 'DOWN')) {
   $direction = 'CENTER';
  }
  $this->direction = $direction;
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
  if (! $x1) {$x1 = "";}
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
