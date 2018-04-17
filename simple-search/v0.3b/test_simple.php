<?php
 /* command-line program to test two versions of simple-search
 php test_simple.php vishnu 
 */
require_once('../../dbgprint.php');
require_once('simple_search_HK.php'); // orig
require_once('simple_search.php'); // 
$dirpfx = "../../";
require_once($dirpfx . "utilities/transcoder.php"); // initializes transcoder

$keyparmin = $argv[1];
echo "keyparmin=$keyparmin\n";
$sshk = new Simple_Search_HK($keyparmin);
$keysinhk = $sshk->keysin;
$keysinhkslp = [];
foreach($keysinhk as $k) {
 $keysinhkslp[] = transcoder_processString($k,'hk',"slp1");
}
$keyparminslp = transcoder_processString($keyparmin,'hk',"slp1");
$ss = new Simple_Search($keyparminslp);
$keysin = $ss->keysin;
dev_cmp_js_php($keysin,$keysinhkslp);

//gentt();
function dev_cmp_js_php($keysin,$keysinhkslp) {
 $keysinjs = $keysinhkslp;
 $njs = count($keysinjs);
 $nss = count($keysin);
 $dbg=true;
 dbgprint($dbg,"$njs keys from hk version\n");
 dbgprint($dbg,"$nss keys from slp1 version\n");
 $nprob=0;
 if ($nss == $njs) {
  for($i=0;$i<$nss;$i++) {
   if ($keysinjs[$i] != $keysin[$i]) {
    $nprob = $nprob + 1;
    dbgprint($dbg,"Problem @ $i: {$keysinjs[$i]} != {$keysin[$i]}\n");
   }
  }
  if ($nprob == 0) {
   dbgprint($dbg,"keysin is same as keysinhkslp\n");
   echo "HK and SLP method agree\n";
  }
 } else {
   echo "HK and SLP method disagree. See dbg_apidev.txt for comparison\n";
   if ($nss < $njs) {
    $n = $njs;
   }else {
    $n = $nss;
   }
   for($i=0;$i<$n;$i++) {
    $keyjs = "NONE";
    $keyss = "NONE";
    if ($i < $nss) {$keyss = $keysin[$i];}
    if ($i < $njs) {$keyjs = $keysinjs[$i];}
    if ($keyjs == $keyss) {
     $code = "OK";
    }else {
     $code = "PROB";
    }
    dbgprint($dbg,"$i : $keyjs  $keyss  $code\n");
   }
  }

}

function gentt() {
  $transitionTable = [
	["a","A"],
	["i","I"],
	["u","U"],
	["r","R","RR","ri"],
	["l","lR","lRR"],
	["h","H"],
	["M","n","N","J","G","m"], 
	["z","S","s","Sh","sh"],
	["b","v"],
	["k","kh"],
	["g","gh"],
	["c","ch"],
	["j","jh"],
	["T","Th","t","th"],
	["D","Dh","d","dh"],
	["p","ph"],
	["b","bh"],
	//["sh","z"]
];
$transitionTable_slp=array();
foreach($transitionTable as $tt) {
 $tt1=[];

 foreach($tt as $k) {
  $x = transcoder_processString($k,'hk',"slp1");
  $tt1[] =  '"' . $x .'"';
 }
 $tt1out = join(',',$tt1);
 echo "   [" . $tt1out . "],\n";
}
}
?>
