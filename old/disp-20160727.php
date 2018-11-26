<?php
// disp.php  - based on disp.php, but for MW.
// The main function basicDisplay constructs an HTML table from
// an array of data elements.
// Each of the  data elements is a string which is valid XML.
// The XML is processed using the XML Parser routines (see PHP documentation)
// This XML string is further assumed to be in UTF-8 encoding.
// Nov 10, 2014. Modified to show Arabic text
// Jul 19, 2015 move DispItem class into dispitem.php
require_once('dispitem.php');

function basicDisplay($parms,$matches) {
 // June 4, 2015 -- assume $matches is filled with records of form:
 //   $matches[$i] == array(key,lnum,rec) -
 //   rec = <info>pg</info><body>html</body>
 // June 14, 2015 for MW, info = pg:Hcode:key2a:hom
 // July 11, 2015  Use 'Parm' object for calling sequence
 // Aug 17, 2015 Remove use of _GET['options']. Always use $options='2'
 $key = $parms->key;
 $dict = strtoupper($parms->dict);
 $options = '2'; // $parms->options;

 #output = returned string of html for basic display
 /* July 27, 2016
  In the 'winls' program, the 'url' is actually of a very specific form:
   mwauth_x.html, where x is one of the transcoder values (slp1, itrans, etc)
   corresponding to what the user is using for output.
   In the current context of the basicDisplay function, this value is
   $parms->filter.
  We make the $winlsurl to use in the next $output template
  We also generalize the 'mwauth' to use the current $parms->dict in
  place of 'mw'
 */
 $winlsurl = ($parms->dict) . 'auth_' . ($parms->filter) . '.html';
 $output = <<<EOT
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<link rel='stylesheet' type='text/css' href='//www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/css/basic.css' />
<script type="text/javascript">
function winls(url,anchor) { 
// Called by a link made by disp.php for MW only. Not used elsewhere
// July 27, 2016. url has the form 'mwauth_slp1.html'. We want to
// replace it with, say, 'mwauth_itrans.html' if the user has chosen
// itrans as the output
 var base = "//www.sanskrit-lexicon.uni-koeln.de/scans/MWScan/2014/web";
 //var url1 = base + '/sqlite/'+url+'#'+anchor;
 var url1 = base + '/sqlite/'+'$winlsurl'+'#'+anchor;
 
 win_ls = window.open(url1,
    "winls", "width=520,height=210,scrollbars=yes");
 win_ls.focus();
}
</script>
</head>
<body>
EOT;
 $english = $parms->english; 
/* use of 'CologneBasic' is coordinated with basic.css
  So basic.css won't interfere with the user page.  This
  assumes that the id 'CologneBasic' is unused on user page.
*/
 if (($options == '1')||($options == '2')) {
  $table = "<div id='CologneBasic'>\n";
 }else {
  if ($english) {
   $table = "<div id='CologneBasic'>\n<h1>&nbsp;$key</h1>\n";
  } else {
   $table = "<div id='CologneBasic'>\n<h1>&nbsp;<SA>$key</SA></h1>\n";
  }
 }
 $table .= "<table class='display'>\n";
 $ntot = count($matches);
 $dispItems=array();
 for($i=0;$i<$ntot;$i++) {
  $dbrec = $matches[$i];
  //echo "<p>DEBUGa: $i,$ntot " . $dbrec[0] . "</p>\n";
  $dispItem = new DispItem($dict,$dbrec);
  //echo "<p>DEBUGb: $i,$ntot " . $dbrec[0] . "</p>\n";
  if ($dispItem->err) {
   return "<p>Internal error in basicDisplay for $dict, $key</p>";
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
   $dispItem->cssshade=True;
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
    $dispItem->firstHom=True;
    break;  // 
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


?>
