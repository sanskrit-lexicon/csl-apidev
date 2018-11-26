<?php
// dispmw.php  - based on disp.php, but for MW.
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
 public $err; // Boolean
 public $keyshow;
 public function __construct($dict,$dbrec) {
  $this->dict = $dict;
  $this->err = False;
  list($this->key,$this->lnum,$rec) = $dbrec;
  if (!preg_match('|<info>(.*?)</info><body>(.*?)</body>|',$rec,$matchrec)) {
   $this->err = True; // rare, if ever
   return;
  }
  $this->info = $matchrec[1];
  $this->html = $matchrec[2];
  //Some derived fields
  if($this->dict == 'MW') {
   list($this->pginfo,$this->hcode,$this->key2,$this->hom) = preg_split('/:/',$this->info);
  }else {
   $this->pginfo = $this->info;
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
  if ($dict != 'MW') {
   // Sanskrit headwords, not MW
   $keyshow = "<span class='sdata'><SA>$this->key</SA></span>";
   return $keyshow;
  }
  // Special handling for MW
  return $this->keyshow_mw();
 } //keyshow

 public function keyshow_MW() {
  $hcode = $this->hcode;
  $key2 = $this->key2;
  $hom = $this->hom;
  /* This is not the right place to make this test
  if ((strlen($hcode) != 2)and(!$hom)) {
   return "";
  }
  */
  $hshow = "($hcode)";  //H1, H2a, etc
  $homshow = "";
  if ($hom && ($hom!='')) {
   $homshow = "<span class='hom'>$hom</span>";
  }
  /* key2 can have
   (a) '-'  not changed
   (b) '~'  raised circle (incomplete)
   (c) </?root/?> (as in ati-<root>kf</root>)
   (d) </?hom>   (as in ati-dA<hom>1</hom> )
   (e) <shortlong/>
   The strategy is to split key2 on all these things, appropriately 
   constructing html for keyshow
  */
  $outarr = array();
  //echo "<p>debug: key2=$key2</p>\n";
  $flags=PREG_SPLIT_DELIM_CAPTURE + PREG_SPLIT_NO_EMPTY;
  $parts = preg_split(':(@)|(~)|(<hom>.*?</hom>)|(<.*?>):',$key2,$flags);
  foreach ($parts as $part) {
   if (!$part) {continue;}
   if ($part == '@') { // <srs/>
    $outarr[] = "<span class='red'>*</span>";
   }else if ($part == '~') { //<sr/>
    $outarr[] = "<span class='red'>&deg;</span>";
   }else if (preg_match('|<hom>(.*?)</hom>|',$part,$matches)) {
    $hom = $matches[1];
    $outarr[] = "<span class='red'>&nbsp;$hom</span>";
   }else if (($part == '<root>') or ($part == '<root/>')) {
    $outarr[] = " &#x221a;"; // root symbol
   }else if (($part == '</root>') or ($part == '<shortlong/>')) {
    $outarr[] = "";
   }else { // Should just be text, to be considered devanagari
    $outarr[] = "<span class='sdata'><SA>$part</SA></span>";
    //echo "<p>debug: part=$part</p>\n";
   }
  }
  $key2show = join('',$outarr);
  // Finally return the join of these strings
  $ans = "$hshow $key2show $hom";
  return $ans; 
 }
 public function basicRow1DefaultParts($prev) {
  if($prev) {
   $hrefdata_prev = $prev->hrefdata;
   $keyshow_prev = $prev->keyshow;
  }else {
   $hrefdata_prev="";
   $keyshow_prev = "";
  }
  $hrefdata = $this->hrefdata;
  $key = $this->key;
  $keyshow = $this->keyshow;
  $lnum = $this->lnum;
  if ($keyshow == $keyshow_prev) {
   $keyshow = ""; // Don't reshow same key on subsequent records
  }
  $lnumshow = "<span class='lnum'> [L=$lnum]</span>";
  $pageshow = "<span class='hrefdata'> [p= $hrefdata]</span>";
  if ($hrefdata == $hrefdata_prev) {
   $pageshow="";
  }
   return array($keyshow,$lnumshow,$pageshow);
 }
 public function basicRow1Default($prev) {
  list($keyshow,$lnumshow,$pageshow) = $this->basicRow1DefaultParts($prev);
  $row1 = "$keyshow $lnumshow $pageshow";  
  return $row1;
 }
 public function basicDisplayRecordDefault($prev) {
  $row1 = $this->basicRow1Default($prev);
  $row = $this->html;
  return ( "<tr><td class='display' valign=\"top\">$row1</td>\n" .
   "<td class='display' valign=\"top\">$row</td></tr>\n");
 }

 public function basicDisplayRecord1($prev) {
  $row1 = $this->basicRow1Default($prev);
  $row = $this->html;
  return ( "<tr><td class='display' valign=\"top\">$row1</td></tr>\n" .
   "<tr><td class='display' valign=\"top\">$row</td></tr>\n");
 } 

 public function basicDisplayRecord2($prev) {
  list($keyshow,$lnumshow,$pageshow) = $this->basicRow1DefaultParts($prev);
  $row = $this->html;
  if ($this->hom) { // for MW
   $pre1 = ""; // incomplete  need a link with onclick
   $hrefdata = $this->hrefdata;
   $pageshow = "<span class='hrefdata'> [p= $hrefdata]</span>";
   $pre2="<span style='font-weight:bold'>$keyshow $pageshow</span> :";
   $pre = $pre1 . $pre2;
  }else if (($keyshow == "") and ($pageshow == "")) {
   $pre = "";
  }else {
   $pre="<span style='font-weight:bold'>$keyshow $pageshow</span> :";
  }
  if (($this->dict == 'MW') and ($this->hom)) {
   // make a link to change list view to be centered at this lnum
   $symbol = "&#8592;";  // unicode left arrow
   $lnum = $this->lnum;
   $class='listlink';
   if (!$prev) {
    $class='listlink listlinkCurrent';
   }
   /* for use of 'this', refer
//stackoverflow.com/questions/925734/whats-this-in-javascript-onclick
   */
   $a = "<a class='$class' onclick='listhier_lnum(\"$lnum\",this);'>$symbol</a>&nbsp;\n";
   $pre = $a . $pre;
  }
   return ( "<tr><td class='display' valign=\"top\"> $pre \n" .
   "$row $lnumshow</td></tr>\n");
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
 $dispItems=array();
 for($i=0;$i<$ntot;$i++) {
  $dbrec = $matches[$i];
  //echo "<p>DEBUGa: $i,$ntot " . $dbrec[0] . "</p>\n";
  $dispItem = new DispItem($dict,$dbrec);
  //echo "<p>DEBUGb: $i,$ntot " . $dbrec[0] . "</p>\n";
  if ($dispItem->err) {
   return "<p>Internal error in basicDisplay for $dict, $key</p>";
  }
  if ($i!=0) {
   // For the first item, always show the key
   // But for other items, conditionally show the key
   $hcode=$dispItem->hcode;
   $hom = $dispItem->hom;
   if ((strlen($hcode) != 2)and(!$hom)) {
    $dispItem->keyshow = ''; 
   }
  }
  $dispItems[] = $dispItem;
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
