<?php
// Exclude WARNING messages also, to solve Peter Scharf Mac version.
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
?>
<?php
//getword.php

if (isset($_GET['callback'])) {
 header('content-type: application/json; charset=utf-8');
 #header("Access-Control-Allow-Origin: *");
}
header("Access-Control-Allow-Origin: *");
require_once('utilities/transcoder.php'); // initializes transcoder
require_once("dal.php");  
require_once('dbgprint.php');
require_once('parm.php');
require_once('getword_data.php');
require_once("disp.php");

// Put code into a class, to minimize namespace clutter
$getword_obj = new Getword();
// Generate output
$getword_obj->getword_html();

class Getword {
 public $getParms,$html_data;
 public function __construct() {
  $getParms = new Parm();
  $this->getParms = $getParms;
  $dict = $getParms->dict;
  $dal = new Dal($dict);
  $this->html_data = getword_data_html($getParms,$dal); // in getword_data.php

  $dal->close();
}
//getword_html($getParms,$html_data);


 public function getword_html() {
 $getParms = $this->getParms;
 $matches  = $this->html_data;
 $dbg=false;
 $nmatches = count($matches);
 dbgprint($dbg,"getword.php #3: nmatches=$nmatches\n");
 $key = $getParms->key;
 $keyin = $getParms->keyin1;
 if ($nmatches == 0) {
  //echo "DBG: cmd1 = $cmd1\n";
 $meta = '<meta charset="UTF-8">';
  echo "$meta\n";
  echo "<h2>not found: '$keyin' (slp1 = $key)</h2>\n";
  return;
 }
 
 $table = basicDisplay($getParms,$matches); // from disp.php
 dbgprint($dbg,"getword\n$table\n\n");
 $filter = $getParms->filter;
 $table1 = transcoder_processElements($table,"slp1",$filter,"SA");
 if (isset($_GET['callback'])) {
  $json = json_encode($table1);
  echo "{$_GET['callback']}($json)";
 }else {
  echo $table1;
 }
 dbgprint($dbg,"getword.php.  END\n");
}
}

?>
 