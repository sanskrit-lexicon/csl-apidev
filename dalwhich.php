<?php
/* dalwhich.php
 code snippet to use either Dal or Dalraw, depending on choice of dir
*/
require_once("dalraw.php");  
require_once("dal.php");  
require_once('dbgprint.php');
function dalwhich($dict) {
 $dbg=false;
 $rawdicts =array('cae','bur','stc','gra','pwg','mw','skd','ae');
 if (in_array($dict,$rawdicts)) {
  $dal = new Dalraw($dict);
 }else {
  $dal = new Dal($dict);
 }
 dbgprint($dbg,"dalwhich sqlite file {$dal->sqlitefile}\n");
 return $dal;
}
?>

