<?php
// Exclude WARNING messages also, to solve Peter Scharf Mac version.
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
?>
<?php
//getsuggestClass.php 04-19-2022
// Read a list of headwords from a text file
// and return first few that start with a given string.
require_once('../utilities/transcoder.php'); // initializes transcoder
require_once("../dbgprint.php");

require_once('../parm.php');
class GetsuggestClass {
 public $matches,$json;
 public function __construct() {
  $getParms = new Parm();
  /* Jquery autosuggest uses parameter 'term' 
    We use logic similar to that in the Parm constructor to adjust this keyin
  */
  list($keyin,$keyin1,$key) = $getParms->getsuggestParms();
  $filterin = $getParms->filterin;
  $dbg=false;
  $origkey = $key;

  //$dict = $getParms->dict;
  //dbgprint($dbg,"getsuggestClass: call Dal($dict)\n");
  //$dal = new Dal($dict);
  $more = True;
  $max = 10;  # max number of return results
  $maxlike=100;
  $filename = "mergehw.txt";
  $lines = file($filename,FILE_IGNORE_NEW_LINES);
  $results1 = array();
  $keymatch = $key;
  if (preg_match('|^([^ ]+) (.*?)$|',$key,$m)) {
   $keymatch = $m[1];
  }
  $keylen = strlen($key);
  $nresults1  = 0;
  foreach($lines as $line) {
   if (substr($line,0,$keylen) == $key) {
    //$results1[] = $line;
    if (preg_match('|^([^ ]+) (.*?)$|',$line,$m)) {
     $hw = $m[1];
     $data = $m[2];
     $results1[] = array($hw,$data);
     $nresults1 = $nresults1 + 1;
     if ($nresults1 >= $max) {break;}
    }
   }
  }
  // transcode back from slp1 to filterin
  $matches = array();
  if ($nresults1 == 0) {
   // $hw = "$key??";
   $hw = "$key";
   $data = "";
   $matches[] = "$hw : $data";
  }
  foreach($results1 as $result) {
   list($hw,$data) = $result;
   $hw1 = transcoder_processString($hw,"slp1",$filterin);
   //$matches[] = array($hw1,$data);
   $matches[] = "$hw1 : $data";
  }

  // convert to Json array
  $json = json_encode($matches);
  // Establish public attributes of class
  $this->matches = $matches;
  $this->json = $json;
 }

}
?>
