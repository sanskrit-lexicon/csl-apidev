<?php
// Exclude WARNING messages also, to solve Peter Scharf Mac version.
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
?>
<?php
require_once('dbgprint.php');
require_once('parm.php');  // loads transcoder
require_once('getword_data.php');
require_once('dispitem.php');

class api0_hws0Class {
 public $getParms,$matches,$table1,$status,$basicOption;
 public $xmlmatches;
 public function __construct() {
  // initialize result 
  $this->init_result();
  $this->getParms = new Parm();
  if ($this->result['status'] != 200) {
   return;  // do no more
  }
  $this->api0_hws0();
 }
 public function api0_hws0() {
  dbgprint(true,"api0_hws0 begins\n");
  $this->basicOption = $this->getParms->basicOption;
  $temp = new Getword_data();
  $this->matches = $temp->matches;  # html
  #$this->table1 = $this->getword_html();
  $xmlmatches = $temp->xmlmatches;
  $htmlmatches = $this->matches; // parallel to xmlmatches
  $nxml = count($xmlmatches);
  dbgprint(true,"nxml=$nxml\n");
  if ($nxml == 0) {
   $this->result['status'] = 404;
   $this->result['errorinfo']="No data found";
   return;
  }
  // Fill $matches array (currently empty array)
  $nmatches = count($xmlmatches);
  dbgprint(true,"nmatches=$nmatches<br/>\n");
  $this->result['nmatches'] = $nmatches;
  $matches = array();
  for($i=0;$i<$nmatches;$i++) {
   $xmlmatch = $xmlmatches[$i];
   list($key0,$lnum0,$xmldata0) = $xmlmatch;
   $htmlmatch = $htmlmatches[$i];
   list($key0a,$lnum0a,$html) = $htmlmatch;
   $obj = $this->parsehtml($html);
   $pc = $obj['pc'];
   $hcode = $obj['hcode'];
   $key2 = $obj['key2'];
   $html0 = $obj['html'];
   $match = array(); 
   $match['key1'] = $key0;
   $match['key2'] = $key2;
   $match['lnum'] = $lnum0;
   $match['xml'] = $xmldata0;
   $match['pc'] = $pc; 
   $match['hcode'] = $hcode;
   $filter = $this->xParm['filter'];
   $html1 = transcoder_processElements($html0,"slp1",$filter,"SA");
   $match['html'] = $html1;  # not transcoded
   // estimate text from $html1
   #$txt1 = transcoder_processElements($html0,"slp1",$filter,"SA");
   $txt = preg_replace('|<.*?>|','',$html1);
   
   $match['txt'] = $txt; 
  $matches[] = $match;
  }
  $this->result['matches'] = $matches;
 }
 public function parsehtml($h) {
  /* $h = <info>$info</info><body>$body</body>
   if dict != mw, then $info = pc
   if dict == mw, then $info = pc:Hcode:key2
  */
  $ans = array();
  if (!preg_match('|<info>(.*?)</info><body>(.*?)</body>|',$h,$matches)) {
   //error condition
   $ans['pc']='';
   $ans['hcode']='';
   $ans['key2']='';
   $ans['html']='<p>api0_hws0Class: parsehtml problem</p>';
   return $ans;
  }
  $info = $matches[1];
  dbgprint(true,"info = $info\n");
  $body = $matches[2];
  $ans['html']=$body;
  # $info is a colon-delimited sequence
  $infos = explode(':',$info);
  $ans['pc']=$infos[0];
  if (count($infos) >= 3) {
   // mw only for now. It seems to end in ':', so count($infos) == 4
   $ans['hcode']=$infos[1];
   $ans['key2']=$infos[2];
  }else{
   // other dictionaries.
   $ans['hcode']='H1';
   $ans['key2']='';
  }
  return $ans;
 }
 public function init_result() {
  $result = array();
  $result['status'] = 200; // assume no error
  $result['errorinfo'] = ''; // assume no error
  $result['nmatches'] = 0;
  $result['matches'] = array();
  $this->result = $result;
  $this->result['request'] = $this->init_request();
 }
 public function key_error($key) {
  $this->result['status'] = 404;
  $this->result['errorinfo'] = "hws0 key error: $key";
 }
 public function init_request() {
  // initialize $this->result['request'] and $this->xParm;
  $xParm = array();
  $getParms = new Parm();
  $this->getParms = $getParms;
  $xParm['keyin'] = $getParms->keyin;   # $_REQUEST['key']
  $xParm['keyin1'] = $getParms->keyin1; # preprocessed keyin (unicode)
  $xParm['key'] = $getParms->key;       # transcoded keyin1
  
  $xParm['getParms'] = $getParms;
  $xParm['english'] = $getParms->english;
  $xParm['filterin'] = $getParms->filterin;
  $xParm['filter'] = $getParms->filter;
  $request = array();
  $request['dict'] = $getParms->dict;
  if (isset($_REQUEST['key'])) {
   $term = $xParm['keyin'];
  }else {
   $term = '';
  }
  $request['key'] = $term;
  $request['keyslp1'] = $xParm['key'];
  $request['input'] = $getParms->filterin;
  $request['output'] = $getParms->filter;
  $request['accent'] = $getParms->accent;

  if ($getParms->status != 200) {
   $this->result['status'] = $getParms->status;
   $this->result['errorinfo'] = $getParms->errorinfo;
  }else if ($term == '') {
    $this->key_error($term);
  }
  $this->xParm = $xParm;
  return $request;
 }

