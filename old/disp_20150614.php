<?php
// disp.php
// The main function basicDisplay constructs an HTML table from
// an array of data elements.
// Each of the  data elements is a string which is valid XML.
// The XML is processed using the XML Parser routines (see PHP documentation)
// This XML string is further assumed to be in UTF-8 encoding.
// Nov 10, 2014. Modified to show Arabic text

function getHrefPage($data,$dict) {
 $ans="";
 $lnums = preg_split('/[,]/',$data);  
 $serve = "servepdf.php";
 foreach($lnums as $lnum) {
  if ($ans == "") {
   $args = "dict=$dict&page=$lnum"; #"page=$page";
   $ans = "<a href='$serve?$args' target='_$dict'>$lnum</a>";
  }else {
   $ans .= ",$lnum";
  }
 }
 return $ans;
}

function basicDisplay($key,$matches,$dictin,$options) {

 // June 4, 2015 -- assume $matches is filled with records of form:
 //   $matches[$i] == array(key,lnum,rec) -
 //   rec = <info>pg</info><body>html</body>
 // June 14, 2015 for MW, info = pg:Hcode:key2a:hom
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
EOT;
 $english = in_array($dict,array("AE","MWE","BOR")); // boolean flag
 if (($options == '1')||($options == '2')) {
  $table = "<div id='basic'>\n";
 }else {
  if ($english) {
   $table = "<div id='basic'>\n<h1>&nbsp;$key</h1>\n";
  } else {
   $table = "<div id='basic'>\n<h1>&nbsp;<SA>$key</SA></h1>\n";
  }
 }
 $table .= "<table class='display'>\n";
 $ntot = count($matches);
 $hrefdata_prev="";
 $i = 0;
 while($i<$ntot) {
  list($keyrec,$lnum,$rec) = $matches[$i];
 
  if (!preg_match('|<info>(.*?)</info><body>(.*?)</body>|',$rec,$matchrec)) {
   return "<p>Internal error in basicDisplay for $dictin, $key</p>";
  }
  $info = $matchrec[1];
  if ($dictin == 'MW') {
   list($pginfo,$hcode,$key2,$hom) = preg_split('/:/',$info);
  } else {
   $pginfo = $info;
  }
  $html = $matchrec[2];

  $hrefdata = getHrefPage($pginfo,$dict);
  if ($english) {
   $keyshow = $key;
  }else {
   $keyshow = "<SA>$key</SA>";
  }
  if ($options == '1') {
   $table .= basicDisplayRecord1($keyshow,$hrefdata,$hrefdata_prev,$lnum,$html);
  }else if ($options == '2') {
   $table .= basicDisplayRecord2($keyshow,$hrefdata,$hrefdata_prev,$lnum,$html);

  }else{
   $table .= basicDisplayRecordDefault($keyshow,$hrefdata,$hrefdata_prev,$lnum,$html);
  }
  $hrefdata_prev = $hrefdata;

  $i++;
 }
 $table .= "</table>\n";
 $output .= $table;
 $output .= "</div> \n";
 return $output;
}
function basicDisplayRecordDefault($keyshow,$hrefdata,$hrefdata_prev,$lnum,$html) {
  if ($hrefdata!=$hrefdata_prev) {
   $row1 = "<td class='display' valign='top'>&nbsp;<span class='sdata'>$keyshow</span><span class='lnum'> [L=$lnum]</span><span class='hrefdata'> [p= $hrefdata]</span></td>";
  }else {
   $row1 = "<td class='display' valign='top'>&nbsp;<span class='sdata'>$keyshow</span><span class='lnum'> [L=$lnum]</span></td>";
  }
  $row = $html;

  return ( "<tr><td class='display' valign=\"top\">$row1</td>\n" .
   "<td class='display' valign=\"top\">$row</td></tr>\n");
}

function basicDisplayRecord1($keyshow,$hrefdata,$hrefdata_prev,$lnum,$html) {
  if ($hrefdata!=$hrefdata_prev) {
   $row1 = "<span class='sdata'>$keyshow</span><span class='lnum'> [L=$lnum]</span><span class='hrefdata'> [p= $hrefdata]</span>";
  }else {
   $row1 = "<span class='sdata'>$keyshow</span><span class='lnum'> [L=$lnum]</span>";
  }

  $row = $html;
  
  return ( "<tr><td class='display' valign=\"top\">$row1</td></tr>\n" .
   "<tr><td class='display' valign=\"top\">$row</td></tr>\n");
}
function basicDisplayRecord2($keyshow,$hrefdata,$hrefdata_prev,$lnum,$html) {
  if ($hrefdata!=$hrefdata_prev) {
   $row1 = "<span class='sdata'>$keyshow</span><span class='lnum'> [L=$lnum]</span><span class='hrefdata'> [p= $hrefdata]</span>";
  }else {
   $row1 = "<span class='sdata'>$keyshow</span><span class='lnum'> [L=$lnum]</span>";
  }

  $row = $html;
  
  return ( "<tr><td class='display' valign=\"top\"><span style='font-weight:bold'>$row1</span> : \n" .
   "$row</td></tr>\n");
}
?>
