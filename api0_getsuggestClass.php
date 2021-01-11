<?php
// Exclude WARNING messages also, to solve Peter Scharf Mac version.
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
?>
<?php
//api0_getsuggestClass.php 08-16-2019
require_once('utilities/transcoder.php'); // initializes transcoder
require_once("dbgprint.php");
require_once("dal.php");
require_once('parm.php');
/* Computes public variable $result, which is an associative array with keys:  
   status:  200 (ok) or 404 (problem)
   errorinfo: empty string if ok, error message string if problem
   request:  associative array of parameters used:
    dict : the dictionary code
    term : the partial word to match in the dictionary. uses 'input' spelling
    input : (optional) Default is slp1.  Otherwise one of the recognized
            spelling systems:  slp1, hk, itrans, deva, iast (or roman)
    termslp1 : term, in slp1 spelling.
   nmatches: number of matches found for term in dictionary
   matches : Array of strings, consisting of matching headwords.
             Results are spelled in same transcoding as input.
*/
class api0_GetsuggestClass {
 public $result;
 public $matches; // needed?
 public $xParm; // associative array of extra data needed from request
 public function __construct() {
  // initialize result 
  $this->init_result();
  if ($this->result['status'] != 200) {
   return;  // do no more
  }
  $this->suggestions1();
 }
 public function suggestions1() { // second version
  // Unpack some local variables
  $getParms = $this->xParm['getParms'];
  $keyin = $this->xParm['keyin'];
  $keyin1 = $this->xParm['keyin1'];
  $termslp1 = $this->xParm['termslp1'];
  $english = $this->xParm['english'];
  $filterin = $this->xParm['filterin'];
  $dbg=false;
  $origkey = $key;
  $dict = $getParms->dict;
  dbgprint($dbg,"getsuggestClass: call Dal($dict)\n");
  $dal = new Dal($dict);
  $maxlike=200000;
  $max = $maxlike;
  $matches=array();
  $nmatches=0;

   dbgprint($dbg,"getsuggestClass. call dal_get3c: $termslp1, $maxlike\n");
   $results1 = $dal->get3c($termslp1,$maxlike); 
   dbgprint($dbg,"dal_get3c: $termslp1, $maxlike, nresults1=".count($results1)."\n");
   $keys1 = $results1;
   /*
   $results=array();
   foreach($results1 as $result){
    $results[]=$result;
   }
   $keys1 = array();
   foreach($results as $line) {
    #list($key1,$lnum1,$data1) = $line;
    $key1=$line;
    if(in_array($key1,$keys1)) {continue;} // skip duplicates
     $keys1[] = $key1; 
     #echo "adding key1=$key1<br/>";
    }
   */
   if(!$english) {
    // sort $keys1 in Sanskrit order
    usort($keys1,'api0_GetsuggestClass::slp_cmp');
   } 
   // Pick first $max in $keys
   foreach($keys1 as $key1) {
    $matches[]=$key1;
    $nmatches++;
    if ($nmatches==$max) {
     break;
    }
   }
   if(!$english) {
    // transcode back from slp1 to filterin
    for($i=0;$i<$nmatches;$i++) {
     $matches[$i] = transcoder_processString($matches[$i],"slp1",$filterin);
    }
   }

  $this->result['nmatches'] = $nmatches;
  $this->result['matches'] = $matches;
 }

 public function suggestions() { // first version
  // Unpack some local variables
  $getParms = $this->xParm['getParms'];
  $keyin = $this->xParm['keyin'];
  $keyin1 = $this->xParm['keyin1'];
  $termslp1 = $this->xParm['termslp1'];
  $english = $this->xParm['english'];
  $filterin = $this->xParm['filterin'];
  $dbg=false;
  $origkey = $key;
  $dict = $getParms->dict;
  dbgprint($dbg,"getsuggestClass: call Dal($dict)\n");
  $dal = new Dal($dict);
  $maxlike=10000;
  $max = $maxlike;
  $matches=array();
  $nmatches=0;

   dbgprint($dbg,"getsuggestClass. call dal_get3a: $termslp1, $maxlike\n");
   $results1 = $dal->get3a($termslp1,$maxlike); 
   dbgprint($dbg,"dal_get3a: $termslp1, $maxlike, nresults1=".count($results1)."\n");
   $results=array();
   foreach($results1 as $result){
    $results[]=$result;
   }
   $keys1 = array();
   foreach($results as $line) {
    list($key1,$lnum1,$data1) = $line;
    if(in_array($key1,$keys1)) {continue;} // skip duplicates
     $keys1[] = $key1; 
     #echo "adding key1=$key1<br/>";
    }
   if(!$english) {
    // sort $keys1 in Sanskrit order
    usort($keys1,'api0_GetsuggestClass::slp_cmp');
   } 
   // Pick first $max in $keys
   foreach($keys1 as $key1) {
    $matches[]=$key1;
    $nmatches++;
    if ($nmatches==$max) {
     break;
    }
   }
   if(!$english) {
    // transcode back from slp1 to filterin
    for($i=0;$i<$nmatches;$i++) {
     $matches[$i] = transcoder_processString($matches[$i],"slp1",$filterin);
    }
   }

  $this->result['nmatches'] = $nmatches;
  $this->result['matches'] = $matches;
 }

 public function init_result() {
  $result = array();
  $result['status'] = 200; // assume no error
  $result['errorinfo'] = ''; // assume no error
  $result['nmatches'] = 0;
  $result['matches'] = array();
  $this->result = $result;
  $this->result['request'] = $this->init_request();
 }
 public function term_error($term) {
  $this->result['status'] = 404;
  $this->result['errorinfo'] = "getsuggest term error: $term";
 }
 public function init_request() {
  // initialize $this->result['request'] and $this->xParm;
  $xParm = array();
  $getParms = new Parm();
  list($xParm['keyin'],$xParm['keyin1'],$xParm['termslp1']) = 
    $getParms->getsuggestParms();
  $xParm['getParms'] = $getParms;
  $xParm['english'] = $getParms->english;
  $xParm['filterin'] = $getParms->filterin;

  $term = $getParms->getsuggestTerm;
  $request = array();
  $request['dict'] = $getParms->dict;
  $request['term'] = $term;
  $request['termslp1'] = $xParm['termslp1'];
  $request['input'] = $getParms->filterin;
  if ($getParms->status != 200) {
   $this->result['status'] = $getParms->status;
   $this->result['errorinfo'] = $getParms->errorinfo;
  }else if ($term == '') {
    $this->term_error($term);
  }
  $this->xParm = $xParm;
  return $request;
 }
 public function slp_cmp($a,$b) {
  // $a, $b are strings in SLP1 coding of Sanskrit. Return -1,0,1 according to
  // whether $a<$b, $a==$b, or $a>$b
  // order per PMS (Sep 25, 2012): L after q, | after Q
  $from = "aAiIuUfFxXeEoOMHkKgGNcCjJYwWqLQ|RtTdDnpPbBmyrlvSzsh";
  $to =   "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxy";

  $a1 = strtr($a,$from,$to);
  $b1 = strtr($b,$from,$to);
  return strcmp($a1,$b1);
 }
}
?>
