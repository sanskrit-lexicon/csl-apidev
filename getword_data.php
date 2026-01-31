<?php
error_reporting(E_ALL & ~E_NOTICE );
?>
<?php 
/* getword_data.php
 class to get the html data for getword.php
*/
require_once('dbgprint.php');
require_once('dal.php');
require_once('basicadjust.php');
require_once('basicdisplay.php');

class Getword_data {
 /* $matches contains array of records. 
   Each record is an array with three elements:
   - key
   - lnum (cologne id)
   - computed html string
 */
 public $matches, $basicOption;
 public $xmlmatches;
 //public $basicdisplaydbg;
 public function __construct($basicOption = true) {
 $dbg=false;
 $getParms = new Parm();
 $this->basicOption = $basicOption;
 $dict = $getParms->dict;
 $dal = new Dal($dict);
 $key = $getParms->key;
  // xmlmatches is array of records rec, where
  // rec is array with 3 items:
  //  0: key0  the headword of the record (usu. but not always same as key)
  //  1: lnum0 The Cologne id
  //  2: data:  xml string from xxx.xml
 $xmlmatches = $dal->get1_mwalt($key); 
 $this->xmlmatches = $xmlmatches;
 $dal->close();

  $xmldata = [];
  for ($i=0;$i<count($xmlmatches);$i++) {
   $xmlmatch = $xmlmatches[$i];
   list($key0,$lnum0,$xmldata0) = $xmlmatch;
   $xmldata[] = $xmldata0;
  }
  
  $adjxml = new BasicAdjust($getParms,$xmldata);
  $adjmatches = $adjxml->adjxmlrecs;
  $htmlmatches = [];
  //$htmlbasics = []; // for debugging
  for($i=0;$i<count($xmlmatches);$i++) {
   $xmlmatch = $xmlmatches[$i];
   list($key0,$lnum0,$xmldata0) = $xmlmatch;
   $adjxmldata0 = $adjmatches[$i];
   $html = $this->getword_data_html_adapter($key0,$lnum0,$adjxmldata0,$dict,$getParms,$xmldata0);
   dbgprint(false,"getword_data:  html=\n  $html\n");
   //$htmlbasics[] = $this->basicdisplaydbg;
   $html1 = $html;
   // dbgprint(false,"getword_data: i = $i, html=\n$html\n\n");
   // 10-23-2023 For koshas, use $L instead of $lnum0.
   // 07-09-2024 For koshas, use $L1
   // Also,use L1 for mw (to get sup,rev)
   if (in_array($dict,array('mw','abch', 'acph', 'acsj'))) {
    if(preg_match('|<L1>(.*?)</L1>|',$html,$tempmatch)) {
     $lnum0 = $tempmatch[1];
     // remove L1 element from html1
     $html1 = preg_replace('|<L1>(.*?)</L1>|','',$html);
    }
   }
   $htmlmatches[] = array($key0,$lnum0,$html1);    
  }
 if ($dbg) {
  dbgprint($dbg,"getword_data returns:\n");
  for($i=0;$i<count($htmlmatches);$i++) {
    dbgprint($dbg,"xmldata[$i]=\n  {$xmldata[$i]}\n\n");
    dbgprint($dbg,"adjmatches[$i]=\n  {$adjmatches[$i]}\n\n");
    // dbgprint($dbg,"htmlbasics[$i]=\n  {$htmlbasics[$i]}\n\n");
    dbgprint($dbg,"htmlmatches[$i][0]= {$htmlmatches[$i][0]}\n"); 
    dbgprint($dbg,"htmlmatches[$i][1]= {$htmlmatches[$i][1]}\n"); 
    dbgprint($dbg,"htmlmatches[$i][2]=\n {$htmlmatches[$i][2]}\n"); 
   }
   
  }
 $this->matches = $htmlmatches;
}
/* ------------------------------
  getword_data_html_adapter and related functions
*/
public function getword_data_html_adapter($key,$lnum,$adjxml,$dict,$getParms,$xmldata)
{
 // 08-07-2020.  This is the only place where  BasicDisplay is called.
 // We don't need to have arrays of strings, but only one string
 // BasicDisplay is written to allow a string for the second argument.

 $filter = $getParms->filter;
 dbgprint(false,"getword_data_html_adapter, adjxml=\n  $adjxml\n");
 $display = new BasicDisplay($key,array($adjxml),$filter,$dict);
 $row1 = $display->row1;
 $row1x = $display->row1x; 
 $row = $display->row;
 $info = $row1;
 if ($row1x == '') { // True except for some mw verbs
  $body = "$row";
 } else {
  $body = "$row1x<br>$row";
 }
 $dbg=false;
 dbgprint($dbg,"adapter\n");
 dbgprint($dbg,"info = $info\n");
 dbgprint($dbg,"body = $body\n");
 // $this->basicdisplaydbg = $body;
 if (in_array($dict,['abch', 'acph', 'acsj'])) {
  // no adjust body
 }else {
  // adjust body
  $body = preg_replace('|<td.*?>|','',$body);
  $body = preg_replace('|</td></tr>|','',$body);
  if ($dict == 'mw') {
   // in case of MW, we remove [ID=...]</span>
   $body = preg_replace('|<span class=\'lnum\'.*?\[ID=.*?\]</span>|','',$body);
  }
 }
 // adjust $info - keep only the dislayed page
 if ($dict == 'mw') {
  if(!preg_match('|>([^<]*?)</a>,(.*?)\]|',$info,$matches)) {
   dbgprint(false,"html ERROR 2: \n" . $info . "\n");
   exit(1);
  }
  $page=$matches[1];
  $col = $matches[2];
  $pageref = "$page,$col";
 }else {
  if(!preg_match('|>([^<]*?)</a>|',$info,$matches)) {
   dbgprint(false,"html ERROR 2: \n" . $info . "\n");
   exit(1);
  }
  $pageref=$matches[1];
 }
 if ($dict == 'mw') {
  // 08-17-2024. $hui 
  list($hcode,$key2,$hom,$hui) = $this->adjust_info_mw($xmldata); 
  // construct return value as colon-separated values
  
  if ($this->basicOption) {
   //dbgprint(false,"getword_data: changing hom to blank; $key2,$hom\n");
   $hom="";
  }
  $infoval = "$pageref:$hcode:$key2:$hom:$hui";
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
 // 08-17-2024
 $hui = '';
 if (preg_match('|<info hui="(.*?)"/>|',$data,$matches)) {
  $hui = $matches[1];
 }

 $key2a = $this->adjust_key2_mw($key2);
 return array($hcode,$key2a,$hom,$hui);
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
  //dbgprint(false,"adjust_key2: $ans1\n");
  exit(1);
 }
 return $ans;
 $ans = preg_replace('||','',$ans);
 $ans = preg_replace('||','',$ans);
 return $ans;
}
}
?>
