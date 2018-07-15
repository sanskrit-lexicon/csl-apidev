<?php
error_reporting( error_reporting() & ~E_NOTICE );
?>
<?php 
/*basicadjust.php
BasicAdjust class  Takes a parameter object
   and a list of xml records from the dictionary database,
   and adjust each of these records so it is ready for the BasicDisplay.
   This was formerly done by the BasicDisplay class in line_adjust function.
   The hope is to have dictionary specific code in this BasicAdjust class,
   and to have the BasicDisplay class to be identical for all dictionaries.
*/
require_once('dalraw.php');
require_once('dbgprint.php');
class BasicAdjust {
 public $getParms;
 public $adjxmlrecs;
 public $dal_ab, $dal_auth; // 
 public $accent;
 public function __construct($getParms,$xmlrecs) {
  $this->accent = $getParms->accent;
  #dbgprint(true,"basicadjust: accent={$this->accent}\n");
  $dict = $getParms->dict;
  $key = $getParms->key;
  $this->dal_ab = new Dalraw($dict,"ab");
  if ($dict == 'pwg') {
   $this->dal_auth = new Dalraw($dict,"bib");  # pwgbib
   dbgprint(false,"basicadjust: bib file open? " . $this->dal_auth->status ."\n");
  }else {
   $this->dal_auth = new Dalraw($dict,"auth");
  }
  $this->getParms = $getParms;
  $adjxmlrecs = array();
  #$i = 0;
  foreach($xmlrecs as $line) {
   $line1 = $this->line_adjust($line);
   $this->adjxmlrecs[] = $line1;
   #$i = $i + 1;
   #dbgprint(true,"adjline[$i]=$line1\n");
  }
  
 }
 public function line_adjust($line) {
 $dbg = false;
 $line = preg_replace('/¦/',' ',$line);
 $line = preg_replace_callback('|<s>(.*?)</s>|','BasicAdjust::s_callback',$line);
 $line = preg_replace_callback('|<key2>(.*?)</key2>|','BasicAdjust::key2_callback',$line);
 $line = preg_replace("|\[Page.*?\]|",  "<pb>$0</pb>",$line);

 $line = preg_replace('/<pc>Page(.*)<\/pc>/',"<pc>\\1</pc>",$line);

 if (preg_match('/<pc>(.*)<\/pc>/',$line,$matches)){
  if($this->pagecol == $matches[1]){
   $line = preg_replace('/<pc>(.*)<\/pc>/','',$line);
  }else {$this->pagecol = $matches[1];}
 }
 /* removed 12-14-2017
 $line = preg_replace_callback('/<ls(.*?)>(.*?)<\/ls>/',
      "line_adjust1_callback",$line);
 */
 /*  Replace the 'title' part of a known ls with its capitalized form
     This is probably particular to pwg and/or pw 
 */
 if (in_array($this->getParms ->dict,array('pw','pwg'))) {
  $line = preg_replace_callback('|<ls n="(.*?)">(.*?)</ls>|',
      "BasicAdjust::ls_callback",$line);
 }
 /* 12-14-2017
  'local' abbreviation handled here. Generate an n attribute if one
   is not present
 */
 $line = preg_replace_callback('|<ab(.*?)>(.*?)</ab>|',"BasicAdjust::abbrv_callback",$line);
 
 // Experiment 05-21-2018 for dict == gra
 $dbg=false;
 dbgprint($dbg,"BasicAdjust dict={$this->getParms->dict}\n");
 if ($this->getParms->dict == 'gra') {
  dbgprint($dbg,"BasicAdjust before rgveda: $line\n");
  $line = preg_replace_callback('| ([0-9]+)[ ,]+([0-9]+)|',"BasicAdjust::rgveda_verse_callback",$line);
  dbgprint($dbg,"BasicAdjust after rgveda: $line\n");
 }

 //$line = preg_replace('|- <br/>|','',$line);
 //$line = preg_replace('|<br/>|',' ',$line);
 // 2018-07-07  Handle lex tag.  
 $line = preg_replace_callback('|<lex(.*?)>(.*?)</lex>|',"BasicAdjust::add_lex_markup",$line);
 return $line;
}
 
