<?php
// Exclude WARNING messages also, to solve Peter Scharf Mac version.
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
?>
<?php
require_once('getwordXmlClass.php');
require_once('dbgprint.php');
if (isset($_GET['callback'])) {
 header('content-type: application/json; charset=utf-8');
}
header("Access-Control-Allow-Origin: *");

#echo "<br>url=$url";
#$apiObj = API($url);
function getapiCall($url) {
  $temp = new API($url);
  $ans = $temp->ans;
  $table1 = $ans['final'];
  #print_r($table1);
  if (isset($_GET['callback'])) {
   $json = json_encode($table1);
   echo "{$_GET['callback']}($json)";
  }else {
   echo json_encode($table1);
  }
 }
#$url = $_REQUEST['url'];   # problem with ? (wildcard for search)
$uri = $_SERVER['REQUEST_URI'];  
// e.g. uri=/cologne/api/dicts/mw/reg/rA?an/slp1/devanagari
// delete up through /api/
$url = preg_replace('|^.*?/api/|','',$uri);
#dbgprint(true,"api_trial.php: uri=$uri\n");
#dbgprint(true,"api_trial.php: url=$url\n");
 getapiCall($url);

?>
<?php
/*
api.route('/' + apiversion + '/dicts/<string:dictionary>/lnum/<string:lnum>')
api.route('/' + apiversion + '/dicts/<string:dictionary>/hw/<string:hw>')
api.route('/' + apiversion + '/dicts/<string:dictionary>/reg/<string:reg>')
api.route('/' + apiversion + '/dicts/<string:dictionary>/hw/<string:hw>/<string:inTran>/<string:outTran>')
api.route('/' + apiversion + '/dicts/<string:dictionary>/reg/<string:reg>/<string:inTran>/<string:outTran>')
api.route('/' + apiversion + '/hw/<string:hw>')
api.route('/' + apiversion + '/hw/<string:hw>/<string:inTran>/<string:outTran>')
api.route('/' + apiversion + '/reg/<string:reg>')
api.route('/' + apiversion + '/reg/<string:reg>/<string:inTran>/<string:outTran>')
*/
class API {
 public $version;
 public $ans;
 public function __construct($url)  {
  $this->ans = array(); //associative array 
  $this->ans['status'] = 404; // problem
  $this->ans['url'] = $url;
  $this->ans['final'] = "";
  #$this->ans['html'] = "";
  $flag = $this->parse_url();
  if (! $flag) {return;}
  $this->ans['final'] = $this->block2();
 }
 public function  parse_url() {
  $url_parts = explode("/",$this->ans['url']);
  # first (optional) parameter is version
  if (preg_match('/^v[0-9.]+$/',$url_parts[0])) {
   $this->version = $url_parts[0];
   $url_parts = array_slice($url_parts,1); # remove 1st
  } else {
   $this->version = "v0.0.1"; // latest version?
  }
  $this->ans['version'] = $this->version;
  # dicts
  #print_r($url_parts); echo "<br/>";
  if($url_parts[0] == 'dicts') {
   $url_parts = array_slice($url_parts,1);
   if (count($url_parts) == 0) { // error
    return False;
   }
   $this->ans['dict'] = $url_parts[0];
   $url_parts = array_slice($url_parts,1);
  }else {
  // if first parameter is not 'dicts', then it is implied to be 'all'
   $this->ans['dict'] = 'all';
  }
  // dictlist is an array of dictionary codes
  $cologne_dicts = array('acc', 'ae', 'ap90', 'ben', 'bhs', 'bop', 'bor', 'bur', 'cae', 'ccs', 'gra', 'gst', 'ieg', 'inm', 
    'krm', 'mci', 'md', 'mw', 'mw72', 'mwe', 'pe', 'pgn', 'pui', 'pw', 'pwg', 'sch', 'shs', 'skd', 'snp', 
    'stc', 'vcp', 'vei', 'wil', 'yat');
  if ($this->ans['dict'] == 'all') {
   $this->ans['dictlist'] = $cologne_dicts;
  }else if (! in_array($this->ans['dict'],$cologne_dicts)) {
   # bad dictionary code
   return False;
  }else {
   $this->ans['dictlist'] = array($this->ans['dict']);
  }
  if (count($url_parts) == 0) { // error
    return False;
  }
  // next parameter hw or reg or lnum
  #$this->ans['hw'] = null;
  #$this->ans['reg'] = null;
  #$this->ans['lnum'] = null;
  if (! in_array($url_parts[0],array('hw','reg','lnum'))) {
   return False; // error
  }
  if (count($url_parts) == 1) {
   // error. no value given  for hw, etc.
   return False;
  }
  $this->ans[$url_parts[0]] = $url_parts[1];
  $this->ans['key'] = $this->ans['hw'];  # key is a 'synonym' of 'hw' that is used in getword api
  $this->ans['regex'] = $this->ans['reg']; # regex is used in getword_xml
  $url_parts = array_slice($url_parts,2);
  // The other url parts, if any, are the inTran and outTran parts
  if (count($url_parts) == 0) {
   // set defaults for inTran and outTran. Name them acc. to getword api
   $this->ans['input'] = 'slp1';
   $this->ans['output'] = 'slp1';
  }else if (count($url_parts) != 2) {
   # error condition
   return False;
  }else {
   $inTran = $url_parts[0];
   $outTran = $url_parts[1];
   $input = $inTran;
   $output = $outTran;
   $this->ans['inTran'] = $input;
   $this->ans['outTran'] = $output;
   if ($inTran == 'devanagari') {$input = 'deva';}
   if ($outTran == 'devanagari') {$output = 'deva';}
   $this->ans['input'] = $input;
   $this->ans['output'] = $output;   
  }
  return True;
 }
 public function block2() {
  /* construct $_REQUEST parameters for getword_xml call. 
     Alternately could use curl.
  */
 $possible_parms = ['key','lnum','regex','input','output'];
 foreach($possible_parms as $parm) {
  if (isset($this->ans[$parm])) {
   $value = $this->ans[$parm];
   $_REQUEST[$parm] = $value;
  }
 }
 $dictlist = $this->ans['dictlist'];
 $final = array(); //associative array, dictionary code is key
 $dbg=False;
 foreach($dictlist as $dict) {
  $_REQUEST['dict'] = $dict;
  $temp = new GetwordXmlClass();
  $jsonobj = $temp->json;
  $obj = json_decode($jsonobj,$assoc=True);
  $status = $obj['status'];
  dbgprint($dbg,"api_trial.php: $dict, $status\n");
  $result=array();
  if ($status  == 200 ) {
   #jsonobj = r.json()
   $datarr = $obj['xml'];  # list of data string
   $htmlarr = $obj['html'];
   for ($idata = 0; $idata < count($datarr); $idata++) {
    $data = $datarr[$idata];
    $html = $htmlarr[$idata];
    $result[] = $this->block1($data,$html);
   }
   $final[$dict] = $result;
  } 
 }
 #$final_json = json_encode($final);
 return $final;
 }
 public function block1($data,$html) {
 if (preg_match('|<key1>(.*?)</key1>|',$data,$matches)) {
  $key1 = $matches[1];
 } else {
  $key1 = 'key1 ?';
 }
 if (preg_match('|<key2>(.*?)</key2>|',$data,$matches)) {
  $key2 = $matches[1];
 } else {
  $key2 = 'key2 ?';
 }
 if (preg_match('|<tail>.*?<L.*?>(.*?)</L>|',$data,$matches)) {
  $lnum = $matches[1];
 } else {
  $lnum = 'lnum ?';
 }
 if (preg_match('|<tail>.*?<L.*?>(.*?)</L>|',$data,$matches)) {
  $lnum = $matches[1];
 } else {
  $lnum = 'lnum ?';
 }
 if (preg_match('|<tail>.*?<pc.*?>(.*?)</pc>|',$data,$matches)) {
  $pc = $matches[1];
 } else {
  $pc = 'pc ?';
 }
 if (preg_match('|<body>(.*?)</body>|',$data,$matches)) {
  $body = $matches[1];
 } else {
  $body = 'body ?';
 }
 $text = $body;
 $htmla = preg_replace('|^<info>.*?</info>|','',$html);
 if (preg_match('|<body>(.*?)</body>|',$htmla,$matches)) {
  $text1 = $matches[1];
 } else {
  $text1 = 'html body ?';
 }
 $ans = array('key1'=> $key1, 'key2'=> $key2, 'pc'=> $pc, 'text'=> $text, 
    'modifiedtext'=> $text1, 'lnum'=> $lnum );
 return $ans;
}
}
?>

