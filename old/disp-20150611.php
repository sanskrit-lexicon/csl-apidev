<?php
// disp.php
// The main function basicDisplay constructs an HTML table from
// an array of data elements.
// Each of the  data elements is a string which is valid XML.
// The XML is processed using the XML Parser routines (see PHP documentation)
// This XML string is further assumed to be in UTF-8 encoding.
// Nov 10, 2014. Modified to show Arabic text
/* June 11, 2015. removed 
$dirutil = "utilities";
$transcoder = $dirutil ."/". "transcoder.php";
require_once($transcoder); // initializes transcoder
*/
function getHrefPage($data,$dict) {
 $ans="";
 $lnums = preg_split('/[,]/',$data);  
 $serve = "servepdf.php";
 foreach($lnums as $lnum) {
  #list($page,$col) =  preg_split('/[-]/',$lnum);
  #$lnumref=$lnum;
  #$ipage = intval($page);

  if ($ans == "") {
   $args = "dict=$dict&page=$lnum"; #"page=$page";
   $ans = "<a href='$serve?$args' target='_$dict'>$lnum</a>";
  }else {
   $ans .= ",$lnum";
  }
 }
 return $ans;
}

function basicDisplay($key,$matches,$filterin,$dictin) {
 // June 4, 2015 -- assume $matches is filled with records of form:
 //   $matches[$i] == array(key,lnum,rec) -
 //   rec = <info>pg</info><body>html</body>
 $dict = $dictin; // upper case
 #output = returned string of html for basic display
 $output = <<<EOT
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<link rel='stylesheet' type='text/css' href='css/basic.css' />
<script type="text/javascript">
function winls(url,anchor) { 
// Called by a link made by disp.php for MW only. Not used elsewhere
 var base = "//www.sanskrit-lexicon.uni-koeln.de/scans/MWScan/2014/web";
 var url1 = base + '/sqlite/'+url+'#'+anchor;
 
 win_ls = window.open(url1,
    "winls", "width=520,height=210,scrollbars=yes");
 win_ls.focus();
}
</script>
</head>
<body>
<div id='basic'>
EOT;
 $english = in_array($dict,array("AE","MWE","BOR")); // boolean flag
 if ($english) {
  $table = "<h1>&nbsp;$key</h1>\n";
 }else {
  $table = "<h1>&nbsp;<SA>$key</SA></h1>\n";
 }

 $table .= "<table class='display'>\n";
 $ntot = count($matches);
 $hrefdata_prev="";
 $i = 0;
 while($i<$ntot) {
  list($keyrec,$lnum,$rec) = $matches[$i];
  #$linein=$matches[$i];
  if (!preg_match('|<info>(.*?)</info><body>(.*?)</body>|',$rec,$matchrec)) {
   return "<p>Internal error in basicDisplay for $dictin, $key</p>";
  }
  $pginfo = $matchrec[1];
  $html = $matchrec[2];

  $hrefdata = getHrefPage($pginfo,$dict);
  if ($english) {
   $keyshow = $key;
  }else {
   $keyshow = "<SA>$key</SA>";
  }
  if ($hrefdata!=$hrefdata_prev) {
   $row1 = "<tr><td class='display' valign='top'>&nbsp;<span class='sdata'>$keyshow</span><span class='lnum'> [L=$lnum]</span><span class='hrefdata'> [p= $hrefdata]</span></td>";
  }else {
   $row1 = "<tr><td class='display' valign='top'>&nbsp;<span class='sdata'><SA>$key</SA></span><span class='lnum'> [L=$lnum]</span></td>";
  }
  $hrefdata_prev = $hrefdata;
  $row = $html;
  $table .= "<tr><td class='display' valign=\"top\">$row1</td>\n";
  $table .= "<td class='display' valign=\"top\">$row</td></tr>\n";

  $i++;
 }
 $table .= "</table>\n";
 $output .= $table;
 $output .= "</div> <!-- basic -->\n";
 return $output;
}

?>
