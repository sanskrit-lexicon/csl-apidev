<?php
/* 03-15-2017 easyspell.php
  Refer https://github.com/sanskrit-lexicon/Cologne/issues/8
*/
require_once("dal.php");  
require_once("dbgprint.php");
require_once('utilities/transcoder.php'); // initializes transcoder
// To avoid cross-origin errors
$dbg=false;
header('content-type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
/*
dbgprint($dbg,'callback set=' . isset($_GET['callback']) . "\n");
if (isset($_GET['callback'])) {
 header('content-type: application/json; charset=utf-8');
 header("Access-Control-Allow-Origin: *");
}
*/
//require_once('parm.php');
//$getParms = new Parm();
$dict = $_REQUEST['dict']; // all parameters
$words=$_REQUEST['words'];
//$parm = json_decode($parmstring,$assoc=true);
$dal = new Dal($dict);
$dal_status=$dal->status;
dbgprint($dbg,"dict=$dict, $dal_status=$dal_status\n");
/*
echo "dict=$dict\n";
echo "values=$values\n";
echo "status = $dal_status\n";
*/
$ans = array(); # associative array
$ans['status']=$dal_status;
$ans['dict']=$dict;
$a = [];
if ($dal_status) {
 for($i=0;$i<count($words);$i++) {
  $keyin = $words[$i];
  // assume in HK spelling. Transcode to $slp1
  $key1 = transcoder_processString($keyin,"hk","slp1");
  $result = $dal->get1($key1);
  $flag = (count($result)>0);
  $arec = array("word"=>$keyin,"slp1"=>$key1,"status"=>$flag);
  $a[]=$arec;
 }
}
$ans['words']=$a;
// convert to JSON
$json = json_encode($ans);
/* Next for JSONP
  Ref: http://www.geekality.net/2010/06/27/php-how-to-easily-provide-json-and-jsonp/
*/
if (isset($_GET['callback'])) {
 echo "{$_GET['callback']}($json)";
}else {
 echo $json;
}

?>
