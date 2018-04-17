<?php
error_reporting(E_ALL & ~E_NOTICE );
?>
<?php
//getword_mwalt.php
// Jul 19, 2015  Uses $dal->get1_mwalt()  when dict is mw.
if (isset($_GET['callback'])) {
 header('content-type: application/json; charset=utf-8');
 header("Access-Control-Allow-Origin: *");
}
require_once('utilities/transcoder.php'); // initializes transcoder
require_once("dal.php");  
include("disp.php");
require_once('dbgprint.php');
require_once('parm.php');
$getParms = new Parm();

$dict = $getParms->dict;
$dal = new Dal($dict);
$dbg = false;
dbgprint($dbg,"getword.php #1 \n");

$meta = '<meta charset="UTF-8">';

/* $matches is array. each element is 3-element array
  list($key1,$lnum1,$data1)
*/
$key = $getParms->key;
dbgprint($dbg,"getword.php #2: key=$key, dict=$dict \n");
if (strtolower($dict) == 'mw') {
 $matches = $dal->get1_mwalt($key); // Jul 19, 2015
}else {
 $matches= $dal->get1($key); 
}
$nmatches = count($matches);
dbgprint($dbg,"getword.php #3: nmatches=$nmatches\n");
if ($nmatches == 0) {
 //echo "DBG: cmd1 = $cmd1\n";
 echo "$meta\n";
 echo "<h2>not found: '$key'</h2>\n";
 exit;
}
# accent-adjustment
require_once("accent_adjust.php");
$dictinfo = $getParms->dictinfo;
$dictup = $dictinfo->dictupper;
$accent = $getParms->accent;
for($i=0;$i<count($matches);$i++) {
 $matches[$i] = accent_adjust($matches[$i],$accent,$dictup);
}

$dictinfo = $getParms->dictinfo;
$dictup  = $dictinfo->dictupper;

$table = basicDisplay($getParms,$matches); // from disp.php
require_once("dbgprint.php");
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

?>
