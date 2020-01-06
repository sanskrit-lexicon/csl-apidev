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
require_once('dal.php');
require_once('dbgprint.php');
class BasicAdjust {
 public $getParms;
 public $adjxmlrecs;
 public $dal_ab, $dal_auth; // 
 public $accent;
 public $dbg;
 public function __construct($getParms,$xmlrecs) {
  $this->accent = $getParms->accent;
  $dict = $getParms->dict;
  $key = $getParms->key;
  $this->dbg=false;
  $this->dal_ab = new Dal($dict,"ab");
  if (in_array($dict,array('pwg','pw'))) {
   $this->dal_auth = new Dal($dict,"bib");  # pwgbib
   dbgprint(false,"basicadjust: bib file open? " . $this->dal_auth->status ."\n");
  }else if ($dict == 'mw'){
   $this->dal_auth = new Dal($dict,"authtooltips");
  }else {
   $this->dal_auth = null;
  }
 
  $this->getParms = $getParms;
  $adjxmlrecs = array();
  #$i = 0;
  foreach($xmlrecs as $line) {
   $line1 = $this->line_adjust($line);
   $this->adjxmlrecs[] = $line1;
   #$i = $i + 1;
   dbgprint($this->dbg,"basicadjust: line=\n$line\n\nadjline=$line1\n");
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
 if (in_array($this->getParms->dict,array('pw','pwg'))) {
  $line = preg_replace_callback('|<ls n="(.*?)">(.*?)</ls>|',
      "BasicAdjust::ls_callback_pwg",$line);
 }else if (in_array($this->getParms->dict,array('mw'))){
  $line = preg_replace_callback('|<ls>([A-ZĀĪŚṚṢṬ][A-Za-zÂáâêîñôĀāĪīŚśūûḍḥṃṅṇṉṚṛṢṣṬṭ.]*[.])(.*?)</ls>|', "BasicAdjust::ls_callback_mw",$line);  
  // handle the frequent <ls>ib.xxx</ls> by marking ib. as abbreviation
  $line = preg_replace('|<ls>ib[.]|','<ls><ab>ib.</ab>',$line);    
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
  $line = preg_replace_callback('|([ (])([0-9]+)[ ,]+([0-9]+)|',"BasicAdjust::rgveda_verse_callback",$line);
  dbgprint($dbg,"BasicAdjust after rgveda: $line\n");
 }

 //$line = preg_replace('|- <br/>|','',$line);
 //$line = preg_replace('|<br/>|',' ',$line);
 // 2018-07-07  Handle lex tag.  
 $line = preg_replace_callback('|<lex(.*?)>(.*?)</lex>|',"BasicAdjust::add_lex_markup",$line);
 
  if ($this->getParms->dict == "mw") {
   $line = $this->move_L_mw($line);
   # remove <hom>X</hom> within head portion
   $line = preg_replace("|<key2>(.*?)<hom>.*?</hom>(.*?<body>)|","<key2>$1$2",$line); 
  }
  else if ($this->getParms->dict == "ap90") {
   /*  ap90.xml has a line break '<lb/>' according to the printed edition.
     In the display, these are not recognized.
     Further, the display attempts to rejoin hyphenation due to line breaks.
     Finally, the pattern '<b>--X</b>' is treated as a division that generates
     a line break.
   */
   #dbgprint(true,"line before <lb> changes\n$line\n");
   $line = preg_replace('|- *<lb/>|','',$line);
   $line = preg_replace('|-</s> <lb/><s>|','',$line);
   $line = preg_replace('|<lb/>|','',$line);
   # now reintroduce some line breaks, and replace '--' with '&mdash;'
   # tech note on php:  when html entity &mdash; is used, then there is
   # an error in the xml parser in basicdisplay.php.  However, when we use 
   # the numerical code, '&#x2014;', the error disappears.
   # It might be better to do this logic (including the em-dash) in
   # make_xml.py or even in ap90.txt. E.g., change
   # <b>--X</b> to <div n="1"/><b>—X</b>
   #$line = preg_replace('|<b>--|','<lb/><b>&mdash;',$line);
   $line = preg_replace('|<b>--|','<div n="1"/><b>&#x2014; ',$line);
   # also, there are seven instances of "<P/>". Replace with a div
   $line = preg_replace('|<P/>|','<div n="P"/>',$line);
   # $line = preg_replace('|<s>--|','<div n="1"/><s>&#x2014;',$line);
   $line = preg_replace('|<s>--|','<div n="1"/><b>&#x2014;</b> <s>-',$line);
   #dbgprint(true,"line after <lb> changes\n$line\n");
  }
  else if ($this->getParms->dict == "ap") {
   // replace -- with mdash : perhaps should be part of ap.txt
   $line = preg_replace('/--/','&#8212;',$line);
   // 03-12-2017.  Put 'b' (bold) tag around the first word of a div
   $line = preg_replace('|(<div[^>]*>)(\(<i>.</i>\))|','\\1<b>\\2</b>',$line);
   // 11-29-2018.  Also pattern '<s>--X</b>' 
   $line = preg_replace('|(<div[^>]*>)([0-9]+)|','\\1<b>\\2</b>',$line);
   // Remove <root/> tag -- it plays no part in display
   $line = preg_replace('|<root/>|','',$line);
  }
  else if ($this->getParms->dict == "yat") {
   $line = preg_replace('|- <br/>|','',$line);
   $line = preg_replace('|<br/>|',' ',$line);
   $line = preg_replace('/--/','&#8212;',$line);  # emdash
  }
  else if ($this->getParms->dict == "shs") {
   $line = preg_replace('|- <lb/>|','',$line);
   $line = preg_replace('|<lb/>|',' ',$line);
   $line = preg_replace('/--/','&#8212;',$line);  # emdash
  } else if ($this->getParms->dict == "ben") {
   $line = preg_replace('/--/','&#8212;',$line);  # emdash
   $line = preg_replace('|<g></g>|','<lang n="greek"></lang>',$line);
   $line = preg_replace('|<P/>|','<div n="P"/>',$line);
  } else if ($this->getParms->dict == "bor") {
   /* Put bold tag around first word of <div n="1"> or <div n="I"> 
      Sometimes there is no space character in the div. Remedy this by always
      putting a space before a closing div </div>.
   */
   $line = preg_replace('|</div>|',' </div>',$line);
   $line = preg_replace('|<div n="([1I])">([^ ]*)|','<div n="\1"><b>\2</b>',$line);
  } else if ($this->getParms->dict == "mw72") {
   # removed 10-31-2019, since Greek text now provided in mw72.
   #$line = preg_replace('|></lang>|'," empty='yes'></lang>",$line);
  } else if ($this->getParms->dict == "sch") {
   # this should have been done in sch.txt or sch.xml
   $line = preg_replace('|\^(.)|',"<sup>\\1</sup>",$line);
  } else if ($this->getParms->dict == "acc") {
   # this should have been done in acc.txt or acc.xml
   $line = preg_replace('|\^([a-d02th]+)|',"<sup>\\1</sup>",$line);
   $line = preg_replace('/--/','&#8212;',$line);  # emdash
   # also, remove breaks.  This is a display choice, maybe not for acc.txt,xml
   $line = preg_replace('|- <br/>|','',$line);
   $line = preg_replace('|<br/>|',' ',$line);
  }
  if ($this->getParms->dict == "mw")  {
   /* 11-13-2018 make bold abbreviations following <div n="vp">
   */
  $line = preg_replace('|(<div n="vp"/> *)(<ab.*?</ab>)|',"\\1<b>\\2</b>",$line);
  }
 return $line;
}
 
 public function ls_callback_pwg($matches) {
 // for pw, pwg
 // <ls n="$n">$data</ls>
 $ans = $matches[0];
 $n = $matches[1];
 $data = $matches[2];
 $dbg=false;
 dbgprint($dbg,"ls_callback_pwg: n=$n, data=$data\n");
 if (!$this->dal_auth->status) {
  return $ans;
 }
 $table = $this->dal_auth->tabname;
 $result = $this->dal_auth->getgeneral($n,$table);
 if (count($result) != 1) {
  return $ans; // failure
 }
 if (in_array($this->getParms->dict,array('pwg','pw'))) {
  // This if is currently redundant, as ls_callback_pwg only called
  // when this is true. However, it does no harm.
  $rec = $result[0];
  list($n0,$code,$codecap,$text) = $rec;
  dbgprint($dbg," ls_callback_pwg code=$code,  codecap=$codecap, text=\n$text\n");
  #$datanew = preg_replace("/^$code/",$codecap,$data);
  #$ans = "<ls n='$n'>$datanew</ls>";
  # 12-26-2017. pwg. Add lshead, so as to be able to style
  $datanew = preg_replace("/^$code/","<lshead>$codecap</lshead>",$data);
  # be sure there is no xml in the text
  $text = preg_replace('/<.*?>/',' ',$text);
  dbgprint($dbg," ls_callback_pwg. text after removing tags: \n$text\n");
  # convert special characters to html entities
  # for instance, this handles cases when $tran has single (or double) quotes
  $tooltip = $this->htmlspecial($text);
  $ans = "<ls n='$tooltip'>$datanew</ls>";
  dbgprint($dbg,"ls_callback_pwg: ans=$ans\n");
 }
 return $ans;
}
public function ls_callback_mw($matches) {
 /* <ls>AR</ls>  A = $abbrv, R = $rest */
 $ans = $matches[0];
 $abbrv = $matches[1];
 $rest = $matches[2];
 $dbg=false;
 dbgprint($dbg,"ls_callback: abbrv=$abbrv, rest=$rest\n");
 if (!$this->dal_auth->status) {
  return $ans;
 }
 $table = $this->dal_auth->tabname;
 $result = $this->dal_auth->getgeneral($abbrv,$table);
 if (count($result) != 1) { // unknown abbreviation
  $title = "Unknown literary source";
  $type = "Unknown type";
 } else {
  $rec = $result[0];
  list($cid,$abbrv1,$title,$type) = $rec;
 }
 $text = "$title ($type)";
 // The tooltip might be malformed for an html attribute. Try to fix
 $tooltip = $this->htmlspecial($text);
 # reconstruct the ls element with an n attribute
 $ans = "<ls n='$tooltip'><lshead>$abbrv</lshead>$rest</ls>";
 dbgprint($dbg,"  lsnew=$ans\n");
  dbgprint($dbg,"ls_callback_mw: ans=$ans\n");
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
 */
 if ($this->getParms->dict == "mw") {
  //Something more complex required for MW.
  return  $this->add_lex_markup_mw($matches);
 }
 $x = $matches[0]; // full <lex>X</lex> string
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
 dbgprint($dbg," add_lex_markup returns $ans\n");
 return $ans;
}
 public function add_lex_markup_mw($matches) {
 /* <lex{attrib}>{data}</lex> ignore attrib
   For mw, {data} is more complex. For display purposes, we want
   to identify the genders and mark as abbreviations.
   This is originally done in BasicDisplay class with an XML Parser.
   That seems to be the only way to do it here.
   So we make a special LexParser class for this purpose
 */
 $dbg=false;
 
 $x = $matches[0]; // full <lex>X</lex> string
 $lexparser = new BasicAdjustLexParser($x,$this);
 if ($lexparser->status) {
  $ans = $lexparser->result;
 } else {
  dbgprint($dbg,"basicadjust error in BasicAdjustLexParser\n");
  $ans = $x;
 }
 dbgprint($dbg," add_lex_markup_mw returns $ans\n");
 return $ans;
}

 public function s_callback($matches) {
/* remove accent if needed
   remove <srs/>
*/
 $x = $matches[0];
 if ($this->accent != "yes") {
  // remove accent characters from slp1 text:  /,^,\
  $y = $matches[1];    // $x = <s>$y</s>
  $y = $this->remove_slp1_accent($y);
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
  $y = $this->remove_slp1_accent($y);
  $x = "<key2>$y</key2>";
 }
 return $x;
}
public function remove_slp1_accent($y) {
  #$y = preg_replace('|[\/\^\\\]|','',$y);
  # udatta accent is '/'.  But '/' also used in xml tags (empty or closing)
  # preadjust $y to replace these instances of '/' with '_'
  #  assumes no tag name starts with '_', a safe assumption in this xml
  $y = preg_replace('|</|','<_',$y);  
  $y = preg_replace('|/>|','_>',$y);
  $y = preg_replace('|[\/\^\\\]|','',$y);
  # restore the '/' used in xml tags
  $y = preg_replace('|<_|','</',$y);
  $y = preg_replace('|_>|','/>',$y);
  return $y;
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
public function rgveda_link($gra1,$gra2) {
 /* gra1 = mandala.hymn, gra2 = verse
 */ 
 $dbg=false;
 dbgprint($dbg,"rgveda_link: gra1=$gra1, gra2=$gra2\n");
 list($mandala,$hymn) = explode(".",$gra1);
 $imandala = (int)$mandala;
 $ihymn = (int)$hymn;
 $hymnfilepfx = sprintf("rv%02d.%03d",$imandala,$ihymn);
 $hymnfile = "$hymnfilepfx.html";
 $iverse = (int)$gra2;
 $versesfx = sprintf("%02d",$iverse);
 $anchor = "$hymnfilepfx.$versesfx";
 dbgprint($dbg,"rgveda_link: hymnfile=$hymnfile, anchor=$anchor\n");
 return array($hymnfile,$anchor);
}
 public function rgveda_verse_callback($matches) {
/* no special coding for Sanskrit in <s>X</s> form.
    So, just remove the <s>,</s> elements
    Adds 'gralink' and 'graverse' elements to xml. These need
    to be converted to html in basicdisplay.php
*/
 $x0 = $matches[0];
 $gra0 = $matches[1];
 $gra1 = $matches[2];
 $gra2 = $matches[3];
 $modern = $this->rgveda_verse_modern((int)$gra1);
 #$x = "<ab n='Standard hymn reference=$modern'>$gra1</ab>,$gra2";
 #$x = "<ab n='=$modern (mandala,hymn)'>$gra1</ab>,<graverse>$gra2</graverse>";
 # This version provides a link
 list($rvfile,$rvanchor) = $this->rgveda_link($modern,$gra2);
 #$dir = "../sqlite/rvhymns";
 # 2018-08-30  use github location
 $dir = "https://sanskrit-lexicon.github.io/rvlinks/rvhymns";
 $href = "$dir/$rvfile#$rvanchor";
 $modern1 = "$modern.$gra2";
 $tooltip = "=$modern1 (mandala,hymn,verse)";
 $x = "<gralink href='$href' n='$tooltip'>$gra1,<graverse>$gra2</graverse></gralink>";
# restore the initial space or (
 $x = $gra0 . $x;
 return $x;
}
public function move_L_mw($line) {
 /* 04-12-2018. For MW. Logic to place Cologne record ID at END
  of displays for <H1X> records. This acomplished by changing the
  name of the <L> tag to <L1>
 */
 $dbg=false;
 dbgprint($dbg,"basicadjust.move_L_mw enter: line=\n$line\n");
 if (preg_match('|<(H[1-4].)>.*(<L>.*?</L>)|',$line,$matches)) {
  $H = $matches[1];
  $Ltag = $matches[2];
  // remove L element
  $line = preg_replace("|$Ltag|","",$line);
  // construct L1 tag
  $L1tag = preg_replace("|L>|","L1>",$Ltag);
  #dbgprint(true,"Ltag=$Ltag,  L1tag=$L1tag\n");
  // Insert L1tag before end of tail -- so at end of display
  $line = preg_replace("|</tail>|","$L1tag</tail>",$line);
 }
 dbgprint($dbg,"basicadjust.move_L_mw leave: line=\n$line\n");
 return $line;
}
public function htmlspecial($text) {
 // First, use the php function to convert quotes to html entities:
 // This converts single quote to &#039;
 $tooltip = htmlspecialchars($text,ENT_QUOTES);
 // since the result is parsed again with xml_parser, and xml_parser
 // autoconverts (apparently) &#039; back to single quote,
 // and then generates a parse error if this single quote occurs
 //  within an atribute value expresses as <x attr='y'>  (i.e. y has a
 //  single quote).
 // Because of this we change &#039; to &#8217;  -- which xml_parser
 // apparently leaves unchanged, and generates no error.
 $tooltip = preg_replace('/&#039;/','&#8217;',$tooltip);
 return $tooltip;
}
}

class BasicAdjustLexParser{
 public $parentEl, $row, $status, $result, $dbg, $basicadj;
 public $parents; # array, treated as stack of elements
 public function __construct($line,$basicadj) {
 // $line is a <lex>X</lex> string
 // $basicadj is the calling instance of Basicadjust class;
 //    used to call getABdata
 $this->basicadj = $basicadj;
 $dbg=false;
 $this->dbg=false;
 dbgprint($dbg,"BasicAdjustLexParser: line=$line\n");
  $p = xml_parser_create('UTF-8');
  xml_set_element_handler($p,array($this,'sthndl'),array($this,'endhndl'));
  xml_set_character_data_handler($p,array($this,'chrhndl'));
  xml_parser_set_option($p,XML_OPTION_CASE_FOLDING,FALSE);
  $this->row="";
  # 09-27-2018. Due to error in 'double-parsing' of '&amp;'
  #   This parser for adding abbreviations in <lex> markup
  #   Also converts &amp; to &.   Since the result is parsed a 
  #   second time (in basicdisplay.php) the naked '&' causes a parsing error.
  #   This rare even was noticed in hw=caRqa (L=70905) and
  #   in hw=aruRa (L=15417).
  $this->parents=array();
  $line1 = preg_replace("/&amp;/","<amp/>",$line); # 09-27-2018
  if (!xml_parse($p,$line1)) {
   dbgprint(true,"BasicAdjustLexParser: xml parse error\n");
   $this->result = $line;
   $this->status = false;
   return;
  }
  $this->status = true;
  $this->result = $this->row;
  dbgprint($dbg,"BasicAdjustLexParser: result={$this->result}\n");
 }
 public function sthndl($xp,$el,$attribs) {
  if ($el == "lex") {
   // nothing.  don't output the lex tag to html
  }else if ($el == "amp") {
   // nothing
  }else {
   // output the element tag and its attributes
   $this->row .= "<$el";
   foreach($attribs as $name=>$value) {
    $this->row .= " $name='$value'";
   }
   $this->row .= ">";
  }
  $this->parentEl = $el;
  $this->parents[] = $el;  
 }
 public function endhndl($xp,$el) {
  #$this->parentEl = "";
  array_pop($this->parents);
  if ($el == "lex") {
   // nothing.  don't output the ending lex tag to html
  }else if ($el == "amp") {
   // nothing
  }else {
   // close the tag
   $this->row .= "</$el>";
  }
 }
 public function chrhndl($xp,$data) {
  // get parent from top of stack
  $this->parentEl = array_pop($this->parents);
  // restore top of stack
  $this->parents[]=$this->parentEl;
  if ($this->parentEl == "lex") {
   // $data is a text node within lex convert to abbreviation if possible
   $tran = $this->basicadj->getABdata($data);  
   // try some adjustments if abbreviation not found 
   if ($tran == "") {
    $data1 = trim($data); // remove spaces at ends
    $data1 = preg_replace('|[.]|','',$data1);
    $data1 = preg_replace('|\(.*$|','',$data1);
    $data1 = "$data1."; # add period
    $tran = $this->basicadj->getABdata($data1);
   }
   if ($tran == "") {
    $data1 = trim($data); // remove spaces at ends
    $data1 = preg_replace('|[.]|','',$data1);
    $data1 = preg_replace('|^.*\)|','',$data1);
    $data1 = "$data1."; //add period at end 
    $tran = $this->basicadj->getABdata($data1);
   }
   dbgprint($this->dbg,"BasicAdjustLexParser. lex chrhndl. data=$data, tran=$tran\n");
   if ($tran == "")  {
    // No translation found
    $this->row .= $data;
   }else {
    # convert special characters to html entities
    # for instance, this handles cases when $tran has single (or double) quotes
    $tran = htmlspecialchars($tran,ENT_QUOTES);  
    $this->row .= "<ab n='$tran'>$data</ab>";
   }
  }else {
   // some other tag. just return $data unchanged
   $this->row .= $data;
   dbgprint($this->dbg,"BasicAdjustLexParser. lex chrhndl. parent={$this->parentEl}, data=$data\n");
  }

 }
public function htmlspecial($text) {
 // we need this function in this class also
 // First, use the php function to convert quotes to html entities:
 // This converts single quote to &#039;
 $tooltip = htmlspecialchars($text,ENT_QUOTES);
 // since the result is parsed again with xml_parser, and xml_parser
 // autoconverts (apparently) &#039; back to single quote,
 // and then generates a parse error if this single quote occurs
 //  within an atribute value expresses as <x attr='y'>  (i.e. y has a
 //  single quote).
 // Because of this we change &#039; to &#8217;  -- which xml_parser
 // apparently leaves unchanged, and generates no error.
 $tooltip = preg_replace('/&#039;/','&#8217;',$tooltip);
 return $tooltip;
}
}
?>
