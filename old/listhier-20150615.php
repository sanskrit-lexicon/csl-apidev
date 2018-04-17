<?php
/* apidev/listhier.php
  June 2015
 
*/

require_once('utilities/transcoder.php');
require_once('listhierskip.php');  //Apr 2013
require_once('displistCommon.php');
require_once('dal.php');
require_once('dictinfo.php');
require_once('getCommon.php');

// direction: either 'UP', 'DOWN', or 'CENTER' (default)
$direction = $_GET['direction'];
if(!$direction) {$direction = $argv[2];}
if (($direction != 'UP') && ($direction != 'DOWN')) {
 $direction = 'CENTER';
}
/* $dal is variable set in getCommon 
  It accesses the html database
*/

// step 1: get a match for key. 
$matches = match_key($key,$dal);
list($key1,$lnum1,$data1) = $matches[0];
//echo "<p>key1=$key1, lnum1=$lnum1</p>\n";
// step 2:  get several keys preceding and several keys following $key1
$nprev=12;
$nnext=12;
if ($direction == 'UP') {
 $listmatches = list_center($key1,$lnum1,$data1,$nprev+$nnext,0,$dal);
}else if ($direction == 'DOWN') {
 $listmatches = list_center($key1,$lnum1,$data1,0,$nprev+$nnext,$dal);
}else {
 $listmatches = list_center($key1,$lnum1,$data1,$nprev,$nnext,$dal);
}

// step 3 format listmatches
$i=0;
$table="";
$spcchar = "&nbsp;";
$spcchar = ".";
while($i < count($listmatches)) {
 list($code,$key2,$lnum2,$data2) = $listmatches[$i];
 $hom2=get_hom($data2);
 if ($i == 0) {
  //  put 'upward button'
  $spc="&nbsp;&nbsp;";
  $out1 = "$spc<a  onclick='getWordlistUp_keyboard(\"$key2\");'>&#x25B2;</a><br/>\n";  
  $table .= $out1;
 }
 $i++;
 if ($code == 0) {$c="color:teal";}
 else {$c="color:black";}
 // Apr 7, 2013.  Color Supplement records 
 if (preg_match('/<L supL="/',$data2,$matches)) {
  $c = "color:red";
 }
 if (preg_match('/<L revL="/',$data2,$matches)) {
  $c = "color:green";
 }

 if (preg_match('/^<H([2])/',$data2,$matches)) {
  $spc="$spcchar";
 }else if(preg_match('/^<H([3])/',$data2,$matches)) {
  $spc="$spcchar$spcchar";
 }else if(preg_match('/^<H([4])/',$data2,$matches)) {
  $spc="$spcchar$spcchar$spcchar";
 }else {
  $spc="";
 }
 if ($hom2 != "") {
  $hom2=" <span style=\"color:red; font-size:smaller\">$hom2</span>";
 }
 // Apr 10, 2013. key2show: 
 $key2show=$key2;

 // Apr 14, 2013: xtraskip
 $xtraskip='';
 if(listhierskip_data($data2)) {
  $xtraskip='<span style="font-size:x-small; color:blue;"> (x)</span>';
 }
 $english = in_array($dictup,array("AE","MWE","BOR")); // boolean flag
 if (!$english) {
  $key2show ="<SA>$key2show</SA>";
 }
 /* In MWE, we have key2 = cat-o'-nine-tails
    And the apostrophe causes a problem - we need to escape it. 
 */
 $key2 = htmlspecialchars($key2,ENT_QUOTES);
 $out1 = "$spc<a  onclick='getWordAlt_keyboard(\"$key2\");'>$key2show$hom2</a>$xtraskip<br/>\n";
 //$out1 = addslashes($out1); 
 $table .= $out1;
 if ($i == count($listmatches)) {
  //  put 'downward button'
  $spc="&nbsp;&nbsp;";
  $out1 = "$spc<a  onclick='getWordlistDown_keyboard(\"$key2\");'>&#x25BC;</a>\n";  
  $table .= $out1;
 }

}
// spit it out
$table1 = transcoder_processElements($table,"slp1",$filter,"SA");
echo $table1;
return;