 public function getword_html() {
  $getParms = $this->getParms;
  $matches  = $this->matches;
  $dbg=false;
  $nmatches = count($matches);
  $key = $getParms->key;
  $keyin = $getParms->keyin1;
  if ($nmatches == 0) {
   $table1 = '';
   $table1 .= "<h2>not found: '$keyin' (slp1 = $key)</h2>\n";
  }else {
   $table = $this->getwordDisplay($getParms,$matches);
   dbgprint($dbg,"getword\n$table\n\n");
   $filter = $getParms->filter;
   $table1 = transcoder_processElements($table,"slp1",$filter,"SA");
  }
  return $table1;
 }
 public function getwordDisplay($parms,$matches) {
 // June 4, 2015 -- assume $matches is filled with records of form:
 //   $matches[$i] == array(key,lnum,rec) -
 //   rec = <info>pg</info><body>html</body>
 // June 14, 2015 for MW, info = pg:Hcode:key2a:hom
 // July 11, 2015  Use 'Parm' object for calling sequence
 // Aug 17, 2015 Remove use of _GET['options']. Always use $options='2'
 $key = $parms->key;
 $dict = strtoupper($parms->dict);
 if(isset($_REQUEST['dispopt'])) {
  $temp = $_REQUEST['dispopt'];
  if (in_array($temp,array('1','2','3'))) {
   $options = $temp;
  }else {
   $options = '2';
  }
 }else { # dispopt not set
  $options = '2'; // $parms->options;
 }

 /* 
    Sep 2, 2018. output link to basic.css depending on $parms->dispcss.
    Aug 4, 2020.  For webtc, never put out basic.css
 */
 $dictinfo = $parms->dictinfo;
 $webpath =  $dictinfo->get_webPath();

 if (isset($parms->dispcss) && ($parms->dispcss == 'no')) {
  $linkcss = "";
 }else {
  $linkcss = "<link rel='stylesheet' type='text/css' href='css/basic.css' />";
 }
 if ($this->basicOption) {
  $linkcss = "";
 }
if ($options == '3') {
 $output = '';
}else {
 $output = <<<EOT
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
$linkcss
</head>
<body>
EOT;
}
 $english = $parms->english; 
/* use of 'CologneBasic' is coordinated with basic.css
  So basic.css won't interfere with the user page.  This
  assumes that the id 'CologneBasic' is unused on user page.
*/
 if (($options == '1')||($options == '2')) {
  $table = "<div id='CologneBasic'>\n";
  if ($this->basicOption) {
   if ($english) {
    $table = "<div id='CologneBasic'>\n<h1>&nbsp;$key</h1>\n";
   } else {
    $filter = $parms->filter;
    if ($filter == 'deva') {
     $class = 'sdata_siddhanta';
    }else {
     $class = 'sdata';
    }
    $table = "<div id='CologneBasic'>\n<h1>&nbsp;<span class='$class'><SA>$key</SA></span></h1>\n";
   }
  }
 }else if ($options == '3') {
  $table = "<div id='CologneBasic'>\n";  
 }else {
  $table = "<div id='CologneBasic'>\n";  
 }

 $table .= "<table class='display'>\n";
 $ntot = count($matches);
 $dispItems=array();
 $dbg=false;
 for($i=0;$i<$ntot;$i++) {
  $dbrec = $matches[$i];
  dbgprint($dbg,"disp.php. matches[$i] = \n");
  for ($j=0;$j<count($dbrec);$j++) {
   dbgprint($dbg,"  [$j] = {$dbrec[$j]}\n");
  }
  $dispItem = new DispItem($dict,$dbrec);
  if ($dispItem->err) {
   $keyin = $parms->keyin;
   return "<p>Could not find headword $keyin in dictionary $dict</p>";
  }
  $dispItems[] = $dispItem;
 }  
 // modify dispitem->keyshow, (when to show the key)
 for($i=0;$i<$ntot;$i++) {
  $dispItem=$dispItems[$i];
  if ($i==0) {//show if first item
  }else if ($dispItem->hom) { // show if a homonym
  }else if (strlen($dispItem->hcode) == 2) { // show; Only restrictive for MW
  }else if (($i>0) and ($dispItem->key== $dispItems[$i-1]->key)){ // don't show
   $dispItem->keyshow = ''; 
  }
 }
 // In the 'alt' version of MW,  not all of the keys shown are the same.
 // In this case, try adding css (shading?) to distinguish the keys that are
 // NOT the same as $parms->key.
 for($i=0;$i<$ntot;$i++) {
  $dispItem=$dispItems[$i];
  if ($dispItem->key != $parms->key) {
   $dispItem->cssshade=true;
  }
 } 
 // Aug 15, 2015. Set firstHom instance variable to True where needed
 $found=False;
 // First, set firstHom always false
 for($i=0;$i<$ntot;$i++) {
  $dispItem=$dispItems[$i];
  $dispItem->firstHom=False;
 }
 // Next, set it True on first record with hom
 for($i=0;$i<$ntot;$i++) {
  $dispItem=$dispItems[$i];
  if ($dispItem->hom ) {
    $dispItem->firstHom=true;
    break;
  }
 } 
 
 // Generate output
 $dispItemPrev=null;
 for($i=0;$i<$ntot;$i++) {
  $dispItem = $dispItems[$i];
  if ($options == '1') {
   $table .= $dispItem->basicDisplayRecord1($dispItemPrev);
  }else if ($options == '2') {
   $table .= $dispItem->basicDisplayRecord2($dispItemPrev);
  }else{
   $table .= $dispItem->basicDisplayRecordDefault($dispItemPrev);
  }
  $dispItemPrev=$dispItem;
 }
 $table .= "</table>\n";
 $output .= $table;
 $output .= "</div> \n";
 return $output;
}
}
?>
