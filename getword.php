<?php
error_reporting(E_ALL & ~E_NOTICE );
?>
<?php
//getword.php
// Jul 19, 2015  Uses $dal->get1_mwalt()  when dict is mw.
// Jul 11, 2018.  dalwhich
// 09-02-2018. Since rawflag is always true, we don't need to use
// it here.  Currently revising code by commenting out old code.

if (isset($_GET['callback'])) {
 header('content-type: application/json; charset=utf-8');
 #header("Access-Control-Allow-Origin: *");
}
header("Access-Control-Allow-Origin: *");
require_once('utilities/transcoder.php'); // initializes transcoder
require_once("dalwhich.php");  
require_once('dbgprint.php');
require_once('parm.php');
require_once('getword_data.php');
require_once("disp.php");

$getParms = new Parm();

$dict = $getParms->dict;
$dal = dalwhich($dict);
$html_data = getword_html_data_raw($getParms,$dal);
/*
$rawflag = $dal->rawflag;
if ($rawflag) {
 $html_data = getword_html_data_raw($getParms,$dal);
} else {
 $html_data = getword_html_data($getParms,$dal);
}
*/
$dal->close();
// getword_html_raw and getword_html are functionally the same.
getword_html($getParms,$html_data);
/*
if ($rawflag) {
 getword_html_raw($getParms,$html_data);
} else {
 getword_html($getParms,$html_data);
}
*/


function getword_html($getParms,$matches) {
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

 // $dictinfo = $getParms->dictinfo; // unused
 // $dictup  = $dictinfo->dictupper; // unused
 
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
/* getword_html_raw not needed
function getword_html_raw($getParms,$matches) {
 $dbg = false;
 $meta = '<meta charset="UTF-8">';
 $nmatches = count($matches);
 $key = $getParms->key;
 $keyin = $getParms->keyin1;
 if ($nmatches == 0) {
  echo "$meta\n";
  $key = $getParms->key;
  echo "<h2>not found: '$keyin' (slp1= $key)</h2>\n";
  exit;
 }
 
 $table = basicDisplay($getParms,$matches); // from disp.php
 dbgprint($dbg,"getword_html_raw \n$table\n\n");
 $filter = $getParms->filter;
 $table1 = transcoder_processElements($table,"slp1",$filter,"SA");
 if (isset($_GET['callback'])) {
  $json = json_encode($table1);
  echo "{$_GET['callback']}($json)";
 }else {
  echo $table1;
 }
 dbgprint($dbg,"getword_html_raw.  END\n");
}
*/

?>
 