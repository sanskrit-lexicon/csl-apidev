<?php
// web/webtc/disp.php
// The main function basicDisplay constructs an HTML table from
// an array of data elements.
// Each of the  data elements is a string which is valid XML.
// The XML is processed using the XML Parser routines (see PHP documentation)
// This XML string is further assumed to be in UTF-8 encoding.
// Nov 10, 2014. Modified to show Arabic text
require_once("disp_servepdf.php");
$dir = dirname(__FILE__); //directory containing this php file
// Note: $dir does not end in '/'
$dir = "$dir/";
$dir1 = $dir . '../utilities/';
$dirphp = realpath($dir1);
$pathutil = $dirphp ."/". 'transcoder.php';
require_once($pathutil); // initializes transcoder

$parentEl;
$row;
$row1;
$pagecol = "";
$true = 1;
$false = 0;
$dbg = $false;
$inSanskrit=$false;
$noLit = "off";
$noParen = "off";
$dispfilter="";
$inlex = $false;
$invlex = $false;
$inParen = 0;
$inBracket = 0;
$msc = $false; # when a semicolon should break the record
$inas1 = $false; #in <as1><s>x</s></as1>
$inkey2;
$inls = $false;
$lsrow;
$lsparentEl;
$lsExpandCode;
$lsExpandFlag;
$greek;

$dbg=$false;
//$dbg=$true;

//echo "DEBUG 1\n";
function monierSetNoLit($value) {
 global $noLit;
 $noLit = $value;
}
//echo "DEBUG 2\n";

function basicDisplay($key,$matches,$filterin) {
 global $row,$row1,$pagecol,$true,$false,$inSanskrit,$dbg;
 global $inlex,$invlex,$inParen,$inBracket,$msc,$inas1,$inkey2;
 global $inls,$lsrow,$lsparentEl,$lsExpandCode,$lsExpandFlag,$parentEl;
 global $noLit;
 global $greek;
 global $dispfilter;
// $noLit="off";
 $dispfilter = $filterin;
// echo "<p>filter = $dispfilter</p>";



 $table = "";
//   Rarely, two vowels occur consecutively (e.g. afRin).
//   These don't print properly in devanagari.
// $key = preg_replace('/([aAiIuIfFxXeEoO])([aAiIuIfFxXeEoO])/',"\\1 \\2",$key); 
 $table = "<h1>&nbsp;<SA>$key</SA></h1>\n";

 $table .= "<table class='display'>\n";
 $ntot = count($matches);
 //echo "<p>$ntot lines for $key</p>\n";
 $i = 0;
//echo "ntot=$ntot<br>\n";
 while($i<$ntot) {
  $linein=$matches[$i];
  $line=$linein;
  if ($dbg == $true) {
   echo "<!--line $i = $line -->\n";
  }
  $line=trim($line);
  $l0=strlen($line);
  $line=line_adjust($line);
  $row = "";
  $row1 = "";
  
  $msc=$false;
  $inSanskrit=$false;
  $inlex = $false;
  $invlex = $false;
  $inParen = 0;
  $inBracket = 0;
  $inkey2 = $false;
//   $line = preg_replace('/</','&lt;',$line);
//   $line = preg_replace('/>/','&gt;',$line);
//   echo "<p>The underlying data for this line:<br/>$line</p>\n";
  if ($dbg == $true) {echo "<!--begin parse $i=\n$line\n\n-->";}
  $p = xml_parser_create('UTF-8');
  xml_set_element_handler($p,'sthndl','endhndl');
  xml_set_character_data_handler($p,'chrhndl');
  xml_parser_set_option($p,XML_OPTION_CASE_FOLDING,FALSE);
  if (!xml_parse($p,$line)) {
//   sprintf("XML error: %s ",
//                    xml_error_string(xml_get_error_code($p)));
   $row1 = "basicDidsplay Error parsing line:";
   //$line=$linein;
   $fpout = fopen("error.xml","w");
   fwrite($fpout,$line);
   $row = $line;
  }
  xml_parser_free($p);
  if ($dbg == $true) {echo "<!--after line, row1=$row1\n\nrow=$row1\n\n-->";}
  $table .= "<tr><td class='display' valign=\"top\">$row1</td>\n";

  if ($msc == $true){
   $row .= "</p>";
  }
  $table .= "<td class='display' valign=\"top\">$row</td></tr>\n";
  $i++;
 }
 $table .= "</table>\n";
 return $table;
}

