<?php
// disp.php
// The main function basicDisplay constructs an HTML table from
// an array of data elements.
// Each of the  data elements is a string which is valid XML.
// The XML is processed using the XML Parser routines (see PHP documentation)
// This XML string is further assumed to be in UTF-8 encoding.
// Nov 10, 2014. Modified to show Arabic text

class DispItem { // info to construct a row of the display table
 public $dict,$key,$lnum,$info,$html;
 public $pginfo,$hcode,$key2,$hom;
 public $hrefdata_prev,$hrefdata;
 public function __construct($hrefdata_prev,$dict,$key,$lnum,$info,$html) {
  $this->hrefdata_prev = $hrefdata_prev;
  $this->dict = $dict;
  $this->info=$info;
  $this->html=$html;
  $this->key=$key;
  $this->lnum=$lnum;
  //Some derived fields
  if($this->dict == 'MW') {
   list($this->pginfo,$this->hcode,$this->key2,$this->hom) = preg_split('/:/',$info);
  }else {
   $this->pginfo = $info;
  }
  // compute $hrefdata
  $this->hrefdata= getHrefPage($this->pginfo,$this->dict);
  // compute $keyshow;
  $this->keyshow = $this->keyshow();
 } // __construct
 public function keyshow() {
  $dict=$this->dict;
  $english = in_array($dict,array("AE","MWE","BOR")); // boolean flag
  if ($english) {
    return $this->key;
  }
  if ($dict == 'MW') {
   list($hcode,$key2,$hom) = $xinfo;
   $hc = $hcode; 
   $keyshow = "<span class='sdata'><SA>$this->key</SA></span>";
   return $keyshow;
  }
  // Sanskrit headwords, not MW
  $keyshow = "<span class='sdata'><SA>$this->key</SA></span>";
  return $keyshow;
 } //keyshow
 public function basicDisplayRecordDefault() {
  $hrefdata = $this->hrefdata;
  $keyshow = $this->keyshow;
  $lnum = $this->lnum;
  if ($hrefdata != $this->hrefdata_prev) {
   $row1 = "<td class='display' valign='top'>&nbsp;$keyshow<span class='lnum'> [L=$lnum]</span><span class='hrefdata'> [p= $hrefdata]</span></td>";
  }else {
   $row1 = "<td class='display' valign='top'>&nbsp;$keyshow<span class='lnum'> [L=$lnum]</span></td>";
  }
  $row = $this->html;

  return ( "<tr><td class='display' valign=\"top\">$row1</td>\n" .
   "<td class='display' valign=\"top\">$row</td></tr>\n");
 } // basicDisplayRecordDefault

 public function basicDisplayRecord1() {
  $hrefdata = $this->hrefdata;
  $keyshow = $this->keyshow;
  $lnum = $this->lnum;
  if ($this->hrefdata != $this->hrefdata_prev) {
   $row1 = "$keyshow<span class='lnum'> [L=$lnum]</span><span class='hrefdata'> [p= $hrefdata]</span>";
  }else {
   $row1 = "$keyshow<span class='lnum'> [L=$lnum]</span>";
  }
  $row = $this->html;
 
  return ( "<tr><td class='display' valign=\"top\">$row1</td></tr>\n" .
   "<tr><td class='display' valign=\"top\">$row</td></tr>\n");
 } // basicDisplayRecord1
 public function basicDisplayRecord2() {
  $hrefdata = $this->hrefdata;
  $keyshow = $this->keyshow;
  $lnum = $this->lnum;
  //$prev = $this->hrefdata_prev;
  //echo "<p>Debug record2 1: $hrefdata, $prev, $keyshow,$lnum</p>\n";
  if ($hrefdata!=$this->hrefdata_prev) {
   $row1 = "$keyshow<span class='lnum'> [L=$lnum]</span><span class='hrefdata'> [p= $hrefdata]</span>";
  }else {
   $row1 = "$keyshow<span class='lnum'> [L=$lnum]</span>";
  }
  //echo "<p>Debug record2 2</p>\n";
  $row = $this->html;
  return ( "<tr><td class='display' valign=\"top\"><span style='font-weight:bold'>$row1</span> : \n" .
   "$row</td></tr>\n");
 } // basicDisplayRecord2
} // class dispItem

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
 var base = "http://www.sanskrit-lexicon.uni-koeln.de/scans/MWScan/2014/web";
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
  $html = $matchrec[2];
  //echo "<p>debug 1, i=$i, ntot=$ntot</p>\n";
  $dispItem = new DispItem($hrefdata_prev,$dict,$keyrec,$lnum,$info,$html);
  //echo "<p>debug 2, i=$i</p>\n";

  if ($options == '1') {
   $table .= $dispItem->basicDisplayRecord1();
  }else if ($options == '2') {
   // echo "<p>Enter basicDisplayRecord2</p>\n";
   $table .= $dispItem->basicDisplayRecord2();
   //echo "<p>Back from basicDisplayRecord2</p>\n";

  }else{
   $table .= $dispItem->basicDisplayRecordDefault();
  }
  $hrefdata_prev = $dispItem->hrefdata;
  //echo "<p>debug 3 i=$i</p>\n";

  $i++;
 }
 $table .= "</table>\n";
 $output .= $table;
 $output .= "</div> \n";
 return $output;
}


?>
