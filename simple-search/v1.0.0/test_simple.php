<?php
 /* command-line program writes of simple-search
 php test_simple.php <key> <dict> <output>
 */
//require_once('../../dbgprint.php');
require_once('simple_search.php');  
//$dirpfx = "../../";
//require_once($dirpfx . "utilities/transcoder.php"); // initializes transcoder

$keyparmin = $argv[1];
$dict = $argv[2];
$fileout = $argv[3];

if (!$fileout) {
 echo "USAGE: php test_simple.php <word> <dict> <fileout>\n";
 exit(1);
}

$ss = new Simple_Search($keyparmin,$dict);

$keysin = $ss->keysin;

 $fout = fopen($fileout,"w");


 $nss = count($keysin);
 fwrite($fout,"$keyparmin,$dict -> $nss variants\n");
 for($i=0;$i<$nss;$i++) {
  fwrite($fout,"$i {$keysin[$i]}\n");
 }
 fclose($fout);
 
 echo "$nss records written to $fileout\n";

?>