function match_key($key,$dal) {
 // this function 'guaranteed' to return an array with one entry
$matches = list1a($key,$dal);
$nmatches = count($matches);
//echo "chk1: $key, $nmatches\n";
if ($nmatches != 1) {
 $key1 = $key;
 $nmatches=0;
 $n1 = strlen($key1);
 while (($nmatches == 0) && ($n1 > 0)) {
  $key2 = substr($key1,0,$n1);
  $matches = list1b($key2,$dal);
  $nmatches = count($matches);
  if ($nmatches == 0) {$n1--;}
 } 
}
if ($nmatches == 0) {
 $key = "a"; // sure to match
 $key1 = $key;
 $nmatches=0;
 $n1 = strlen($key1);
 while (($nmatches == 0) && ($n1 > 0)) {
  $key2 = substr($key,0,$n1);
  $matches = list1b($key2,$dal);
  $nmatches = count($matches);
  if ($nmatches == 0) {$n1--;}
 } 
}
// $nmatches = count($matches);
// echo "chk2: $key, $nmatches\n";
 return $matches;
}
function list1a($key,$dal) {
// first exact match
$recarr = $dal->get1($key);
$matches=array();
$nmatches=0;
$more=True;
foreach($recarr as $rec) {
 if ($more) {
  list($key1,$lnum1,$data1) = $rec;
  // May 23, 2013.  Do not consider the listhierskip records
  if (listhierskip_data($data1)) { continue;}
  $matches[0]=$rec;
  $more=False;
 }
}
/* commented out, Apr 14
if (count($recarr) > 0) {
   $matches[0]=$recarr[0]; 
}
*/
return $matches;
 }
function list1b($key,$dal) {
// first  partial match
$recarr = $dal->get3($key); // records LIKE key%
$matches=array();
$nmatches=0;
$keylen = strlen($key);
$more=true;
foreach($recarr as $rec) {
 if ($more) {
  list($key1,$lnum1,$data1) = $rec;
  // May 23, 2013.  Do not consider the listhierskip records
  if (listhierskip_data($data1)) { continue;}
  $keylen1 = strlen($key1);
  if (($keylen1 >= $keylen) && (substr($key1,0,$keylen) == $key)) {
   $matches[$nmatches]=$rec;
   $nmatches++;
   $more=false;
  }
 }
}
return $matches;
}

