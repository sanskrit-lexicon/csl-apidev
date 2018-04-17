<?php
/* 01-02-2018. Present a headword (in SLP1) and a dictionary.
   Reads a headword from command line.
   Access hwnorm1c, and print results.
   Assumes headword is already in slp1 transliteration.
*/
$dirpfx = "../../";
require_once($dirpfx . "utilities/transcoder.php"); // initializes transcoder
require_once('dalnorm.php');
$normname = 'hwnorm1c';  // name of sqlite table for norm
$normdir  = '../../../sanhw1'; // path to directory with sqlite
$dalnorm = new Dalnorm($normname,$normdir);

// Input parameters for these test
$keyin0 = $argv[1];
echo "keyin0 = $keyin0\n";
$tranin = "slp1";
$keyin = transcoder_processString($keyin0,$tranin,"slp1");
$keyin_norm = $dalnorm->normalize($keyin);
 $matches = $dalnorm->get1($keyin_norm);
 foreach($matches as $match) {
  list($mkey,$mdata) = $match;
   $parts = explode(';',$mdata);
   foreach($parts as $part) {
    list($dictheadword0,$dictliststring) = explode(':',$part);
    $dictlist = explode(',',$dictliststring);
    foreach($dictlist as $dict) {
     echo "$dictheadword0 , $dict\n";
    }
   }
 }
?>
