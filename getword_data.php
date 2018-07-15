<?php
error_reporting(E_ALL & ~E_NOTICE );
?>
<?php 
/* getword_data.php
 functions to get the html data for getword.php
*/
require_once('dbgprint.php');
function getword_html_data($getParms,$dal) {
 
 /* $matches is array. each element is 3-element array
   list($key1,$lnum1,$data1)
 */
 $key = $getParms->key;
 dbgprint($dbg,"getword.php #2: key=$key, dict=$dict \n");
 if (strtolower($dict) == 'mw') {
  $matches = $dal->get1_mwalt($key); // Jul 19, 2015
 }else {
  $matches= $dal->get1($key); 
 }
 # accent-adjustment
 require_once("accent_adjust.php");
 $dictinfo = $getParms->dictinfo;
 $dictup = $dictinfo->dictupper;
 $accent = $getParms->accent;
 for($i=0;$i<count($matches);$i++) {
  $matches[$i] = accent_adjust($matches[$i],$accent,$dictup);
 }
 return $matches;
}

function getword_html_data_raw($getParms,$dal){
 $dbg=false;
 $dict = $getParms->dict;
 dbgprint($dbg,"getword.php #1 getword_html_raw\n");
 
 
 /* $matches0 is array. each element is 3-element array
   list($key1,$lnum1,$data1)
 */
 $key = $getParms->key;
 dbgprint($dbg,"getword.php #2: key=$key, dict=$dict \n");
 if (strtolower($dict) == 'mw') {
  $matches0 = $dal->get1_mwalt($key); // Jul 19, 2015
 }else {
  $matches0= $dal->get1($key); 
 }
 $nmatches = count($matches0);
 dbgprint($dbg,"getword.php #3: nmatches=$nmatches\n");
 // adjust xml
 $matches = array();
 foreach($matches0 as $match0){
  list($key0,$lnum0,$data0) = $match0;
  $html = getword_html_data_raw_adapter($key0,$lnum0,$data0,$dict,$getParms);
  $matches[] = array($key0,$lnum0,$html);
 }
 return $matches;
}
function getword_html_data_raw_adapter($key,$lnum,$data,$dict,$getParms)
{
 require_once('basicadjust.php');
 require_once('basicdisplay.php');
 //global $pagecol;
 //$pagecol = ""; // otherwise, not all pc data is reported in $info below
 $matches1=array($data);
 # note $filter is undefined
 $adjxml = new BasicAdjust($getParms,$matches1);
 $matches = $adjxml->adjxmlrecs;
 $filter = $getParms->filter;
 dbgprint(tru,"getword. filter=$filter\n");
 $display = new BasicDisplay($key,$matches,$filter,$dict);
 $table = $display->table;
 #$table = basicDisplay($key,$matches,$filter);
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