 public function ls_callback($matches) {
 // for pw, pwg
 // <ls n="$n">$data</ls>
 $ans = $matches[0];
 $n = $matches[1];
 $data = $matches[2];
 $dbg=false;
 dbgprint($dbg,"ls_callback: n=$n, data=$data\n");
 if (!$this->dal_auth->status) {
  return $ans;
 }
 $table = $this->dal_auth->tabname;
 $result = $this->dal_auth->getgeneral($n,$table);
 if (count($result) != 1) {
  return $ans; // failure
 }
 if ($this->getParms->dict == 'pwg') {
  $rec = $result[0];
  list($n0,$code,$codecap,$text) = $rec;
  dbgprint($dbg," ls_callback code=$code,  codecap=$codecap, text=\n$text\n");
  #$datanew = preg_replace("/^$code/",$codecap,$data);
  #$ans = "<ls n='$n'>$datanew</ls>";
  # 12-26-2017. pwg. Add lshead, so as to be able to style
  $datanew = preg_replace("/^$code/","<lshead>$codecap</lshead>",$data);
  # be sure there is no xml in the text
  $text = preg_replace('/<.*?>/',' ',$text);
  dbgprint($dbg," ls_callback. text after removing tags: \n$text\n");
  # convert special characters to html entities
  # for instance, this handles cases when $tran has single (or double) quotes
  $tooltip = htmlspecialchars($text,ENT_QUOTES);
  $ans = "<ls n='$tooltip'>$datanew</ls>";
  dbgprint($dbg,"ls_callback: ans=$ans\n");
 }
 return $ans;
}
 public function abbrv_callback($matches) {
 /* <ab n="{tran>}">{data}</ab>
  <ab{attrib}>{data)</ab>
 */
 $x = $matches[0]; // full string
 $a = $matches[1];
 $data = $matches[2];
 $dbg=false;
 dbgprint($dbg,"abbrv_callback: a=$a, data=$data\n");
 if(preg_match('/n="(.*?)"/',$a,$matches1)) {
  dbgprint($dbg," abbrv_callback case 1\n");
  $ans = $x;
 }else {
  $tran = $this->getABdata($data);  
  # convert special characters to html entities
  # for instance, this handles cases when $tran has single (or double) quotes
  $tran = htmlspecialchars($tran,ENT_QUOTES);
  $ans = "<ab n='$tran'>$data</ab>";
  dbgprint($dbg," abbrv_callback case 2\n");
 }
 dbgprint($dbg," abbrv_callback returns $ans\n");
 return $ans;
}

 public function getABdata($key) {
 // abbreviation tool tips from Xab.sqlite
 $ans="";
 #$table = "{$this->getParms->dict}ab";
 $table = $this->dal_ab->tabname;
 $result = $this->dal_ab->getgeneral($key,$table);
 $dbg=false;
 dbgprint($dbg,"getABdata: length of result=" . count($result) . "\n");
 if (count($result) == 1) {
  list($key1,$data) = $result[0];
  if (preg_match('/<disp>(.*?)<\/disp>/',$data,$matches)) {
   $ans = $matches[1];
   /*  This taken from mw code; but is probably obsolete.
     It permitted <s>X</s> coding within the abbreviation expansion
     and conversion to the user's choice of 'filter'
   global $dispfilter;
   $temp = strtolower($dispfilter);
   $filterflag = (preg_match('/deva/',$temp) || preg_match('/roman/',$temp));
   if ($filterflag) {
	$ans = preg_replace('/<s>/','<SA>',$ans);
	$ans = preg_replace('/<\/s>/','</SA>',$ans);
   }
   */
  }
 }
 return $ans;
}
 public function add_lex_markup($matches) {
 /* <lex{attrib}|>{data}</lex> ignore attrib
   Turn it into an abbreviation.
   This function current just for cae dictionary.
   Something more complex required for MW.
 */
 $x = $matches[0]; // full string
 $a = $matches[1]; # attributes
 $data = $matches[2]; # {data}
 $dbg=false;
 dbgprint($dbg,"add_lex_markup: a=$a, data=$data\n");
 if(preg_match('/n="(.*?)"/',$a,$matches1)) {
  dbgprint($dbg," add_lex_markup case 1\n");
  $ans = $x;
 }else {
  $tran = $this->getABdata($data);  
  # what if $tran is not present as an abbreviation
  if (!$tran) {
   $tran = "substantive information";
  }
  # convert special characters to html entities
  # for instance, this handles cases when $tran has single (or double) quotes
  $tran = htmlspecialchars($tran,ENT_QUOTES);
  $ans = "<ab n='$tran'>$data</ab>";
  dbgprint($dbg," add_lex_markup case 2\n");
 }
 dbgprint($dbg," abbrv_callback returns $ans\n");
 return $ans;
}

