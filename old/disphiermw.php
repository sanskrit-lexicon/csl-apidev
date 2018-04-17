<?php
//disphiermw.php  based on getword.php
#$dir = dirname(__FILE__); //directory containing this php file
# Note: $dir does not end in '/'
$dirutil = "utilities";
$transcoder = $dirutil ."/". "transcoder.php";
require_once($transcoder); // initializes transcoder
require_once("dal.php");  
include("dispmw.php");
require_once('dictinfo.php');
//echo "<p>disphiermw.php</p>\n";
// June 11, 2015.  options controls some aspects of basicDisplay.
$options = $_GET['options'];  
require_once('getCommon.php');


$meta = '<meta charset="UTF-8">';

$origkey = $key;

$more = True;
while ($more) {
 $results = $dal->get1($key); 
 $matches=array();
 $nmatches=0;
 foreach($results as $line) {
  list($key1,$lnum1,$data1) = $line;
  $matches[] = $line; # June 4, 2015
  $nmatches++;
 }
 if($nmatches > 0) {$more=False;break;}
 // try next shorter key:  Not sure this is good idea
  break;
 $n = strlen($key);
 if ($n > 1) {
  $key = substr($key,0,-1); // remove last character
 } else {
  $more=False;
 }
}
if ($nmatches == 0) {
 //echo "DBG: cmd1 = $cmd1\n";
 echo "$meta\n";
 echo "<h2>not found: '$key'</h2>\n";
 exit;
}
# accent-adjustment
for($i=0;$i<count($matches);$i++) {
 $matches[$i] = accent_adjust($matches[$i],$accent,$dictup);
}

$table = basicDisplay($key,$matches,$dictup,$options); // from disp.php
//echo "<!-- debug\n$table\n-->\n";
$table1 = transcoder_processElements($table,"slp1",$filter,"SA");
echo $table1;

//exit;

function accent_adjust($line,$accent,$dict) {
 list($key,$lnum,$data) = $line;
 if ($dict == 'MW') {
  return accent_adjust_MW($line,$accent,$dict);
 }
 if($accent == 'yes') {
  $new = preg_replace_callback("|<span class='sdata'><SA>(.*?)</SA></span>|",
         "accent_yes",$data);
 }else {
  $new = preg_replace_callback("|<span class='sdata'><SA>(.*?)</SA></span>|",
         "accent_no",$data);
  //echo "<p>accent_no ends</p>";
  //$newx = preg_replace("|<|","&lt;",$new);
  //$newx = preg_replace("|>|","&gt;",$newx);
  //echo "<p>$newx</p>";
 }
 return array($key,$lnum,$new);
}
function accent_adjust_MW($line,$accent) {
 // Also must adjust key2, 
 list($key,$lnum,$data) = $line;
  if (!preg_match('|<info>(.*?)</info><body>(.*?)</body>|',$data,$matchrec)) {
  return $line; // cannot proceed. Unexpected
 }
 $info = $matchrec[1];
 $html = $matchrec[2];

 if($accent == 'yes') {
  $newhtml = preg_replace_callback("|<span class='sdata'><SA>(.*?)</SA></span>|",
         "accent_yes",$html);
  $newinfo = $info;
 }else {
  $newhtml = preg_replace_callback("|<span class='sdata'><SA>(.*?)</SA></span>|",
         "accent_no",$html);
  list($pginfo,$hcode,$key2,$hom) = preg_split('/:/',$info);
  $newkey2 = preg_replace('|[\/\^\\\]|','',$key2);
  $newinfo = join(':',array($pginfo,$hcode,$newkey2,$hom));
 }
 $new = "<info>$newinfo</info><body>$newhtml</body>";
 return array($key,$lnum,$new);
}
function accent_yes($matches) {
 $old = $matches[0];
 $new = preg_replace("|class='sdata'|","class='sdata_siddhanta'",$old);
 return $new;
}
function accent_no($matches) {
 $olddata = $matches[1];
 $newdata = preg_replace('|[\/\^\\\]|','',$olddata);
 $new = "<span class='sdata'><SA>$newdata</SA></span>";
 return $new;
}
?>
