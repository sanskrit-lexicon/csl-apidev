<?php
// disp.php
// The main function basicDisplay constructs an HTML table from
// an array of data elements.
// Each of the  data elements is a string which is valid XML.
// The XML is processed using the XML Parser routines (see PHP documentation)
// This XML string is further assumed to be in UTF-8 encoding.
// Nov 10, 2014. Modified to show Arabic text
$dirutil = "utilities";
$transcoder = $dirutil ."/". "transcoder.php";
require_once($transcoder); // initializes transcoder

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
   $ans = "<a href='$serve?$args' target='_Blank'>$lnum</a>";
  }else {
   $ans .= ",$lnum";
  }
 }
 return $ans;
}

function basicDisplay($key,$matches,$filterin,$dictin) {
 $dict = $dictin;
 global $row,$row1,$inkey2,$parentEl,$dict;
 
 $table = "";
 $table = "<h1>&nbsp;<SA>$key</SA></h1>\n";

 $table .= "<table class='display'>\n";
 $ntot = count($matches);
 $i = 0;
 while($i<$ntot) {
  $linein=$matches[$i];
  $line=$linein;
  $line=trim($line);
  $l0=strlen($line);
  $line=line_adjust($line);
  $row = "";
  $row1 = "";
  
   $inkey2 = false;
  
  $p = xml_parser_create('UTF-8');
  xml_set_element_handler($p,'sthndl','endhndl');
  xml_set_character_data_handler($p,'chrhndl');
  xml_parser_set_option($p,XML_OPTION_CASE_FOLDING,false);
  if (!xml_parse($p,$line)) {
   $row1 = "basicDidsplay Error parsing line:";
   $fpout = fopen("error.xml","w");
   fwrite($fpout,$line);
   $row = $line;
  }
  xml_parser_free($p);

  $table .= "<tr><td class='display' valign=\"top\">$row1</td>\n";
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
 $line = preg_replace_callback('|<s>(.*?)</s>|',"s_callback",$line);
 $line = preg_replace("|\[Page.*?\]|",  "<pb>$0</pb>",$line);
 $mdash = '&#8212;'; 
 $line = preg_replace('/--/',"$mdash ",$line);
 $line = preg_replace('/<pc>Page(.*)<\/pc>/',"<pc>\\1</pc>",$line);
 if (strlen($line) == 0) {return "line_adjust err @ 2";}

 return $line;
}

function sthndl($xp,$el,$attribs) {
 global $row,$row1,$inkey2,$parentEl,$dict;
 $mdash = '&#8212;';

  if (preg_match('/^H.+$/',$el)) {
   // don't display 'H1'
   // $row1 .= "($el)";
  } else if ($el == "s")  {
  } else if ($el == "key2"){
   $inkey2 = true;
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
  global $row,$row1,$inkey2,$parentEl,$dict;
  $parentEl = "";
  if ($el == "s") {
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
   $inkey2 = false;
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

function chrhndl($xp,$data) {
 global $row,$row1,$inkey2,$parentEl,$dict;
  if ($inkey2) {
   //$data = strtolower($data);
   //$data = transcoder_processString($data,"as","roman");
   $row1 .= "&nbsp;<span class='sdata'><SA>$data</SA></span>";
   //$row1 .= "&nbsp;<span class='sdata'>$data</span>";
  } else if ($parentEl == "key1"){ // nothing printed
  } else if ($parentEl == "pc") {
   $hrefdata = getHrefPage($data,$dict);
   $row1 .= "<span class='hrefdata'> [p= $hrefdata]</span>";
  } else if ($parentEl == "pcol") {
   $hrefdata = getHrefPage($data,$dict);
   $row .= "<span class='hrefdata'> [p= $hrefdata]</span>";
  } else if ($parentEl == "L") {
   $row1 .= "<span class='lnum'> [L=$data]</span>";
  } else if ($parentEl == 's') {
   $row .= "<span class='sdata'><SA>$data</SA></span>";
  } else if ($parentEl == 'divm') { // text displayed in sthdndl
  } else if ($parentEl == "pb"){
   $row .= "&nbsp;<span class='pb'>$data</span>&nbsp;";
  } else if ($parentEl == "hom") {
   $row .= "<span class='hom'>$data</span>&nbsp;";
  } else if ($parentEl == "Arabic") {
   $row .= $data;

  } else { // Arbitrary other text
   $row .= transcoder_processString($data,"as","roman");
  }
}

?>
