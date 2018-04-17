<?php
// dispitem.php  Contains class DispItem, which
// parses a records from a dictionary's html database.
// Jul 20, 2015 cssshade
// 11-09-2017. Add tooltips for p= and L=
require_once('dbgprint.php');
class DispItem { // info to construct a row of the display table
 public $dict,$dictup,$key,$lnum,$info,$html;
 public $pginfo,$hcode,$key2,$hom;
 public $hrefdata_prev,$hrefdata;
 public $err; // Boolean
 public $keyshow;
 public $cssshade; // July 20, 2015. See basicDisplayRecord2 for use.
 public $firstHom; // Aug 15, 2015
 public function __construct($dict,$dbrec) {
  $this -> cssshade=False;
  $this->dict = $dict;
  $this->dictup = strtoupper($dict);
  $this->err = False;
  list($this->key,$this->lnum,$rec) = $dbrec;
  $dbg=False;
  dbgprint($dbg,"rec=\n$rec\n");
  if (!preg_match('|<info>(.*?)</info><body>(.*?)</body>|',$rec,$matchrec)) {
   $this->err = True; // rare, if ever
   return;
  }
  $this->info = $matchrec[1];
  $this->html = $matchrec[2];
  dbgprint($dbg,"this->info starts as {$this->info}\n");
  //Some derived fields
  if($this->dictup == 'MW') {
   list($this->pginfo,$this->hcode,$this->key2,$this->hom) = preg_split('/:/',$this->info);
  }else {
   $this->pginfo = $this->info;
  }
  // compute $hrefdata
  $this->hrefdata= $this->getHrefPage();
  // compute $keyshow;
  $this->keyshow = $this->keyshow();
 } // __construct

 public function keyshow() {
  $dictup=$this->dictup;
  $english = in_array($dictup,array("AE","MWE","BOR")); // boolean flag
  if ($english) {
    return $this->key;
  }
  if ($dictup != 'MW') {
   // Sanskrit headwords, not MW
   $keyshow = "<span class='sdata'><SA>$this->key</SA></span>";
   return $keyshow;
  }
  // Special handling for MW
  dbgprint($dbg,"this->info before keyshow_MW(): {$this->info}\n");

  return $this->keyshow_MW();
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
  Here is how key2 looks initially for key=vivf:
     vi-<root>vf<hom>1</hom></root>
  And here is the (sep 2, 2015) erroneous expansion:
  <span class='sdata'><SA>vi-</SA></span>
  <span class='sdata'><SA>vf</SA></span>
  <span class='sdata'><SA>1<hom><root></SA></span>   This is wrong
  */
$dbg=False;
dbgprint($dbg,"dispitem: key={$this->key}, lnum={$this->lnum}, hom={$this->hom}\n");
dbgprint($dbg,"dispitem: info=" . $this->info . "\n");
dbgprint($dbg,"dispitem. key2=$key2\n");
  $outarr = array();
  $flags=PREG_SPLIT_DELIM_CAPTURE + PREG_SPLIT_NO_EMPTY;
  $parts = preg_split(':(<hom>.*?</hom>)|(@)|(~)|(<.*?>):',$key2,-1,$flags);
  foreach ($parts as $part) {
   if (!$part) {continue;}
   $outpart='';
   if ($part == '@') { // <srs/>
    $outpart = "<span class='red'>*</span>";
   }else if ($part == '~') { //<sr/>
    $outpart = "<span class='red'>&deg;</span>";
   }else if (preg_match('|<hom>(.*?)</hom>|',$part,$matches)) {
    $homroot = $matches[1]; // Sep 7, 2015. 
    $outpart = "<span class='red'>&nbsp;$homroot</span>";
   }else if (($part == '<root>') or ($part == '<root/>')) {
    $outpart = " &#x221a;"; // root symbol
   }else if (($part == '</root>') or ($part == '<shortlong/>')) {
    $outpart = "";
   }else { // Should just be text, to be considered devanagari
    $outpart = "<span class='sdata'><SA>$part</SA></span>";
    //echo "<p>debug: part=$part</p>\n";
   }
   $outarr[]=$outpart;
   dbgprint($dbg,"dispitem: part=$part  => $outpart\n");
  }
  $key2show = join('',$outarr);
  // Finally return the join of these strings
  // Sep 3, 2015
  // There are two kinds of 'hom':  The 'vivf' example is one such,
  // where the hom refers to the root 'vf', not to the headword, vivf.
  // In this case, we don't want to show $hom again. The 'real' kind,
  // where hom refers to headword, occurs as a different part of the
  // <info> record.  So, in short we never want to show $hom separately
  // Sep 7, 2015.  '$homroot' used above.  $hom is a separate field,
  // which should be retained
  $ans = "$hshow $key2show <span class='hom'>$hom</span>";
  #$ans = "$hshow $key2show";
  dbgprint($dbg,"dispitem returns: $ans\n");
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
  $lnumshow = "<span class='lnum'> [<span title='Cologne record ID'>L=</span>$lnum]</span>";
  $pageshow = "<span class='hrefdata'> [<span title='Printed book page-column'>p=</span> $hrefdata]</span>";
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
   $pageshow = "<span class='hrefdata'> [<span title='Printed book page-column'>p=</span> $hrefdata]</span>";
   $pre2="<span style='font-weight:bold'>$keyshow $pageshow</span> :";
   $pre = $pre1 . $pre2;
  }else if (($keyshow == "") and ($pageshow == "")) {
   $pre = "";
  }else {
   $pre="<span style='font-weight:bold'>$keyshow $pageshow</span> :";
  }
  if (($this->dictup == 'MW') and ($this->hom)) {
   // make a link to change list view to be centered at this lnum
   $symbol = "&#8592;";  // unicode left arrow
   $lnum = $this->lnum;
   $class='listlink';
   if ($this->firstHom)  { 
    $class='listlink listlinkCurrent';
   }
   /* for use of 'this', refer
http://stackoverflow.com/questions/925734/whats-this-in-javascript-onclick
   */
   $a = "<a class='$class' onclick='listhier_lnum(\"$lnum\",this);'>$symbol</a>&nbsp;\n";
   $pre = $a . $pre;
  }
  $class = "display";
  if ($this->cssshade) {
   $class = "display cssshade";
  }
  $ans = ( "<tr><td class='$class' valign=\"top\"> $pre \n" .
   "$row $lnumshow</td></tr>\n");
  $dbg=False;
  dbgprint($dbg,"basicDisplayRecord2: pre = $pre\n");
   return $ans;
} // basicDisplayRecord2
public function getHrefPage() {
 $ans="";
 $data = $this->pginfo;
 $dict = $this->dict;
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


} // class dispItem


?>
