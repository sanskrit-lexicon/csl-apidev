<?php
/* alphabet.php
   php alphabet.php alphabet.txt
   Generates Sanskrit alphabet in several forms 
*/
$dirpfx = "../../";
require_once($dirpfx . "utilities/transcoder.php"); // initializes transcoder
main();
function main() {
 $slp_alphabet = ['a','A','i','I','u','U','f','F','x','X','e','E','o','O',
  'M','H',
  'k','K','g','G','N',
  'c','C','j','J','Y',
  'w','W','q','Q','R',
  't','T','d','D','n',
  'p','P','b','B','m',
  'y','r','l','v',
  'S','z','s','h'
 ];
 $outlines = [];
 foreach($slp_alphabet as $slp) {
  $hk = transcoder_processString($slp,'slp1','hk');
  $iast = transcoder_processString($slp,'slp1','roman');
  $deva = transcoder_processString($slp,'slp1','deva');
  $outvals = [$slp,$hk,$iast,$deva];
  $outlines[] = $outvals;
 }
 $filename = "alphabet.txt";
 $fp = fopen($filename,"w");
 $i = 0;
 foreach($outlines as $outvals) {
  $i = $i + 1;
  list($slp,$hk,$iast,$deva) = $outvals;
  $out = sprintf("%2d %s %s %s %s\n",$i,$slp,$hk,$iast,$deva);
  fwrite($fp,$out);
 }
 fclose($fp);
 
}
?>