 public function s_callback($matches) {
/* remove accent if needed
*/
 $x = $matches[0];
 if ($this->accent != "yes") {
  // remove accent characters from slp1 text:  /,^,\
  // Assume no closing xml tag within text.
  $y = $matches[1];    // $x = <s>$y</s>
  $y = preg_replace('|[\/\^\\\]|','',$y);
  $x = "<s>$y</s>";
 }
 return $x;
}
public function key2_callback($matches) {
/* remove accent if needed
*/
 $x = $matches[0];
 if ($this->accent != "yes") {
  // remove accent characters from slp1 text:  /,^,\
  // Assume no closing xml tag within text.
  $y = $matches[1];    // $x = <key2>$y</key2>
  $y = preg_replace('|[\/\^\\\]|','',$y);
  $x = "<key2>$y</key2>";
 }
 return $x;
}
 public function rgveda_verse_modern($gra) {
 /*Github user SergeA
  https://github.com/sanskrit-lexicon/Cologne/issues/223#issuecomment-390369526
 */
 $data = [
  [1,191,1,1,191],
  [192,234,2,1,43],
  [235,295,3,1,62],
  [297,354,4,1,58],
  [355,441,5,1,87],
  [442,516,6,1,75],
  [517,620,7,1,104],
  [621,668,8,1,48],
  [1018,1028,8,59,59], //Vālakhilya hymns 1—11
  [669,712,8,60,103],
  [713,826,9,1,114],
  [827,1017,10,1,191]
 ];
 for($i=0;$i<count($data);$i++) {
  list($gra1,$gra2,$mandala,$hymn1,$hymn2) = $data[$i];
  if (($gra1 <= $gra) && ($gra<=$gra2)) {
   $hymn = $hymn1 + ($gra - $gra1);
   $x = "$mandala.$hymn";
   return $x;
  }
 }
 return "?"; // algorithm failed
}
 public function rgveda_verse_callback($matches) {
/* no special coding for Sanskrit in <s>X</s> form.
    So, just remove the <s>,</s> elements
*/
 $x0 = $matches[0];
 $gra1 = $matches[1];
 $gra2 = $matches[2];
 $modern = $this->rgveda_verse_modern((int)$gra1);
 #$x = "<ab n='Standard hymn reference=$modern'>$gra1</ab>,$gra2";
 $x = "<ab n='=$modern (mandala,hymn)'>$gra1</ab>,<graverse>$gra2</graverse>";
 # restore the initial space
 $x = " $x";
 return $x;
}
/*
 public function monierSetNoLit($value) {
 // This function has no effect now (May 4, 2017)
 $this->noLit = $value;
}
 public function basicDisplaySetAccent($accentin){
 if ($accentin == 'yes') {
  $this->accent = True;
 }else {
  $this->accent = False;
 }
}
*/
}
?>