function s_callback($matches) {
/* no special coding for Sanskrit in <s>X</s> form.
    So, just remove the <s>,</s> elements
*/
 $x = $matches[0];
 //$x = preg_replace("|(\[Page.*?\])|","</s> $0 <s>",$x);
 $x = preg_replace("|</?s>|","",$x);
 return $x;
}
function line_adjust($line) {
 global $pagecol;
 $dbg = False;
 if ($dbg) {
  $line1 = preg_replace('|<|',"&lt;",$line);
  $line1 = preg_replace('|>|',"&gt;",$line1);
  echo "<p>@0, line1 = $line1</p>\n";
 } 
 $line = preg_replace_callback('|<s>(.*?)</s>|',"s_callback",$line);
 $line = preg_replace("|\[Page.*?\]|",  "<pb>$0</pb>",$line);
 //if (strlen($line) == 0) {return "line_adjust err @ 1";}
 $mdash = '&#8212;'; // mw72
 //$line = preg_replace('/<i>--/',"<lb/><i>$mdash ",$line);
 $line = preg_replace('/--/',"$mdash ",$line);
 $line = preg_replace('/<pc>Page(.*)<\/pc>/',"<pc>\\1</pc>",$line);
 if (strlen($line) == 0) {return "line_adjust err @ 2";}

 if (preg_match('/<pc>(.*)<\/pc>/',$line,$matches)){
  if($pagecol == $matches[1]){
   $line = preg_replace('/<pc>(.*)<\/pc>/','',$line);
  }else {$pagecol = $matches[1];}
 }
 if (strlen($line) == 0) {return "line_adjust err @ 4";}

 if (strlen($line) == 0) {return "line_adjust err @ 5";}
 /* skip?
 $line = preg_replace('|&#x2021;|','-',$line);  // double-dagger adjust
 */
 return $line;
}
//echo "DEBUG 4\n";

function as_cedilla_s4 ($x) {
 $x = preg_replace("|<xe7/>|","s4",$x);
 $x = preg_replace("|<xc7/>|","S4",$x);
 return $x;
}
function bold_as($matches) {
 $x = $matches[1];
 //$x = as_cedilla_s4 ($x);
 $y = transcoder_processString($x,"as","roman");
 $ans = "$y";
 return $ans;
}
function italic_as($matches) {
 $x = $matches[1];
 //$x = as_cedilla_s4 ($x);
 $y = transcoder_processString($x,"as","roman");
 $ans = "<i>$y</i>";
 return $ans;
}
function sanskrit_hk($matches) {
 $x = $matches[1];
 $y = transcoder_processString($x,"hk","slp1");
 $ans = "<s>$y</s>";
 return $ans;
}
//echo "DEBUG 5\n";
function sthndl($xp,$el,$attribs) {
 global $row,$row1,$pagecol,$true,$false,$inSanskrit,$noParen;
 global $inlex,$invlex,$inParen,$inBracket,$msc,$inas1,$inkey2;
 global $inls,$lsrow,$lsparentEl,$lsExpandCode,$lsExpandFlag,$parentEl;
  $mdash = '&#8212;';

  if (preg_match('/^H.+$/',$el)) {
   // don't display 'H1'
   // $row1 .= "($el)";
  } else if ($el == "s")  {
   $inSanskrit = $true;
  } else if ($el == "key2"){
   $inkey2 = $true;
  } else if ($el == "b"){
   $row .= ""; 
  } else if ($el == "i"){
   $row .= "<i>"; 
  } else if ($el == "br"){
   $row .= "<br/>";   
  } else if ($el == "P"){
   $row .= "<hr/> <span style='font-weight:bold;'>$mdash </span>";   
  } else if ($el == "lb"){
   $row .= "<br/>";   
  } else if ($el == "h"){
  } else if ($el == "body"){
  } else if ($el == "tail"){
  } else if ($el == "L"){
  } else if ($el == "pc"){
  } else if ($el == "pb"){
  } else if ($el == "key1"){
  } else if ($el == "hom"){
  } else if ($el == "Arabic"){
   $row .= ""; 
  } else if ($el == "F"){
   $row .= "<br/>&nbsp;<span class='footnote'>[Footnote: ";
  } else if ($el == "g"){
   $row .= "<span class='g'>(greek) ";
  } else if ($el == "lang"){
   $n = $attribs['n'];
   $row .= "<span class='lang'>($n) ";
  } else if ($el == "ls") {
   $row .= "&nbsp;<span class='ls'>";
  } else if ($el == "gram") {
   $row .= "&nbsp;<span class='gram'>";
  } else if ($el == "divm") {
   $type=$attribs['type'];
   $n=$attribs['n'];
   $row .= "<br/><span class='divm'>$mdash ";
   if ($type=='g') { // greek. Substitute values for $n
    $n = "$n?";
   }
   $row .= "$n) ";
  } else if ($el == "wide") {
   $row .= "<span class='wide'>";  // css3
   
  } else {
    $row .= "<br/>&lt;$el&gt;";
  }

  $parentEl = $el;
}
//echo "DEBUG 6\n";

