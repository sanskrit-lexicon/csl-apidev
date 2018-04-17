<?php
/* 01-02-2018. 
   Reads input and output file names
   input file assumed to have slp1 spelled headwords (from pwg), one per line
   for each headword, use dalnorm to find the mw headword spelling.
   Write the two headwords to an output line.

*/
$dirpfx = "../../";
require_once($dirpfx . "utilities/transcoder.php"); // initializes transcoder
require_once('dalnorm.php');
$normname = 'hwnorm1c';  // name of sqlite table for norm
$normdir  = '../../../sanhw1'; // path to directory with sqlite
$dalnorm = new Dalnorm($normname,$normdir);
global $dalnorm;
$filein = $argv[1];
$fileout = $argv[2];
$lines = file($filein);
$fpout = fopen($fileout,"w") or die("Cannot open $fileout\n");
 $n = 0;
 $nprob = 0;
 foreach($lines as $line){
  $hw = trim($line);
  $n = $n + 1;
  $mwhws = get_word($hw,"mw"); // an array, possibly empty
  if (count($mwhws) == 0) {
   $mwhws = array("NOTFOUND");
   $nprob = $nprob + 1;
  }
  $mwhw = $mwhws[0];  // ignore multiple spellings in this instance
  $out = "$hw $mwhw";
  fwrite($fpout,"$out\n");
 }
echo "$n records read from $filein\n";
echo "$nprob records had a problem\n";
exit(0);
function get_word($keyin0,$dictlo) {
 global $dalnorm;
 $dictup = strtoupper($dictlo);
 $tranin = "slp1";
 $keyin = transcoder_processString($keyin0,$tranin,"slp1");
 $keyin_norm = $dalnorm->normalize($keyin);
 $matches = $dalnorm->get1($keyin_norm);
 $ans = array();
 foreach($matches as $match) {
  list($mkey,$mdata) = $match;
   $parts = explode(';',$mdata);
   foreach($parts as $part) {
    list($dictheadword0,$dictliststring) = explode(':',$part);
    $dictlist = explode(',',$dictliststring);
    foreach($dictlist as $dict) {
     //echo "$dictheadword0 , $dict\n";
     if ($dict == $dictup) {
      $ans[] = $dictheadword0;
     }
    }
   }
 }
 return $ans;
}
?>
