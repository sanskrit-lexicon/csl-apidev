<?php
error_reporting(E_ALL & ~E_NOTICE );
?>
<?php 
/* basic_xml_html.php
 Previously method getword_data_html_adapter in getword_data.php
*/
class Basic_xml_html {
 public function __construct($rec,$dict,$getParms) {
  // rec is array of length 3 ($m['key'],$m['lnum'],$m['data']) 
  list($key0,$lnum0,$data0) = $rec;
  $this->html = $this->getword_data_html_adapter($key0,$lnum0,$data0,$dict,$getParms);
 }
public function getword_data_html_adapter($key,$lnum,$data,$dict,$getParms)
{
 require_once('basicadjust.php');
 require_once('basicdisplay.php');
 $matches1=array($data);
 $adjxml = new BasicAdjust($getParms,$matches1);
 $matches = $adjxml->adjxmlrecs;
 $filter = $getParms->filter;
 $display = new BasicDisplay($key,$matches,$filter,$dict);
 $table = $display->table;
 $tablines = explode("\n",$table); 
 $ntablines = count($tablines);
 /* $table is a string with 6 lines, or 7 lines when dict==mw
  Only indices 2,3,4 of $tablines are used here.
  The exact structure of these lines is complicated.
  STRUCTURE FOR MW
  $idx  $tablines[$idx] description
   0    <h1 class='$sdata'>&nbsp;<SA>$key2</SA></h1>
   1   <table class='display'>
   2  <tr><td>Hx and link to scan<br> (but for Hxy cases no Hxy)
   a) When there are Whitney links or Westegaard links,
   3   The Whitney/Westergaard links<br>
   4   html for the body of the entry
   5   </td><td>spaces</td></tr></table>
   6   empty line
   b) When there are no links
   3   html for the body of the entry
   4   </td><td>spaces</td></tr></table>
   5   empty line
  STRUCTURE FOR non-mw, and non-English headwords (i.e., not ae,mwe, bor, mw)
   0    <h1 class='$sdata'>&nbsp;<SA>$key2</SA></h1>
   1   <table class='display'>
   2  <tr><td>{KEY} {link to scan}  (but for Hxy cases no Hxy)
   3  <br> html for the body of the entry
   4  </td><td>spaces</td></tr></table>
   5   empty line

  STRUCTURE FOR  ae,mwe, bor,
   0    <h1>&nbsp;$key2</h1>
   1   <table class='display'>
   2  <tr><td>{KEY} {link to scan}  (but for Hxy cases no Hxy)
   3  <br> html for the body of the entry
   4  </td><td>spaces</td></tr></table>
   5   empty line
 */
 if (($ntablines != 6)&& ($ntablines != 7)){
  #dbgprint(true,"html ERROR 1: actual # lines in table = $ntablines\n");
  for ($i=0;$i<$ntablines;$i++) {
   #dbgprint(true,"tablines[$i]=" .$tablines[$i]."\n");
  }
  exit(1);
 }

 $info = $tablines[2];
 #$body = $tablines[3];
 if ($ntablines == 6) {
  $body = $tablines[3];
 }else {  //$ntablines == 7
  $body = $tablines[3] . $tablines[4];
 }

 # adjust body
 $body = preg_replace('|<td.*?>|','',$body);
 $body = preg_replace('|</td></tr>|','',$body);
 if ($dict == 'mw') {
  // in case of MW, we remove [ID=...]</span>
  $body = preg_replace('|<span class=\'lnum\'.*?\[ID=.*?\]</span>|','',$body);
 }
 # adjust $info - keep only the displayed page
 if ($dict == 'mw') {
  if(!preg_match('|>([^<]*?)</a>,(.*?)\]|',$info,$matches)) {
   #dbgprint(true,"html ERROR 2: \n" . $info . "\n");
   exit(1);
  }
  $page=$matches[1];
  $col = $matches[2];
  $pageref = "$page,$col";
 }else {
  if(!preg_match('|>([^<]*?)</a>|',$info,$matches)) {
   #dbgprint(true,"html ERROR 2: \n" . $info . "\n");
   exit(1);
  }
  $pageref=$matches[1];
 }
 if ($dict == 'mw') {
  list($hcode,$key2,$hom) = $this->adjust_info_mw($data); 
  # construct return value as colon-separated values
  $infoval = "$pageref:$hcode:$key2:$hom";
  $ans = "<info>$infoval</info><body>$body</body>";
 }else {
  # construct return value
  $ans = "<info>$pageref</info><body>$body</body>";
 }
 return $ans;
}
public function adjust_info_mw($data) {
 # In case of MW, also retrieve Hcode and hom from head of $data
 $hom='';
 if (preg_match('|</key2><hom>(.*?)</hom>|',$data,$matches)) {
  $hom = $matches[1];
 }
 $hcode='';
 if (preg_match('|^<(H.*?)>|',$data,$matches)) { // always matches
  $hcode=$matches[1];
 }
 $key2='';
 if (preg_match('|<key2>(.*?)</key2>|',$data,$matches)) {
  $key2 = $matches[1];
 }
 $key2a = $this->adjust_key2_mw($key2);
 return array($hcode,$key2a,$hom);
}
public function adjust_key2_mw($key2) {
 $ans = preg_replace('|--+|','-',$key2);  // only 1 dash
 $ans = preg_replace('|<sr1?/>|','~',$ans); # ~ not in key1 for MW (?)
 $ans = preg_replace('|<srs1?/>|','@',$ans); # @ not in SLP1
 // Leave some xml in place:
 // <root>kf</root>
 // <root/>daMh
 // dA<hom>1</hom>
 // <shortlong/>
 $ans1 = preg_replace('|</?root/?>|','',$ans);
 $ans1 = preg_replace('|</?hom>|','',$ans1);
 $ans1 = preg_replace('|<shortlong/>|','',$ans1);
 if (preg_match('|<|',$ans1)) {
  #dbgprint(true,"adjust_key2: $ans1\n");
  exit(1);
 }
 return $ans;
 $ans = preg_replace('||','',$ans);
 $ans = preg_replace('||','',$ans);
 return $ans;
} 

}