function endhndl($xp,$el) {
// echo "endhndl, $el, $inSanskrit\n";
 global $row,$row1,$pagecol,$true,$false,$inSanskrit,$noParen;
 global $inlex,$invlex,$inParen,$inBracket,$msc,$inas1,$inkey2;
 global $inls,$lsrow,$lsparentEl,$lsExpandCode,$lsExpandFlag,$parentEl;
  $parentEl = "";
  if ($el == "s") {
   $inSanskrit = $false;
  } else if ($el == "F") {
   $row .= "]</span>&nbsp;<br/>";
  } else if ($el == "b"){
   $row .= ""; 
  } else if ($el == "g"){
   $row .= "</span>"; 
  } else if ($el == "lang"){
   $row .= "</span>"; 
  } else if ($el == "i"){
   $row .= "</i>"; 
  } else if ($el == "key2") {
   $inkey2 = $false;
  } else if ($el == "ls") {
   $row .= "</span>&nbsp;";
  } else if ($el == "gram") {
   $row .= "</span>&nbsp;";
  } else if ($el == "divm") {
    $row .= "</span>";
  } else if ($el == "wide") {
    $row .= "</span>";
 }
}
//echo "DEBUG 7\n";

function chrhndl($xp,$data) {
 global $row,$row1,$pagecol,$true,$false,$inSanskrit,$noParen;
 global $inlex,$invlex,$inParen,$inBracket,$msc,$inas1,$inkey2;
 global $inls,$lsrow,$lsparentEl,$lsExpandCode,$lsExpandFlag,$parentEl;
 global $noLit;
  if ($inkey2 == $true) {
   //$data = strtolower($data);
   //$data = transcoder_processString($data,"as","roman");
   $row1 .= "&nbsp;<span class='sdata'><SA>$data</SA></span>";
   //$row1 .= "&nbsp;<span class='sdata'>$data</span>";
  } else if ($parentEl == "key1"){ // nothing printed
  } else if ($parentEl == "pc") {
   $hrefdata = getHrefPage($data);
   $row1 .= "<span class='hrefdata'> [p= $hrefdata]</span>";
  } else if ($parentEl == "pcol") {
   $hrefdata = getHrefPage($data);
   $row .= "<span class='hrefdata'> [p= $hrefdata]</span>";
  } else if ($parentEl == "L") {
   $row1 .= "<span class='lnum'> [L=$data]</span>";
  } else if ($parentEl == 's') {
   $row .= "<span class='sdata'><SA>$data</SA></span>";
  //} else if ($parentEl == 'g') {
  } else if ($parentEl == 'divm') { // text displayed in sthdndl
  } else if ($parentEl == "pb"){
   $row .= "&nbsp;<span class='pb'>$data</span>&nbsp;";
  } else if ($parentEl == "hom") {
   $row .= "<span class='hom'>$data</span>&nbsp;";
  } else if ($parentEl == "Arabic") {
   //$row .= "<span class='hom'>$data</span>&nbsp;";
   $row .= $data;

  } else { // Arbitrary other text
   $row .= transcoder_processString($data,"as","roman");
  }
}
//echo "DEBUG 8\n";

?>