function list_prev($key0,$lnum0,$nprev,$dal) {
$ans = array();
if ($nprev <= 0) {return $ans;}
$max = 5 * $nprev;  // 5 is somewhat arbitrary.
$recarr = $dal->get4a($lnum0,$max);
//echo "<p>list_prev: $key0,$lnum0, $nprev finds " . count($recarr) . " records</p>\n";
// 1. get records to be displayed
$matches = list_filter($lnum0,$recarr,$dal);
// 2. get the last $nprev records for return
$nmatches = count($matches);
if ($nmatches == 0) {return $ans;}
if ($nprev <= $nmatches) {
 $n1 = $nprev;
}else {
 $n1 = $nmatches;
}
// we retrieved in descending order. Now, we get back to ascending order
$j=$n1-1;
for($i=0;$i<$n1;$i++) {
 $x = $matches[$j];
 $ans[]=$x;
 $j--;
}
return $ans;
}
function list_next($key0,$lnum0,$n0,$dal) {
$ans = array();
if ($n0 <= 0) {return $ans;}
// next $n0 different keys
$max = 5 * $n0;  // 5 is somewhat arbitrary.
$recarr = $dal->get4b($lnum0,$max);
// 1. get records to be displayed
$matches = list_filter($lnum0,$recarr,$dal);
// 2. get the last $nprev records for return
$nmatches = count($matches);

if ($nmatches == 0) {return $ans;}
if ($n0 <= $nmatches) {
 $n1 = $n0;
}else {
 $n1 = $nmatches;
}
for($i=0;$i<$n1;$i++) {
 $x = $matches[$i];
 $ans[]=$x;
}
return $ans;
}
function list_filter($lnum0,$recarrin,$dal) {
// This variant matches on key+hom
// This logic is relevant for MW, but will need to be changed
// since '$data1'  is now html, not xml.
$dict = $dal->dict; // lowercase
$matches=array();
if ($dict != 'mw') {
 foreach($recarrin as $rec) {
  list($key1,$lnum1,$data1) = $rec;  // $data1 is <info>x</info><body>y</body>
  $rec1 = array($key1,$lnum1,""); // no use for data1, since not mw
  $matches[]=$rec1;
 }
 return $matches;
}
// Rest of logic is for mw
// First readjust 3rd parm of recarrin, 
$recarr=array();
foreach($recarrin as $rec) {
 list($key1,$lnum1,$data1) = $rec;  // $data1 is <info>x</info><body>y</body>
 if (!preg_match('|<info>(.*?)</info><body>(.*?)</body>|',$data1,$matchrec)) {
  $data2="";
 }else {
  $info = $matchrec[1];
  $html = $matchrec[2];
  list($pginfo,$hcode,$key2,$hom) = preg_split('/:/',$info);
  // Mimic part of the MW structure
  $out=array();
  $out[] = "<$hcode>";
  if (($hom)&&($hom!='')){
   $out[] = "<h><hom>$hom</hom></h>";
  }
  $data2 = join('',$out);
 }
 $rec1 = array($key1,$lnum1,$data2);
 $recarr[]=$rec1;
}

$recarr0 = $dal->get2($lnum0,$lnum0); 
if (count($recarr0) != 1) {return $matches;} // should not happen
// Apr 6, 2013. Changed $recarr[0] to $recarr0[0] in next.
list($key0,$lnum0a,$data0)=$recarr0[0];  
$keyhom0 = get_keyhom($key0,$data0);
$keyhom = '';
foreach($recarr as $rec) {
  list($key1,$lnum1,$data1) = $rec;
  if (!preg_match('/^<H[1-4][BC]?>/',$data1)) {continue;}
  // Apr. 10, 2013. Don't show H.[BC]
  if (preg_match('/^<H[1-4][BC]>/',$data1)) {continue;}
  
  $keyhom1 = get_keyhom($key1,$data1);
  if ($keyhom1 == $keyhom){continue;}
  // Apr 13, 2013 commented out next line. Consider example avata
  // if ($keyhom1 == $keyhom0) {continue;}
  // found a new one
  $matches[]=$rec;
  $keyhom = $keyhom1; 
}
return $matches;
}
function get_keyhom($key,$data){
$hom = get_hom($data);
return "$key+$hom";
}
function get_hom($data) {
$hom="";
if (preg_match('|<hom>(.*?)</hom>.*?</h>|',$data,$matches)) {
 $hom = $matches[1];
}
return $hom;
}
function list_filter_v0($lnum0,$recarr,$dal) {
// The original version
$matches=array();
$recarr0 = $dal->get2($lnum0,$lnum0); 
if (count($recarr0) != 1) {return $matches;} // should not happen
list($key0,$lnum0a,$data0)=$recarr[0];
$key = '';
foreach($recarr as $rec) {
  list($key1,$lnum1,$data1) = $rec;
  if (!preg_match('/^<H[1-4][BC]?>/',$data1)) {continue;}
  if ($key1 == $key){continue;}
  if ($key1 == $key0) {continue;}
  // found a new one
  $matches[]=$rec;
  $key = $key1; 
}
return $matches;
}
function list_center($key1,$lnum1,$data1,$nprev,$nnext,$dal) {
$listmatches = array();
$matches1 = list_prev($key1,$lnum1,$nprev,$dal);
$matches2 = list_next($key1,$lnum1,$nnext,$dal);
$nmatches1 = count($matches1);
$nmatches2 = count($matches2);
// handle special cases
$i=0;
while($i < count($matches1)) {
 list($key2,$lnum2,$data2) = $matches1[$i];
 $listmatches[]=array(-1,$key2,$lnum2,$data2);
 $i++;
}
 $listmatches[]=array(0,$key1,$lnum1,$data1);
$i=0;
while($i < count($matches2)) {
 list($key2,$lnum2,$data2) = $matches2[$i];
 $listmatches[]=array(1,$key2,$lnum2,$data2);
 $i++;
}
return $listmatches;
}

?>
