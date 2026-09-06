<?php
/* fork_sync_check.php — drift guard for the hand-synced fork files shared
   with csl-websanlexicon (readme1_websanlexicon.txt, docs/WEB_BACKEND_MANUAL.md §5).

   Edits are made in csl-websanlexicon first, then transferred here via that
   repo's v02/apidev_copy.sh. This tool is the mechanical drift check the
   manual asks for: byte-compare the three fork files against the twin repo.

   Usage:
     php tools/fork_sync_check.php [path-to-csl-websanlexicon]
   Default twin root: ../csl-websanlexicon next to this repo clone.
   Twin subpath: v02/makotemplates/web/webtc/<file> (per the 07-11-2024
   diff examples in readme1_websanlexicon.txt).

   Exit codes: 0 in sync (or twin absent -> skip), 1 DRIFT, 2 usage error.
   H4212.
*/

$files = array('basicadjust.php', 'basicdisplay.php', 'getword_data.php');
$subpath = 'v02/makotemplates/web/webtc';

$repo = dirname(__DIR__);
$twinRoot = (isset($argv[1]) && $argv[1] !== '') ? rtrim($argv[1],'/\\')
 : dirname($repo) . DIRECTORY_SEPARATOR . 'csl-websanlexicon';

if (!is_dir($twinRoot)) {
 echo "skip: twin repo not found at $twinRoot (clone csl-websanlexicon or pass its path)\n";
 exit(0);
}

$drift = 0;
foreach ($files as $f) {
 $a = $repo . DIRECTORY_SEPARATOR . $f;
 $b = $twinRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $subpath) . DIRECTORY_SEPARATOR . $f;
 if (!file_exists($a)) {
  echo "ERROR: missing here: $f\n";
  $drift++;
  continue;
 }
 if (!file_exists($b)) {
  echo "ERROR: missing in twin: " . $b . "\n";
  $drift++;
  continue;
 }
 $ha = md5_file($a);
 $hb = md5_file($b);
 if ($ha === $hb) {
  echo "ok      $f (" . filesize($a) . " bytes)\n";
 } else {
  echo "DRIFT   $f  here=" . $ha . " twin=" . $hb . "\n";
  echo "        run csl-websanlexicon v02/apidev_copy.sh, or diff:\n";
  echo "          diff \"$a\" \"$b\"\n";
  $drift++;
 }
}

echo $drift ? "DRIFT: $drift file(s) out of sync\n" : "in sync: all " . count($files) . " fork files byte-identical\n";
exit($drift ? 1 : 0);
?>
