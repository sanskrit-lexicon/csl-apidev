<?php
//getsuggest.php
header('content-type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
require_once('utilities/transcoder.php'); // initializes transcoder
require_once("dal.php");  
require_once("dbgprint.php");

require_once('parm.php');
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
$dal = new Dal($dict);
$keyprobFlag=false;
if ($english) {
 $keyin1 = $keyin;
 $key = $keyin1;  
}else {
 $keyin1 = preprocess_unicode_input($keyin,$filterin);
 $key = transcoder_processString($keyin1,$filterin,"slp1");
 dbgprint($dbg,"keyin=$keyin, keyin1=$keyin1, key=$key, filterin=$filterin\n");
 if ($filterin == 'hk') {
  // for cases like 'gaN'
  $keychk = transcoder_processString($key,"slp1",$filterin);
  if ($keychk != $keyin1) {
  dbgprint($dbg,"Problem with HK spelling: keychk=$keychk\n");
   $keyprobFlag=true;
  }
 }
}

$meta = '<meta charset="UTF-8">';

$origkey = $key;

$more = True;
$max = 10;  # max number of return results
$maxlike=100;

$matches=array();
$nmatches=0;
if(!$keyprobFlag) {
 $results = $dal->get3a($key,$maxlike); 
 $nresults=count($results);
 dbgprint($dbg,"dal_get3a: $key, $maxlike, nresults=$nresults\n");
 foreach($results as $line) {
  list($key1,$lnum1,$data1) = $line;
  if(in_array($key1,$matches)) {continue;} // skip duplicates
  // perhaps other filtering, esp. for MW
  $matches[] = $key1; 
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
}
// convert to Json array
$json = json_encode($matches);
/* Next for JSONP
  Ref: //www.geekality.net/2010/06/27/php-how-to-easily-provide-json-and-jsonp/
*/
if (isset($_GET['callback'])) {
 echo "{$_GET['callback']}($json)";
}else {
 echo $json;
}

?>
