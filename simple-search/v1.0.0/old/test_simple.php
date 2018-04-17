<?php
 /* command-line program writes of simple-search
 php test_simple.php vishnu mw
output goes to file dbg_apidev.txt
 */
require_once('../../dbgprint.php');
require_once('simple_search.php'); // 
$dirpfx = "../../";
require_once($dirpfx . "utilities/transcoder.php"); // initializes transcoder

$keyparmin = $argv[1];
$dict = $argv[2];
if (!$dict) {
 echo "USAGE: php test_simple.php <word> <dict>\n";
 exit(1);
}
$dbg = true;
$fileout = "dbg_apidev.txt";
unlink($fileout); // deletes the file.
dbgprint($dbg, "keyparmin=$keyparmin\n");

$ss = new Simple_Search($keyparmin,$dict);

$keysin = $ss->keysin;

 $nss = count($keysin);
 $dbg=true;
 dbgprint($dbg,"$nss keys from simple search\n");
 for($i=0;$i<$nss;$i++) {
  dbgprint($dbg,"$i {$keysin[$i]}\n");
 }
 
 echo "$nss records written to $fileout\n";

?>
