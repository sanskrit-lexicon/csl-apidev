<?php
/* dalwhich.php
 code snippet to use either Dal or Dalraw, depending on choice of dir
 08-06-2018. Changed so Dalraw ALWAYS used.  This implies that
 the xxxhtml.sqlite files (created in pywork/html) are no longer needed.
*/
require_once("dalraw.php");  
require_once("dal.php");  
require_once('dbgprint.php');
function dalwhich($dict) {
 $dbg=false;
 $dal = new Dalraw($dict);
 return $dal;
 /* previous code allows some dictionaries to use
    Dal, which accesses xxxhtml.sqlite
 */
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

