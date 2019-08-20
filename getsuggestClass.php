<?php
// Exclude WARNING messages also, to solve Peter Scharf Mac version.
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
?>
<?php
//getsuggestClass.php 08-16-2019
require_once('utilities/transcoder.php'); // initializes transcoder
require_once("dbgprint.php");
require_once("dal.php");
require_once('parm.php');
class GetsuggestClass {
 public $matches,$json;
 public function __construct() {
  $getParms = new Parm();
  /* Jquery autosuggest uses parameter 'term' 
    We use logic similar to that in the Parm constructor to adjust this keyin
  */
  $keyin = $_GET['term'];
  $dbg=false;
  $keyin = trim($keyin); // remove leading and trailing whitespace
  $english = $getParms->english;
  $filterin = $getParms->filterin;
  $dict = $getParms->dict;
  dbgprint($dbg,"getsuggest: dict=$dict\n");
  $dal = new Dal($dict);
  $keyprobFlag=false;
  if ($english) {
   $keyin1 = $keyin;
   $key = $keyin1;  
  }else {
   $keyin1 = $getParms -> preprocess_unicode_input($keyin,$filterin);
   $key = transcoder_processString($keyin1,$filterin,"slp1");
   dbgprint($dbg,"getsuggest.php: keyin=$keyin, keyin1=$keyin1, key=$key, filterin=$filterin\n");
   if ($filterin == 'hk') {
    // for cases like 'gaN'
    $keychk = transcoder_processString($key,"slp1",$filterin);
    if ($keychk != $keyin1) {
    dbgprint($dbg,"Problem with HK spelling: keychk=$keychk\n");
     $keyprobFlag=true;
    }
   }
  }
  $origkey = $key;

  $more = True;
  $max = 10;  # max number of return results
  $maxlike=100;
  $matches=array();
  $nmatches=0;
  if(!$keyprobFlag) {
   $results1 = $dal->get3a($key,$maxlike); 
   dbgprint($dbg,"dal_get3a: $key, $maxlike, nresults1=".count($results1)."\n");
   $results2 = $dal->get1($key); // include exact matches, if any
   dbgprint($dbg,"dal_get1: $key, nresults2=".count($results2)."\n");
   $results=array();
   foreach($results1 as $result){
    $results[]=$result;
   }
   foreach($results2 as $result){
    $results[]=$result;
   }
   $keys1 = array();
   foreach($results as $line) {
    list($key1,$lnum1,$data1) = $line;
    if(in_array($key1,$keys1)) {continue;} // skip duplicates
     $keys1[] = $key1; 
    }
   if(!$english) {
    // sort $keys1 in Sanskrit order
    usort($keys1,'GetsuggestClass::slp_cmp');
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
  }
  if ($keyprobFlag or ($nmatches==0)) {
   $matches[]="$key??";
   $nmatches=1;
   if(!$english) {
    // transcode back from slp1 to filterin
    for($i=0;$i<$nmatches;$i++) {
     $matches[$i] = transcoder_processString($matches[$i],"slp1",$filterin);
    }
   }
  }
  // convert to Json array
  $json = json_encode($matches);
  // Establish public attributes of class
  $this->matches = $matches;
  $this->json = $json;
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