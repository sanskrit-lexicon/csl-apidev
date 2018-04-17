<?php /* June 5, 2015 */
require_once("../../web/webtc/disp.php");
/* June 6, 2015
 1. establish global $accent as True, for those dictionaries that have
    accents. disp.php for other dictionaries are assumed to ignore this
    global  
 2. Changed so X.xml is read one line at a time. This since 
    PHP memory allocation error for some dictionaries (like MW)
*/ 
 global $accent;
 $accent=True;

 $filein = $argv[1]; //"X.xml";
 $fileout = $argv[2]; //"input.txt";

 #$lines = file($filein);
 $fpin = fopen($filein,"r") or die("Cannot open $filein\n");
 $fpout = fopen($fileout,"w") or die("Cannot open $fileout\n");
 $lnum=0;
 $n = 0;
 $m = 10; # dbg
 $m = 1000000; # production
 #foreach($lines as $line){
 while (!feof($fpin)) {
  $line = fgets($fpin);
  $line = trim($line);
  if (!preg_match('|^<H|',$line)) {continue;} # should work for all
  // construct output
  $lnum = $lnum + 1;
  if(!preg_match('|<key1>(.*?)</key1>.*<L[^>]*>(.*?)</L>|',$line,$matches)) {
   echo "ERROR: Could not find key1,lnum from line: $line\n";
   exit(1);
  }
  $key1 = $matches[1];
  $lnum = $matches[2];
  #$data = $line;
  $data = html($key1,$lnum,$line);
  $out = "$key1\t$lnum\t$data";
  fwrite($fpout,"$out\n");
  $n = $n + 1;
  if ($n >= $m) {echo "dbg break after $m records\n";break;}
 }
 echo "$n records processed\n";
 fclose($fpout);
exit(0);
function html($key,$lnum,$data) {
 global $pagecol;
 $pagecol = ""; // otherwise, not all pc data is reported in $info below
 $matches=array($data);
 $table = basicDisplay($key,$matches,$filter);
 #return $table;
 # expect table has 6 lines.
 $tablines = explode("\n",$table); 
 if (count($tablines) != 6) {
  echo "html ERROR 1: actual # lines in table = " . count($tablines) ."\n";
  exit(1);
 }
 $info = $tablines[2];
 $body = $tablines[3];
 # adjust body
 $body = preg_replace('|<td.*?>|','',$body);
 $body = preg_replace('|</td></tr>|','',$body);
 # adjust $info - keep only the displayed page
 if(!preg_match('|>([^<]*?)</a>|',$info,$matches)) {
  echo "html ERROR 2: \n" . $info . "\n";
  exit(1);
 }
 $pageref=$matches[1];
 # construct return value
 $ans = "<info>$pageref</info><body>$body</body>";
 return $ans;
}
?>