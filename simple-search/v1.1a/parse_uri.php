<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
require_once('../../dbgprint.php');
function parse_uri($htaccess) {
 /* uri: uri = /cologne/simple[/DICT/KEY/INPUT_SIMPLE/OUTPUT/ACCENT]
   all are optional, but the order is assumed
   DICT default from cookie if present, otherwise mw
   KEY  '' (empty string or null)
   INPUT_SIMPLE:  default value is 'default'. Otherwise slp1,deva,iast,hk,itrans
   OUTPUT: default from cookie if present, otherwise 'deva'. 
           Otherwise slp1,..,itrans
   ACCENT: default from cookie if present, default = no.  values yes or (NOT yes)
 */
 $uri = $_SERVER['REQUEST_URI'];
 //$_REQUEST['input'] = 'simple'; // force this 'parm'
 //dbgprint(true,"parse_uri: uri=$uri\n");
 //echo("uri = $uri<br/>\n");
 // delete up through /simple/
 $simplepat = "/$htaccess";
 if (preg_match('|' . $simplepat . '$|',$uri)) {$uri = ($uri . "/");}
 $url = preg_replace('|^.*?' . $simplepat . '/|','',$uri);
 // Remove slash at end, if present
 $url = preg_replace('|/$|','',$url);
 $url_parts = explode("/",$url);
 $url_parts_num = count($url_parts);
 $key_parts =['dict','key','input_simple','output','accent'];
 $key_parts_num = count($key_parts); //5
 $parms = array(); 
 // set defaults for $parms 
 $parms['dict'] = null; // use cookie
 $parms['key'] = null;
 $parms['input'] = 'simple'; 
 $parms['input_simple'] = 'default'; #'iast';  # can be overridden
 $parms['output'] = null; // use cookie ?
 $parms['accent'] = null; // use cookie ?
 // overwrite with values from url_parts
 // all parameters except 'key' must have one of several known values
 $parmvalues = array();
 $parmvalues['dict'] =
   array('wil','yat','gst','ben','mw72','ap90','lan','cae','md','mw',
     'shs','bhs','ap','pd','mwe','bor','ae','bur','stc','pwg',
     'gra','pw','ccs','sch','bop','skd','vcp','inm','vei','pui',
     'acc','krm','ieg','snp','pe','pgn','mci','armh');
 $parmvalues['input_simple'] = array('slp1','deva','iast','hk','itrans'); // 'roman'?
 $parmvalues['output'] = array('slp1','deva','iast','hk','itrans');
 $parmvalues['accent'] = array('yes','no');
 for($i=0;$i<$key_parts_num;$i++) {
  $key = $key_parts[$i];
  if ($i < $url_parts_num) {
   $val = $url_parts[$i];
   if ($key == 'key') {
    $parms[$key] = $val;
   }else { // check for validity
    $val1 = strtolower($val);
    if (in_array($val1,$parmvalues[$key])) {
     $parms[$key] = $val1;
    }
   }
  }else {
   $val = 'N/A';
  }
  #$vala= $parms[$key];
  #dbgprint(true,"list-0.2s_rw: url_parts[$i] = $val, parms[$key] = $vala\n");
 }
 /* See phpinit
  $dict = $_REQUEST['dict'];
  $key = $_REQUEST['key'];
  $accent= $_REQUEST['accent'];
  $input_simple = $_REQUEST['input_simple'];
  $output = $_REQUEST['output'];
 */
 $keys = array('key','dict','input_simple','output','accent');
 $phpvals = array();
 for($i=0;$i<count($keys);$i++) {
  $key=$keys[$i];
  $val=$parms[$key];
  if ($key == 'key') { //12-03-2020
   $val1 = urldecode($val);  // from uri-encoding to utf-8
   $phpvals[$key] = $val1;
  }else if ($key == "input_simple") {
   // optional. This will specify the assumed spelling used in the citation.
   $phpvals[$key] = $val;
  }else {
   $phpvals[$key] = $val; //$_REQUEST[$key];
  }
  //$_REQUEST[$key] = $val; 
  $vala = $phpvals[$key];
  $valb = $parms[$key];
  #dbgprint(true,"list-0.2s_rw: $i , phpvals[$key] = $vala, parms[$key]=$valb\n");
 }
 return $phpvals;
}
?>
  