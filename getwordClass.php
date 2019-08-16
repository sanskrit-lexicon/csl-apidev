<?php
// Exclude WARNING messages also, to solve Peter Scharf Mac version.
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
?>
<?php
require_once('utilities/transcoder.php'); // initializes transcoder
require_once("dal.php");  
require_once('dbgprint.php');
require_once('parm.php');
require_once('getword_data.php');
require_once("disp.php");

class GetwordClass {
 public $getParms,$matches,$table1;
 public function __construct() {
  $getParms = new Parm();
  $this->getParms = $getParms;
  $dict = $getParms->dict;
  $dal = new Dal($dict);
  $temp = new Getword_data($getParms,$dal);
  $this->matches = $temp->matches; 
  $this->table1 = $this->getword_html();
  $dal->close();
}

 public function getword_html() {
  $getParms = $this->getParms;
  $matches  = $this->matches;
  $dbg=false;
  $nmatches = count($matches);
  $key = $getParms->key;
  $keyin = $getParms->keyin1;
  if ($nmatches == 0) {
   $table1 = "<meta charset='UTF-8'>\n";
   $table1 .= "<h2>not found: '$keyin' (slp1 = $key)</h2>\n";
  }else {
   $table = basicDisplay($getParms,$matches); // from disp.php
   dbgprint($dbg,"getword\n$table\n\n");
   $filter = $getParms->filter;
   $table1 = transcoder_processElements($table,"slp1",$filter,"SA");
  }
  return $table1;
 }
}
?>
 