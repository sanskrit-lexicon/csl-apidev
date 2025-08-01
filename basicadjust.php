<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
?>
<?php 
/*basicadjust.php
BasicAdjust class  Takes a parameter object
   and a list of xml records from the dictionary database,
   and adjust each of these records so it is ready for the BasicDisplay.
   This was formerly done by the BasicDisplay class in line_adjust function.
   The hope is to have dictionary specific code in this BasicAdjust class,
   and to have the BasicDisplay class to be identical for all dictionaries.
   11-20-2023 revised for abch
*/
require_once('dal.php');
require_once('dbgprint.php');
class BasicAdjust {
 public $getParms;
 public $adjxmlrecs;
 public $dal_ab, $dal_auth; // 
 public $accent;
 public $dbg;
 public $pagecol;
 public $pagecol_pc;  //11-15-2024 for '<ab>col.</ab> n' links
 public $dict, $key;
 public function __construct($getParms,$xmlrecs) {
  $this->accent = $getParms->accent;
  $dict = $getParms->dict;
  $this->dict = $dict;
  $key = $getParms->key;
  $this->key = $key;
  $this->dbg=false;
  $this->dal_ab = new Dal($dict,"ab");
  if (in_array($dict,array('pwg','pw','pwkvn'))) {
   $this->dal_auth = new Dal($dict,"bib");  # pwgbib
   dbgprint(false,"basicadjust: bib file open? " . $this->dal_auth->status ."\n");
  }else if (in_array($dict,array('mw','ap90','ben','sch','gra','bhs'))){
   $this->dal_auth = new Dal($dict,"authtooltips");
  }else {
   $this->dal_auth = null;
  }
 
  $this->getParms = $getParms;
  $this->adjxmlrecs = array();
  $i = 0;
  foreach($xmlrecs as $line) {
   $this->pagecol = '';
   $line1 = $this->line_adjust($line);
   $this->adjxmlrecs[] = $line1;
   $i = $i + 1;
   dbgprint($this->dbg,"basicadjust: i=$i line=\n$line\n\nadjline=$line1\n");
  }
 }


 public function line_adjust($line) {
 $dbg = false;
 if (in_array($this->dict,array('abch', 'acph', 'acsj'))) {
  // for koshas like abch
  $line = preg_replace('|<hwdetails>(.*?)</hwdetails>|',
         '<div style="background-color: beige;">\1</div>',$line);
  $line = preg_replace_callback('|<hwdetail><hw>(.*?)</hw><meaning>(.*?)</meaning></hwdetail>|',
    'BasicAdjust::kosha_meaning_callback',
     $line);
  //dbgprint(true,"before kosha_syns_callback\n$line\n");
  $line = preg_replace_callback('|<hwdetail><eid>(.*?)</eid><syns>(.*?)</syns></hwdetail>|',
    'BasicAdjust::kosha_syns_callback',
     $line);
  //dbgprint(true,"after syns_callback\n$line\n");
  
  $line = preg_replace('|<entrydetails>(.*?)</entrydetails>|',
   '<lb/>\1',$line);
  $line = preg_replace('|<entrydetail>(.*?)</entrydetail>|',
           '<div>\1</div>',$line);	   
  return $line;
 } else {
 // All other dictionaries
 $line = preg_replace('/¦/',' ',$line);
 // chg_markup currently only applies to gra dictionary
 // Nov. 2024. Also used in mw dictionary
 $line = preg_replace_callback('|<chg +type="(.*?)" +n="(.*?)" src="(.*?)">(.*?)</chg>|',"BasicAdjust::chg_markup",$line);
 $line = preg_replace_callback('|<info vn="(.*?)"/>|',"BasicAdjust::infovn_markup",$line);
           
 $line = preg_replace_callback('|<s>(.*?)</s>|','BasicAdjust::s_callback',$line);
 $line = preg_replace_callback('|<key2>(.*?)</key2>|','BasicAdjust::key2_callback',$line);
 //$line = preg_replace("|\[Page.*?\]|",  "<pb>$0</pb>",$line);
 $line = preg_replace("|\[(Page.*?)\]|",  "<pb>$1</pb>",$line);

 $line = preg_replace('/<pc>Page(.*)<\/pc>/',"<pc>\\1</pc>",$line);

 if (preg_match('/<pc>(.*)<\/pc>/',$line,$matches)){
  $this->pagecol_pc = $matches[1]; 
  if($this->pagecol == $matches[1]){
   // so basicdisplay does not show repetitive "Printed book page" links
   $line = preg_replace('/<pc>(.*)<\/pc>/','',$line);
  }else {$this->pagecol = $matches[1];}
 }
 if (in_array($this->getParms->dict,array('pw'))) {
  // supplement note for entries merged into pw. 02-08-2024
  $line = preg_replace_callback('|<info n="sup_(.*?)"/>|',
      "BasicAdjust::infovn_markup_pw",$line);
 }
 /*  Replace the 'title' part of a known ls with its capitalized form
     This is probably particular to pwg and/or pw
 */
 if (in_array($this->getParms->dict,array('pw','pwg','pwkvn'))) { 
  $line = preg_replace_callback('|<ls(.*?)>(.*?)</ls>|',
      "BasicAdjust::ls_callback_pwg",$line);
  
      
 }else if (in_array($this->getParms->dict,
           array('mw','ap90','ben','sch','gra','bhs'))){
  //dbgprint(true,"before ls_callback_mw: $line\n");
  $line = preg_replace_callback('|<ls(.*?)>(.*?)</ls>|',
      "BasicAdjust::ls_callback_mw",$line);
  //dbgprint(true,"after ls_callback_mw: $line\n");

  $line = preg_replace('|<ls>ib[.]|','<ls><ab>ib.</ab>',$line);    
 } 
 if (in_array($this->getParms->dict,array('mw'))) {
  /* 04-16-2024  This change already present in mw.txt
  $line1 = preg_replace('|<lang n="greek">(.*?)</lang>@|',
                        '<i><gk>\1</gk></i>',$line);
  */
  // 04-08-2024
  $line1 = preg_replace('|<lang(.*?)>(.*?)</lang>|', '<ab\1>\2</ab>',$line);
  $line = $line1;
 }
 if (in_array($this->getParms->dict,array('gra', 'md'))) {
  // 06-15-2023. Treat <pe> and <lang> tags like ab
  // 12-23-2023. Also for md. And also <cl>
  // 03-29-2024. Replace '<pe>' tag by '<per>'
  $line1 = preg_replace('|<per(.*?)>(.*?)</per>|', '<ab\1>\2</ab>',$line);
  $line1 = preg_replace('|<lang(.*?)>(.*?)</lang>|', '<ab\1>\2</ab>',$line1);
  $line1 = preg_replace('|<cl(.*?)>(.*?)</cl>|', '<ab\1>\2</ab>',$line1);
  $line = $line1;
 }
 // 11-26-2023
 if (in_array($this->getParms->dict,array('pw'))) {
  $line = preg_replace('|<lang(.*?)>(.*?)</lang>|', '<ab\1>\2</ab>',$line);
 }
 // 08-21-2024 Change <s1 n="X">Y</s1> to <ab n="X">Y</ab> for tooltip
 if (in_array($this->getParms->dict,array('mw'))) {
  $line = preg_replace('|<s1( n=".*?")>(.*?)</s1>|', '<ab\1>\2</ab>',$line);
 }
 /* 08-02-2023
    For bhs,  change <lex>X</lex>, <lang>X</lang>, <ed>X</ed>, <ms>X</ms>
    to <ab>X</ab>
    Similarly <lex n="T">X</lex> etc.
 */
 if (in_array($this->getParms->dict,array('bhs'))) {
  $line = preg_replace('|<lex>|','<ab>',$line);
  $line = preg_replace('|<lex |','<ab ',$line);
  $line = preg_replace('|</lex>|','</ab>',$line);
  
  $line = preg_replace('|<lang>|','<ab>',$line);
  $line = preg_replace('|<lang |','<ab ',$line);
  $line = preg_replace('|</lang>|','</ab>',$line);

  $line = preg_replace('|<ed>|','<ab>',$line);
  $line = preg_replace('|<ed |','<ab ',$line);
  $line = preg_replace('|</ed>|','</ab>',$line);

  $line = preg_replace('|<ms>|','<ab>',$line);
  $line = preg_replace('|<ms |','<ab ',$line);
  $line = preg_replace('|</ms>|','</ab>',$line);

 }
 if (in_array($this->getParms->dict,array('mw'))) {
  // 11-14-2024 "<ab>p.</ab> 1234" -> "<ab>p.</ab> <pref>1234</pref>"
  // basicdisplay will generate a link for pref. This to call before abbrv_callback
  $line = preg_replace('|<ab>p\.</ab> ([0-9]+)|', "<ab>p.</ab> <pref>\\1</pref>", $line);
  // 11-15-2024
  $pc = $this->pagecol_pc;
  list($page,$col_unused) = preg_split("|,|",$pc);
  $line = preg_replace('|<ab>col\.</ab> ([1-3]+)|', "<ab>col.</ab> <cref>$page \\1</cref>", $line);
 }
 /* 12-14-2017
  'local' abbreviation handled here. Generate an n attribute if one
   is not present.  The 'chg_markup' callback above may introduce the 'abbr' tag.
   Since abbrv_callback is confused by this, we temporarily change '<abbr' to '<_abbr'.
   Then we do the abbrv_callback.  Then we replace '<_abbr' with '<abbr'
 */
 $line = preg_replace("|<abbr|", "<_abbr",$line);
 $line = preg_replace_callback('|<ab(.*?)>(.*?)</ab>|',"BasicAdjust::abbrv_callback",$line);
 $line = preg_replace("|<_abbr|", "<abbr",$line);
 
 // Revised 04-05-2021, 04-09-2021 for AV
 // Revised 06-16-2023 for AV.
 // old format: {AV. 1,2,3}
 // new format: <ls>AV. 1 2 3</ls>
 $dbg=false;
 dbgprint($dbg,"BasicAdjust dict={$this->getParms->dict}\n");
 if ($this->getParms->dict == 'gra') {
  dbgprint($dbg,"BasicAdjust before rgveda, avveda: $line\n");
  // $line = preg_replace_callback('|[{](AV[.] .*?)[}]|',"BasicAdjust::avveda_verse_callback",$line);
  // $line = preg_replace_callback('|<ls>(AV[.] .*?)</ls>|',"BasicAdjust::avveda_verse_callback",$line);
  $line = preg_replace_callback('|[{](.*?)[}]|',"BasicAdjust::rgveda_verse_callback",$line);
  dbgprint($dbg,"BasicAdjust after rgveda: $line\n");
 }
 if ($this->getParms->dict == 'lan') {
  /* Two types: <ls n="lan,16,4">16^4^</ls>
      <ls n="wg,1235">1235b</ls>
  */
  dbgprint($dbg,"BasicAdjust before lanman link: $line\n");
  $line = preg_replace_callback('|<ls n="(.*?)">(.*?)</ls>|',"BasicAdjust::lanman_link_callback",$line);
  dbgprint($dbg,"BasicAdjust after lanman_link: $line\n");
 }
 // Get tooltip for <lex>X</lex>, for all dictionaries
 $line = preg_replace_callback('|<lex(.*?)>(.*?)</lex>|',"BasicAdjust::add_lex_markup",$line);
  // 10-31-2023  remove <hom>X</hom> within head portion
  // 06-21-2024  For all dictionaries with some metaline with <h>
  $dicts_with_h = array("ap90", "bhs", "bop", "cae", "ccs",
                        "gra", "gst", "inm", "mci", "md",
			"mw", "mw72", "pe", "pui", "pwg","pw","pwkvn",
			"stc", "vei", "lan");
  if (in_array($this->getParms->dict, $dicts_with_h)) {
   $line = preg_replace("|<key2>(.*?)<hom>.*?</hom>(.*?<body>)|","<key2>$1$2",$line);
  }
  //
  if (in_array($this->getParms->dict, array("mw","md"))) {
   $line = $this->move_L_mw($line);
   # remove <hom>X</hom> within head portion
   $line = preg_replace("|<key2>(.*?)<hom>.*?</hom>(.*?<body>)|","<key2>$1$2",$line); 
   # remove space after sqrt 
   $line = preg_replace("|√ |u","√",$line); # experiment of 12/25/2019
  }
  else if ($this->getParms->dict == "ap90") {
   /*  ap90.xml has a line break '<lb/>' according to the printed edition.
     In the display, these are not recognized.
     Further, the display attempts to rejoin hyphenation due to line breaks.
     Finally, the pattern '<b>--X</b>' is treated as a division that generates
     a line break.
   */
   //dbgprint(true,"line before <lb> changes\n$line\n");
   $line = preg_replace('|- *<lb/>|','',$line);
   $line = preg_replace('|-</s> <lb/><s>|','',$line);
   $line = preg_replace('|<lb/>|','',$line);
   /* moved into make_xml.py 04-21-2020
   # now reintroduce some line breaks, and replace '--' with '&mdash;'
   # tech note on php:  when html entity &mdash; is used, then there is
   # an error in the xml parser in basicdisplay.php.  However, when we use 
   # the numerical code, '&#x2014;', the error disappears.
   # It might be better to do this logic (including the em-dash) in
   # make_xml.py or even in ap90.txt. E.g., change
   # <b>--X</b> to <div n="1"/><b>—X</b>
   $line = preg_replace('|<b>--|','<div n="1"/><b>&#x2014; ',$line);
   # also, there are seven instances of "<P/>". Replace with a div
   $line = preg_replace('|<P/>|','<div n="P"/>',$line);
   # remove '-' after <s> 04-11-2021
   # $line = preg_replace('|<s>--|','<div n="1"/><b>&#x2014;</b> <s>-',$line);
   $line = preg_replace('|<s>--|','<div n="1"/><b>&#x2014;</b> <s>',$line);
   // 04-11-2021.  Add line breaks at two additional patterns
   // at start of italics (about 2000 cases)
   $line = preg_replace('|<i>--|','<div n="1"/><i>&#x2014; ',$line);
   // preceding small Roman numerals (about 360 case, in verbs)
   $line = preg_replace('|--([IV]+[.])|','<div n="1"/>&#x2014; \1',$line);
   //dbgprint(true,"line after <lb> changes\n$line\n");
   // any remaining -- to mdash
   $line = preg_replace('|--|','&#x2014; ',$line);
   */
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
  } else if ($this->getParms->dict == "inm") {
   # Greek text in inm is italic
   $line = preg_replace('|<lang n="greek">|','<i><lang n="greek">',$line);
   $line = preg_replace('|</lang>|','</lang></i>',$line);
  } else if ($this->getParms->dict == "sch") {
   // this conversion now present in sch.txt
   // $line = preg_replace('|\^(.)|',"<sup>\\1</sup>",$line);
  } else if ($this->getParms->dict == "acc") {
   # this should have been done in acc.txt or acc.xml
   $line = preg_replace('|\^([a-d02th]+)|',"<sup>\\1</sup>",$line);
   $line = preg_replace('/--/','&#8212;',$line);  # emdash
   # also, remove breaks.  This is a display choice, maybe not for acc.txt,xml
   $line = preg_replace('|- <br/>|','',$line);
   $line = preg_replace('|<br/>|',' ',$line);
  }
  if ($this->getParms->dict == "mw")  {
   // 11-13-2018 make bold abbreviations following <div n="vp">
   $line = preg_replace('|(<div n="vp"/> *)(<ab.*?</ab>)|',"\\1<b>\\2</b>",$line);
  }
  return $line;
 }
}

 public function ls_matchabbr($fieldname,$fieldidx,$data) {
  $dbg = false;
  $table = $this->dal_auth->tabname;
  dbgprint($dbg,"ls_matchabbr: data=$data\n");
  //dbgprint($dbg,"  table=$table\n");
  $ans = array();  // default return value
  // Use $data. Variant of getgeneral
  if (!$this->dal_auth->file_db) {
   return $ans;
  }
  // 
  if (!preg_match("|^([^ .,']+)|",$data,$matches)) {
   return $ans;
  }
  //$tabid = 'code'; // pw, pwg, pwkvn
  $key = $matches[1];
  $key1 = $key . '%';
  $sql = "select * from $table where $fieldname LIKE '$key1'";
  dbgprint($dbg,"ls_matchabbr: sql=$sql\n");
  $result = $this->dal_auth->file_db->query($sql);
  $ansarr = array();
  $max = -1;
  $ansmax = null;
  foreach($result as $m) {
   $code0 = $m[$fieldidx];
   if (strpos($data,$code0) === 0) {
    // this is a candidate. is it the longest?
    $n = strlen($code0);
    if ($n > $max) {
     $ansmax = $m;
     $max = $n;
    }
   }
  }
  if ($ansmax == null) {
   // probably could not happen. Return default answer
   return $ans;
  }
  $ans = array($ansmax);
  return $ans;
 }
 public function ls_callback_pwg($matches) {
 // for pw, pwg
 // Two situations envisioned:
 // <ls>X</ls>  
 // <ls n="C">Y</ls>
 $dbg=false;
 $ans = $matches[0];
 $ndata = $matches[1];  // empty string or ' n="C"'
 $data0 = $matches[2];
 if (preg_match('|n="(.*?)"|',$ndata,$matchesn)) {
  $n = $matchesn[1]; //
  // $data = "$n $data0";  // controversial.
  $data1 = "$n $data0";
  $data = $data0;
 } else{
  $n = '';
  $data1 = $data0;
  $data = $data0;
 }
 dbgprint($dbg,"ls_callback_pwg BEGIN: ndata=$ndata, n=$n, data0=$data0, data1=$data1\n");
 dbgprint($dbg,"ls_callback_pwg : n=$n, data=$data\n");
 if (!$this->dal_auth->status) {
  return $ans;
 }
 $fieldname = 'code';
 $fieldidx = 1;
 $result = $this->ls_matchabbr($fieldname,$fieldidx,$data1);
 if (count($result) == 0) {
  return $ans; // failure
 }
  $rec = $result[0];
  list($n0,$code,$codecap,$text) = $rec;
  // 12-26-2017. pwg. Add lshead, so as to be able to style
  $ncode = strlen($code); // use substr_replace in case $code has parens
  if ($n != '') {
   //$datanew = preg_replace("/^$code/","<lshead></lshead>",$data);
   $datanew = $data;
   dbgprint($dbg,"pwg lshead 1: n=$n: datanew=$datanew\n");
  } else {
   //$datanew = preg_replace("/^$code/","<lshead>$codecap</lshead>",$data);
   $datanew = substr_replace($data,"<lshead>$codecap</lshead>",0,$ncode);
   dbgprint($dbg,"lshead 2: n=$n: datanew=$datanew\n");
  }
  # be sure there is no xml in the text
  $text = preg_replace('/<.*?>/',' ',$text);
  //dbgprint($dbg," ls_callback_pwg. text after removing tags: \n$text\n");
  # convert special characters to html entities
  # for instance, this handles cases when $tran has single (or double) quotes
  $tooltip = $this->htmlspecial($text);
  $tip0 = mb_substr($tooltip,0,10) . "...";
  //dbgprint($dbg," ls_callback_pwg code=$code,  codecap=$codecap, tooltip=$tip0\n");
 // 04-14-2021.  Use 'gralink' for certain values of 'code'
  //$linkcodes = array('ṚV.','AV.','P');
  $href = $this->ls_callback_pwg_href($code,$data1);
  dbgprint($dbg,"ls_callback_pwg. code=$code, data1=$data1, href=$href\n");
  if ($href != null) {
   // link
   //$ans = "<gralink href='$href' n='$tooltip'><ls>$datanew</ls></gralink>";
   $datanew1 = preg_replace("|</lshead>(.*)$|",'</lshead><span class="ls">${1}</span>',$datanew);
   //dbgprint(true,"datanew=$datanew\n");
   //dbgprint(true,"datanew1=$datanew1\n");
   if ($n == '') {
    $ans = "<gralink href='$href' n='$tooltip'><span class='ls'>$datanew1</span></gralink>";
    //dbgprint(true,"ans1=$ans\n");
   } else { // currently the same
    $ans = "<gralink href='$href' n='$tooltip'><span class='ls'>$datanew1</span></gralink>";    
    //dbgprint(true,"ans2=$ans\n");
   }
  }else {
   //$ans = "<ls n='$tooltip'>$datanew</ls>";
   $ans = "<ls n='$tooltip'><span class='dotunder ls'>$datanew</span></ls>";
  }
  dbgprint($dbg,"ls_callback_pwg: ans=$ans\n");
 
 return $ans;
}
public function ls_callback_pwg_href($code,$data) {
 $href = null; // default if no success
 $dbg = false;
 dbgprint($dbg,"ls_callback_pwg_href. data=$data\n");
 if (preg_match('|^(Spr[.]) ([0-9]+)|',$data,$matches)) {
  if (in_array($this->dict,array('pw','pwkvn'))) {
   // link to Spruche 2nd edition in pw
   $pfx = $matches[1];
   $verse = $matches[2];
   $href = "https://sanskrit-lexicon-scans.github.io/boesp2/web1/boesp.html?$verse";
   dbgprint($dbg,"Spr: href=$href\n");
   return $href;
  }
  if ($this->dict == 'pwg') {
   // link to Spruche 1st edition in pw
   $pfx = $matches[1];
   $verse = $matches[2];
   $href = "https://sanskrit-lexicon-scans.github.io/boesp1/app1/?$verse";
   return $href;
  }
 }
 /******* link to Spruche 2nd edition in pwg***********/
 if (preg_match('|^(Spr[.]) \(II\) ([0-9]+)|',$data,$matches)) {
  // Indische Sprüche in pwg (2nd edition)
  $pfx = $matches[1];
  $verse = $matches[2];
  $href = "https://sanskrit-lexicon-scans.github.io/boesp2/web1/boesp.html?$verse";
  dbgprint($dbg,"Spr: href=$href\n");
  return $href;
 }
 /******* link to Mahabharata Bombay edition - 3 parms ***********/
 if (preg_match('|^(MBH[.]) *([0-9]+) *, *([0-9]+), *([0-9]+)|',$data,$matches)) {
  $pfx = $matches[1];
  $parvan = $matches[2];
  $adhy = $matches[3];
  $verse = $matches[4];
  $href = "https://sanskrit-lexicon-scans.github.io/mbhbomb/app1?$parvan,$adhy,$verse";
  dbgprint($dbg,"$pfx: href=$href\n");
  return $href;
 }
 /******* link to Mahabharata Bombay edition - 3 parms ***********/
 if (preg_match('|^(MBH[.] ed. Bomb.) *([0-9]+) *, *([0-9]+), *([0-9]+)|',$data,$matches)) {
  $pfx = $matches[1];
  $parvan = $matches[2];
  $adhy = $matches[3];
  $verse = $matches[4];
  $href = "https://sanskrit-lexicon-scans.github.io/mbhbomb/app1?$parvan,$adhy,$verse";
  dbgprint($dbg,"$pfx: href=$href\n");
  return $href;
 }
 /******* link to Mahabharata Calcutta edition - 2 parms ***********/
 if (preg_match('|^(MBH[.]) *([0-9]+) *, *([0-9]+)[.]?$|',$data,$matches)) {
  $pfx = $matches[1];
  $parvan = $matches[2];
  $verse = $matches[3];
  $href = "https://sanskrit-lexicon-scans.github.io/mbhcalc?$parvan.$verse";
  dbgprint($dbg,"$pfx: href=$href\n");
  return $href;
 }
 /******* link to Harivamsa ***********/
 if (preg_match('|^(HARIV[.]) *([0-9]+)[.]?|',$data,$matches)) {
  // Mahabharata, Calcutta edition for harivamsa
  $pfx = $matches[1];
  $verse = $matches[2];
  $href = "https://sanskrit-lexicon-scans.github.io/hariv?$verse";
  dbgprint($dbg,"$pfx: href=$href\n");
  return $href;
 }
 /******* link to Verz. D. Oxf. H. ***********/
 $temparr = array("Verz. d. Oxf. H[.]", "Verz. der Oxf. H[.]");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+)|",$data,$matches)) {
   $pfx = $matches[1];
   $page = $matches[2];
   $href = "https://sanskrit-lexicon-scans.github.io/Oxf_Cat_Aufrecht/index.html?$page";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
 /******* link to kaTAsaritsAgara  ***********/
 $temparr = array("KATHĀS.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // taranga
   $s = $matches[3]; // shloka
   $href = "https://sanskrit-lexicon-scans.github.io/kss/index.html?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
 /******* link to Vājasaneyisaṃhitā ***********/
 $temparr = array("VS.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // adhyAya
   $s = $matches[3]; // shloka
   $href = "https://sanskrit-lexicon-scans.github.io/vajasasa/app1?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
/******* link to rajatar RĀJATARAṄGIṆĪ  ***********/
 $temparr = array("RĀJA-TAR.","RĀJAT.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // taranga
   $s = $matches[3]; // shloka
   $href = "https://sanskrit-lexicon-scans.github.io/rajatar/app1?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
 /******* link to YĀJÑAVALKYA'S Gesetzbuch  ***********/
 // pwg,pw,pwkvn  YĀJÑ. N,N;  
 $temparr = array("YĀJÑ.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // adhyAya
   $s = $matches[3]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/yajnavalkya/app1?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
 /******* link to Raghuvaṃśa  ***********/
 // pwg,pw,pwkvn  RAGH. N,N;  
 $temparr = array("RAGH.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // adhyAya
   $s = $matches[3]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/raghuvamsa/app1?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
 /******* link to Raghuvaṃśa (calcutta edition) ***********/
 // pwg,pw,pwkvn  RAGH. N,N;  
 $temparr = array("RAGH. ed. Calc.", "RAGH. \(ed. Calc.\)", "RAGH. \(Calc.\)");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // sarga
   $s = $matches[3]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/raghuvamsacalc/app1?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
 /******* link to Mārkāṇḍeyapurāṇa  ***********/
 // pwg,pw,pwkvn  MĀRK. P. N,N;  
 $temparr = array("MĀRK. P.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // adhyAya
   $s = $matches[3]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/markandeyapurana/app1?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
 /******* link to   ***********/
 // pwg,pw,pwkvn  BHAG. N,N;  
 $temparr = array("BHAG.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // adhyAya
   $s = $matches[3]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/bhagavadgita/app1?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
/******* link to Anekārthasaṃgraha of Hemacandra  ***********/
 // pwg,pw,pwkvn  H. an. N,N;  Also, an. N,N
 $temparr = array("H. an.","an.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // adhyAya
   $s = $matches[3]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/anekarthasamgraha/app1?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
/******* link to ŚĀKUNTALA (Bohtlingk edition)  ***********/
 // pwg,pw,pwkvn   
 $temparr = array("ŚĀK.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // page
   $s = $matches[3]; // linenum
   $href = "https://sanskrit-lexicon-scans.github.io/shakuntala/app2?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }else if (preg_match("|^($temp) *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // verse
   dbgprint($dbg,"$pfx: href=$href\n");
   $href = "https://sanskrit-lexicon-scans.github.io/shakuntala/app1?$t";
   return $href;
  }
 }
 /******* link to malavikagni ***********/
 // pwg,pw,pwkvn   MĀLAV. N,N and MĀLAV. N
 $temparr = array("MĀLAV.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // page
   $s = $matches[3]; // linenum
   $href = "https://sanskrit-lexicon-scans.github.io/malavikagni/app2?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }else if (preg_match("|^($temp) *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // verse
   dbgprint($dbg,"$pfx: href=$href\n");
   $href = "https://sanskrit-lexicon-scans.github.io/malavikagni/app1?$t";
   return $href;
  }
 }
 /******* link to PAÑCATANTRA, Kosegarten, 1849 pwg,pw,pwkvn  ***********/
 $temparr = array("PAÑCAT.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // page
   $s = $matches[3]; // linenum
   $href = "https://sanskrit-lexicon-scans.github.io/pantankose/app2?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }else if (preg_match("|^($temp) ([VI]+), *([0-9]+)|",$data,$matches)) {
   $tantra = $matches[2]; // roman-numeral
   $t = $this->romanToInt($tantra);
   $v = $matches[3]; // verse
   dbgprint($dbg,"$pfx: href=$href\n");
   $href = "https://sanskrit-lexicon-scans.github.io/pantankose/app1?$t,$v";
   return $href;
  }else if (preg_match("|^($temp) *(Pr\.) *([0-9]+)|",$data,$matches)) {
   $t = 0; // tantra = 0 is convention of app1 for prastAva
   $v = $matches[3]; // verse
   dbgprint($dbg,"$pfx: href=$href\n");
   $href = "https://sanskrit-lexicon-scans.github.io/pantankose/app1?$t,$v";
   return $href;
  }
 }
/******* link to Hitopadeśa, ed. Schlegel und Lassen, 1829 pwg,pw,pwkvn  ***********/
 $temparr = array("HIT.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // page
   $s = $matches[3]; // linenum
   $href = "https://sanskrit-lexicon-scans.github.io/hitopadesha/app2?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }else if (preg_match("|^($temp) ([IV]+), *([0-9]+)|",$data,$matches)) {
   $tantra = $matches[2]; // roman-numeral
   $t = $this->romanToInt($tantra);
   $v = $matches[3]; // verse
   dbgprint($dbg,"$pfx: href=$href\n");
   $href = "https://sanskrit-lexicon-scans.github.io/hitopadesha/app1?$t,$v";
   return $href;
  }else if (preg_match("|^($temp) (Pr\.) *([0-9]+)|",$data,$matches)) {
   $tantra = $matches[2]; // prastAva
   $t = 0; 
   $v = $matches[3]; // verse
   dbgprint($dbg,"$pfx: href=$href\n");
   $href = "https://sanskrit-lexicon-scans.github.io/hitopadesha/app1?$t,$v";
   return $href;
  }
 }
/******* link to amarakosha, deslongchamp, 1839 pwg,pw,pwkvn  ***********/
 $temparr = array("AK.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+), *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $b = $matches[2]; // book
   $c = $matches[3]; // chapter
   $s = $matches[4]; // section
   $v = $matches[5]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/amara_dlc/app1?$b,$c,$s,$v";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }else if (preg_match("|^($temp) *([0-9]+), *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $b = $matches[2]; // book
   $c = $matches[3]; // chapter
   $v = $matches[4]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/amara_dlc/app1?$b,$c,$v";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
/******* link to amarakosha, Colebrooke 1808 pwg,pw,pwkvn  ***********/
 $temparr = array("COL.","COLEBR.","AK. ed. COLEBR.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+), *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $b = $matches[2]; // book
   $c = $matches[3]; // chapter
   $s = $matches[4]; // section
   $v = $matches[5]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/amara_col/app1?$b,$c,$s,$v";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }else if (preg_match("|^($temp) *([0-9]+), *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $b = $matches[2]; // book
   $c = $matches[3]; // chapter
   $v = $matches[4]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/amara_col/app1?$b,$c,$v";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
/******* link to gitagovinda pwg,pw,pwkvn  ***********/
 $temparr = array("GĪT.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $a = $matches[2]; // adhyaya
   $v = $matches[3]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/gitagov/app1?$a,$v";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
 /******* link to Mugdhabodha of Vopadeva  ***********/
 // pwg,pw,pwkvn  VOP. N,N;  
 $temparr = array("VOP.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // adhyAya
   $s = $matches[3]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/mugdhabodha/app1?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
 /******* link to bhattikavya  ***********/
 // pwg,pw,pwkvn  BHAṬṬ. N,N;  
 $temparr = array("BHAṬṬ.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // sarga
   $s = $matches[3]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/bhattikavya/app1?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
 /******* link to kumara sambhava  ***********/
 // pwg,pw,pwkvn  KUMĀRAS. N,N;  
 $temparr = array("KUMĀRAS.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // sarga
   $s = $matches[3]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/kumaras/app1?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
 /******* link to shatapathabr  ***********/
 // pwg,pw,pwkvn  ŚAT. BR. N,N,N,N;  
 $temparr = array("ŚAT. BR.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+), *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $pfx = $matches[1];
   $k = $matches[2]; 
   $a = $matches[3];
   $b = $matches[4];
   $v = $matches[5];
   $href = "https://sanskrit-lexicon-scans.github.io/shatapathabr/app1?$k,$a,$b,$v";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
 /******* link to taittiriyas  ***********/
 // pwg,pw,pwkvn  TS. N,N,N,N;  
 $temparr = array("TS.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+), *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $pfx = $matches[1];
   $k = $matches[2]; 
   $a = $matches[3];
   $b = $matches[4];
   $v = $matches[5];
   $href = "https://sanskrit-lexicon-scans.github.io/taittiriyas/app1?$k,$a,$b,$v";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
 /******* link to taittiriyabr  ***********/
 // pwg,pw,pwkvn  TBR. N,N,N,N;  
 $temparr = array("TBR.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+), *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $pfx = $matches[1];
   $k = $matches[2]; 
   $a = $matches[3];
   $b = $matches[4];
   $v = $matches[5];
   $href = "https://sanskrit-lexicon-scans.github.io/taittiriyabr/app1?$k,$a,$b,$v";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
 /******* link to katyasr  ***********/
 // pwg,pw,pwkvn KĀTY. ŚR. N,N,N;  
 $temparr = array("KĀTY. ŚR.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $pfx = $matches[1];
   $k = $matches[2]; 
   $a = $matches[3];
   $b = $matches[4];
   $href = "https://sanskrit-lexicon-scans.github.io/katyasr/app1?$k,$a,$b";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
 /******* link to pancaratra  ***********/
 // pwg,pw,pwkvn PAÑCAR. N,N,N;  OR (for pwg PAÑCAR. S. N  (page N)
 $temparr = array("PAÑCAR.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $pfx = $matches[1];
   $r = $matches[2]; 
   $a = $matches[3];
   $v = $matches[4];
   $href = "https://sanskrit-lexicon-scans.github.io/pancar/app1?$r,$a,$v";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  } else if (preg_match("|^($temp) +S\. +([0-9]+)|",$data,$matches)) {
   $pfx = $matches[1];
   $p = $matches[2]; 
   $href = "https://sanskrit-lexicon-scans.github.io/pancar/app0?$p";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }

 }
 /******* link to Nalopakhyana in bchrest1  ***********/
 // pwg,pw,pwkvn  N. N,N;  
 $temparr = array("N.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // adhyAya
   $s = $matches[3]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/bchrest1/app1?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
/******* link to Dasharatha's death in bchrest1  ***********/
 // pwg,pw,pwkvn  DAŚ. N,N;  
 $temparr = array("DAŚ.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // adhyAya
   $s = $matches[3]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/bchrest1/app2?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
/******* link to story of vidushaka in bchrest1  ***********/
 // pwg,pw,pwkvn  VID. N;  
 $temparr = array("VID.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/bchrest1/app3?$t";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
/******* CAURAPAÑCĀŚIKĀ  ***********/
 // pwg,pw,pwkvn  CAURAP. N;  
 $temparr = array("CAURAP.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/bhartrhari/app1?$t";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
/******* link to Vishvamitra's battle in bchrest1  ***********/
 // pwg,pw,pwkvn  VIŚV. N,N;  
 $temparr = array("VIŚV.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // adhyAya
   $s = $matches[3]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/bchrest1/app4?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
/******* Bhartṛhariśataka ***********/
 // pwg BHARTṚ.  N,N;  //(no instances in pw, pwkvn)
 $temparr = array("BHARTṚ.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // shataka
   $s = $matches[3]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/bhartrhari/app2?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
/******* link meghaduta ***********/
 // pwg,pw,pwkvn  MEGH. N;  
 $temparr = array("MEGH.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/meghasrnga/app1?$t";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
/******* link srngaratilaka ***********/
 // pwg,pw,pwkvn ŚṚṄGĀRAT. N;  
 $temparr = array("ŚṚṄGĀRAT.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/meghasrnga/app2?$t";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }

/******* link to Medinikosha   ***********/
 // pwg,pw,pwkvn  MED. A. N;  
 $temparr = array("MED.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([khgṅcjñṭḍṇtdnpbmyrlvśṣsao]+)[.] *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // adhyAya
   $s = $matches[3]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/medini/app1?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
 /******* link to Trikandashesha of Purushottamadeva  ***********/
 // pwg,pw,pwkvn  TRIK. N,N,N;  
 $temparr = array("TRIK.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $k = $matches[2]; // kand
   $t = $matches[3]; // varga
   $s = $matches[4]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/medini/app2?$k,$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
 /******* link to Haravali of Purushottamadeva  ***********/
 // pwg,pw,pwkvn  HĀR. N;  
 $temparr = array("HĀR.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/medini/app3?$t";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
 /******* link to Abhidhānacintāmaṇipariśiṣṭa of Hemacandra  ***********/
 // pwg,pw,pwkvn  H. ś. N;  
 $temparr = array("H. ś.","ś.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/abch2/app2?$t";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
 /******* link to Abhidhānacintāmaṇi Hemacandra  ***********/
 // pwg,pw,pwkvn  H. N;  
 $temparr = array("H.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/abch2/app1?$t";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
 /******* link to Abhidhānaratnamālā of Halāyudha  ***********/
 // pwg,pw,pwkvn  H. N;  
 $temparr = array("HALĀY.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // adhyAya
   $s = $matches[3]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/armh2/app1?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
 /******* link to Manava DharmaSastra  ***********/
 $temparr = array("M.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $a = $matches[2]; // adhyaya
   $s = $matches[3]; // shloka
   $href = "https://sanskrit-lexicon-scans.github.io/manu/index.html?$a,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
 /******* link to VARĀHAMIHIRA'S BṚHATSAM̃HITĀ  ***********/
 // pwg,pw,pwkvn  VARĀH. BṚH. S. N,N 
 $temparr = array("VARĀH. BṚH. S.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // adhyAya
   $s = $matches[3]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/brihatsam/app1?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }

 /******* link to sāhityadarpaṇa  ***********/
 // pwg,pw,pwkvn  SĀH. D. N,N  or SĀH. D. N
 $temparr = array("SĀH. D.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // adhyAya
   $s = $matches[3]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/sahityadarpana/app1?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
  if (preg_match("|^($temp) *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // ipage
   $href = "https://sanskrit-lexicon-scans.github.io/sahityadarpana/app1?$t";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
 /******* link to B. Chrestomathie ***********/
 if (preg_match('|^(Chr[.]) *([0-9]+)|',$data,$matches)) {
  // Boehtlingk Chrestomathie, 2nd edition.
  if (! in_array($this->dict,array('pw'))) {
   // PWG refers under N. (Nalopakhyana), maybe others.
   // Not yet handled.
   return $href;
  }
  $pfx = $matches[1];
  $verse = $matches[2]; // page
  // 03-02-2025 Change repo name from 'bchrest' to 'bchrest2' 
  $href = "https://sanskrit-lexicon-scans.github.io/bchrest2/index.html?$verse";
  dbgprint($dbg,"$pfx: href=$href\n");
  return $href;
 }
 /******* link to Westergaard Dhatupatha  ***********/
 if(preg_match('|^(DHĀTUP[.]) *([0-9]+)(.*)$|',$data,$matches)) {
  $pfx = $matches[1];
  $section = $matches[2];  // int
  if ($section == 0) {
   // error condition
   return $href;
  }
  $dir = "https://www.sanskrit-lexicon.uni-koeln.de/scans/csl-westergaard/disp/index.php";
  $href = "$dir?section=$section";
  return $href;
 }
 /******* link to Bhagavata Purana 11-27-2024**********/
  //  pw and pwg: BHĀG. P.
  // use '|i' for case-insensitive
  // Three parameters,
  if(preg_match('|^BHĀG\. P\. *([0-9]+)[ ,]+([0-9]+)[ ,]+([0-9]+)(.*)$|i',$data,$matches)) {
  $skanda = $matches[1];
  $adhyaya = $matches[2];
  $verse = $matches[3];
  /* 01-13-2025 when skandha is 1-9, PWG links to bhagp_bur
    and for 10-12, PWG links to bhagp_bom
  */
  $iskanda = intval($skanda);
  if (in_array($iskanda,array(10,11,12))) {
   $dir = "https://sanskrit-lexicon-scans.github.io/bhagp_bom/app1";
  } else {
   $dir = "https://sanskrit-lexicon-scans.github.io/bhagp_bur/app1";
  }
  $href = "$dir/?$skanda,$adhyaya,$verse";
  return $href;
 }
  // Two parameters. set verse to 1
  if(preg_match('|^BHĀG\. P\. *([0-9]+)[ ,]+([0-9]+)(.*)$|i',$data,$matches)) {
  $skanda = $matches[1];
  $adhyaya = $matches[2];
  $verse = 1;
  // 01-13-2025 when skandha is 1-9, PWG links to bhagp_bur
  //  and for 10-12, PWG links to bhagp_bom
  //
  $iskanda = intval($skanda);
  if (in_array($iskanda,array(10,11,12))) {
   $dir = "https://sanskrit-lexicon-scans.github.io/bhagp_bom/app1";
  } else {
   $dir = "https://sanskrit-lexicon-scans.github.io/bhagp_bur/app1";
  }
  $href = "$dir/?$skanda,$adhyaya,$verse";
  return $href;
 }

/******* link to R. ed. Bomb.**********/
  //  pw and pwg: "R. ed. Bomb. N,N,N,N" or
  //  R. ed. Bomb. N,N,N  
  // use '|i' for case-insensitive
  // Four parameters
  if(preg_match('|^R\. ed\. Bomb. *([0-9])[ ,]+([0-9]+)[ ,]+([0-9]+)[ ,]+([0-9]+)(.*)$|i',$data,$matches)) {
   $kanda = $matches[1];
   $sarga = $matches[2];
   $sargap = $matches[3]; // prakshipta sarga
   $verse = $matches[4];
   $dir = "https://sanskrit-lexicon-scans.github.io/ramayanabom/app1";
   $href = "$dir/?$kanda,$sarga,$sargap,$verse";
   return $href;
 }
 // Three parameters
  if (preg_match('|^R\. ed\. Bomb. *([0-9])[ ,]+([0-9]+)[ ,]+([0-9]+)(.*)$|i',$data,$matches)) {
   $kanda = $matches[1];
   $sarga = $matches[2];
   $verse = $matches[3];
   $dir = "https://sanskrit-lexicon-scans.github.io/ramayanabom/app1";
   $href = "$dir/?$kanda,$sarga,$verse";
   return $href;
 }

/******* link to R. 7th kanda**********/
  //  pw and pwg: "R. 7,N,N,N" or
  //  "R. 7,N,N  
  // use '|i' for case-insensitive
  // Four parameters
  if(preg_match('|^R\. *(7)[ ,]+([0-9]+)[ ,]+([0-9]+)[ ,]+([0-9]+)(.*)$|i',$data,$matches)) {
   $kanda = $matches[1];
   $sarga = $matches[2];
   $sargap = $matches[3]; // prakshipta sarga
   $verse = $matches[4];
   $dir = "https://sanskrit-lexicon-scans.github.io/ramayanabom/app1";
   $href = "$dir/?$kanda,$sarga,$sargap,$verse";
   return $href;
 }
 // Three parameters
  if (preg_match('|^R\. *(7)[ ,]+([0-9]+)[ ,]+([0-9]+)(.*)$|i',$data,$matches)) {
   $kanda = $matches[1];
   $sarga = $matches[2];
   $verse = $matches[3];
   $dir = "https://sanskrit-lexicon-scans.github.io/ramayanabom/app1";
   $href = "$dir/?$kanda,$sarga,$verse";
   return $href;
 }
 
 /******* link to Rgveda, Atharvaveda, or Panini ***********/
 // 10-08-2024 code rearranged to allow 2,3 parameters
 // links for Rigveda, Atharvaveda, or Panini,
 // Ramayana Gorresio, Ramayana Schlegel
 $code_to_pfx = array('ṚV.' => 'rv', 'AV.' => 'av', 'P.' => 'p',
  'Spr.' => 'Spr',
  'R. GORR.' => 'rgorr', 'R. ed. GORR.' => 'rgorr', 'GORR.' => 'rgorr',
  'R.' => 'rschl','R. SCHL.' => 'rschl');
 if (!isset($code_to_pfx[$code])) {
  return $href;
 }
 $pfx = $code_to_pfx[$code];
 
 if (preg_match('|^(.*?)[.] *([0-9]+)[ ,]+([0-9]+)[ ,]+([0-9]+)(.*)$|',$data,$matches)) {
  // code with 3 parameters
  $code0 = $matches[1];
  $imandala = (int)$matches[2]; 
  $ihymn = (int)$matches[3];
  $iverse = (int)$matches[4];
 } else if (preg_match('|^(.*?)[.] *([0-9]+)[ ,]+([0-9]+)(.*)$|',$data,$matches)) {
  // code with 2 parameters
  $code0 = $matches[1];
  $imandala = (int)$matches[2]; 
  $ihymn = (int)$matches[3];
  $iverse = 1; // Assume verse 1
 } else {
  return $href;
 }

 dbgprint($dbg,"ls_callback_pwg_href. $code0, $imandala, $ihymn, $iverse\n");
 // $rest = $matches[5]; not used
 if (in_array($pfx,array('rv','av'))) {
  $hymnfilepfx = sprintf("%s%02d.%03d",$pfx,$imandala,$ihymn);
  $hymnfile = "$hymnfilepfx.html";
  $versesfx = sprintf("%02d",$iverse);
  $anchor = "$hymnfilepfx.$versesfx";
  $versesfx = sprintf("%02d",$iverse);
  $anchor = "$hymnfilepfx.$versesfx";
  $dir = sprintf("https://sanskrit-lexicon.github.io/%slinks/%shymns",$pfx,$pfx);
  $href = "$dir/$hymnfile#$anchor";
 }else if ($pfx == "p") {  // P.  = Panini
  $dir = "https://ashtadhyayi.com/sutraani";
  $href = "$dir/$imandala/$ihymn/$iverse";
 }else if (in_array($pfx,array('rgorr'))) { 
  $dir = "https://sanskrit-lexicon-scans.github.io/ramayanagorr";
  $href = "$dir/?$imandala,$ihymn,$iverse";
  return $href;
 }else if (in_array($pfx,array('rschl'))) {
  /* 06-13-2022. rschl is appropriate when $imandala is 1 or 2
  Otherwise ($imandala 3,4,5,6,7) rschl should change to rgorr
   This is believed to be appropriate for pwg dictionary.
   For 
  */
  if (in_array($imandala,array(1,2))) {
   $dir = "https://sanskrit-lexicon-scans.github.io/ramayanaschl";
  }else {
   $dir = "https://sanskrit-lexicon-scans.github.io/ramayanagorr";
  }
  $href = "$dir/?$imandala,$ihymn,$iverse";
  return $href;
 }
 dbgprint($dbg,"href=$href\n");
 return $href; 
}

public function ls_callback_mw($matches) {
 // Try to also handle ap90, ben, sch
 // Two situations envisioned:
 // <ls>X</ls>  
 // <ls n="C">Y</ls>
 $dbg=false;
 $ans = $matches[0];
 $ndata = $matches[1];  // empty string or ' n="C"'
 $data0 = $matches[2];
 dbgprint($dbg,"Enter ls_callback_mw. ans=$ans AND ndata=$ndata AND data0=$data0\n");
 if (preg_match('|n="(.*?)"|',$ndata,$matchesn)) {
  $n = $matchesn[1]; //
  $data1 = "$n $data0";  // controversial.
  $data = $data0; 
 } else{
  $n = '';
  $data1 = $data0;
  $data = $data0;
 }
 if (preg_match('|tit="(.*?)"|',$ndata,$matchesn)) {
  $titular = true;
 } else {
  $titular = false;
 }
 dbgprint($dbg,"\nls_callback_mw BEGIN: ndata=$ndata, n=$n, data0=$data0, data1=$data1\n");
 if (!$this->dal_auth->status) {
  return $ans;
 }
 // --------------------------------------------------------------
 // Tooltip for name of work
 $fieldname = 'key';
 if ($this->dict == 'mw') {
  $fieldidx = 1;
 }else { // ap90, ben, bhs,sch
  $fieldidx = 0;
 }
 $result = $this->ls_matchabbr($fieldname,$fieldidx,$data1);
 if (count($result) == 0) {
  dbgprint($dbg,"ls_callback_mw : ls_matchabbr returns no results\n");
  return $ans; // failure
 }
  $rec = $result[0];
  if ($this->dict == 'mw') {
   list($cid,$code,$title,$type) = $rec;
   $text = "$title ($type)";
   dbgprint($dbg,"ls_matchabbr returns: cid=$cid, code=$code, title=$title, type=$type\n");
  } else if (in_array($this->dict,array('ap90','ben','sch','gra','bhs'))) {
   list($code,$text) = $rec;
  }
  // be sure there is no xml in the text
  if ($text == null) {$text = "";}
  $text = preg_replace('/<.*?>/',' ',$text);
  // convert special characters to html entities
  // for instance, this handles cases when $text has single (or double) quotes
  $tooltip = $this->htmlspecial($text);
  dbgprint($dbg,"ls_callback_mw : n=$n, data=$data\n");
  if ($code == null) {$code = "";}
  $codecap = $code;
  $ncode = strlen($code); // use substr_replace in case $code has parens
  if (! $titular) {
   if ($code == $data0) {
    $titular = true;
   }
  }
  if ($titular) {
   // 07-05-2024. display of 'empty' ls 
   // $style = 'font-size: 11pt; font-family:charterindocapital; border-bottom: 1px dotted #000; ';
   $style = 'border-bottom: 1px dotted #000; color:#8080ff;';
   $ans = "<span title='$tooltip' style='$style'>$code</span>";
   return $ans;
  } 
 
  if ($n != '') {
   $datanew = $data;
   dbgprint($dbg,"lshead 1: n=$n: datanew=$datanew\n");
  } else {
   $datanew = substr_replace($data,"<lshead>$codecap</lshead>",0,$ncode);
   dbgprint($dbg,"lshead 2: n=$n: datanew=$datanew\n");
  }
  // --------------------------------------------------------------
  $href = null;
  //dbgprint(true,"before ls_callback_mw_href, dict=" . $this->dict . "\n");
  if ($this->dict == 'mw') {
   $href = $this->ls_callback_mw_href($code,$n,$data);
  }else if ($this->dict == 'ap90') {
   $href = $this->ls_callback_ap90_href($code,$n,$data);
  }else if ($this->dict == 'gra') {
   $href = $this->ls_callback_mw_href($code,$n,$data);
  }else if ($this->dict == 'bhs') {
   $href = $this->ls_callback_mw_href($code,$n,$data);
  }else if ($this->dict == 'sch') {
   $href = $this->ls_callback_sch_href($code,$n,$data);
  }
  dbgprint($dbg,"ls_callback_mw: href=$href\n");
  if ($href != null) {
   // link
   //$ans = "<gralink href='$href' n='$tooltip'><ls>$datanew</ls></gralink>";
   dbgprint($dbg,"ls_callback_mw: n=$n, datanew=$datanew\n");
   if ($n == '') {
    $datanew1 = preg_replace("|</lshead>(.*)$|",'</lshead><span class="ls">${1}</span>',$datanew);
   }else {
    $datanew1 = '<span class="ls">' . $datanew . '</span>';
   }
   //dbgprint(true,"datanew1=$datanew1\n");
   $ans = "<gralink href='$href' n='$tooltip'><span class='ls'>$datanew1</span></gralink>";
  }else {
   $ans = "<ls n='$tooltip'><span class='dotunder ls'>$datanew</span></ls>";
  }
  dbgprint($dbg,"ls_callback_mw: ans=$ans\n");
 return $ans;
}
public function ls_callback_mw_href($code,$n,$data) {
 $href = null; // default if no success
 $dbg = false;
 dbgprint($dbg,"ls_callback_mw_href. code=$code, n='$n', data='$data'\n");
 $code_to_pfx = array('RV.' => 'rv', 'AV.' => 'av', 'Pāṇ.' => 'p',
  'MBh.' => 'MBH.','Hariv.' => 'hariv',
  'MBh. (ed. Calc.)' => 'MBHC', 'MBh. (ed. Bomb.)' => 'MBHB',
  'R.' => 'R', 'R. G.' => 'R', 'R. (G)' => 'R', 'R. (G.)' => 'R', 'R. [G]' => 'R',
  'R. ed. Gorresio' => 'R', 'Dhātup.' => 'dp', 'Dhāt.' => 'dp',
   'Kathās.' => 'kathas', 'Mn.' => 'M.', 'BhP.' => 'bhp',
   'Yājñ.' => 'yajn', 'Ragh.' => 'ragh', 'Sāh.' => 'sahitya',
   'Vop.' => 'vop', 'Halāy.' => 'halay',
   'VarBṛS.' => 'brihatsam', 
   'MārkP.' => 'markandeyap', // mw
   'Mārk P.' => 'markandeyap', // sch
   'H. an.' => 'anekarthaS', // sch  No references in mw
   'Śāk.' => 'shakuntala', // sch
   'Śak.' => 'shakuntalamw', // mw    ??
   'Śat. Br.' => 'shatapathabr', // sch
   'ŚBr.' => 'shatapathabr', // mw
   'Sāh. D.' => 'sahityadarpana', // sch
   'Sāh.' => 'sahitya', // mw
   'Bhag.' => 'bhagavadgita', // mw, sch
   'R. ed. Bomb.' => 'ramayanabom', // mw, sch
   'Pañcat.' => 'pantankose', // mw, sch
   'VS.' => 'vajasasa', 'TS.' => 'taittiriyas',
   'Ragh. ed. Calc.' => 'raghuvamsacalc', // sch
   'Ragh. (C)' => 'raghuvamsacalc', // mw
   'Rājat.' => "rajatar", //mw, sch
   'Bhaṭṭ.' => "bhattikavya", //mw, sch
   'TBr.' => 'taittiriyabr',
   'KātyŚr.' => 'katyasr', //mw
   'Kāty. Śr.' => 'katyasr1', // sch
   'Kumāras.' => 'kumaras', // sch
   'Kum.' => 'kumaras', // mw
   'Mālav.' => 'malavikagni', // mw, sch
   'Śṛṅgār.' => 'meghasrnga', // mw
   'Megh.' => 'meghaduta', // mw, sch
   'Caurap. (A.)' => 'Caurapañcāśikā', // sch
   'Caurap.'  => 'Caurapañcāśikā', // mw
   'Bhartṛ.'  => 'Bhartṛhariśataka', // mw
   'Hit.' => 'Hit.', // mw, sch
   'AK.' => 'AK.', // sch (none for mw)
   'Gīt.' => 'Gīt.', // mw, sch
   'Pañcar.' => 'pancar', // mw, sch
   );
 //hrefs for MBHC, MBHB not implemented. MBHC is same as MBH.(?)
 if (!isset($code_to_pfx[$code])) {
  dbgprint($dbg,"ls_callback_mw_href. Code is unknown:'$code'\n");
  return $href;
 }
 $pfx = $code_to_pfx[$code];
 dbgprint($dbg,"ls_callback_mw_href: code=$code, pfx=$pfx\n");
 if ($n == '') {
  $data1 = $data;
 }else {
  $data1 = "$n $data";
 }
 dbgprint($dbg,"  n = '$n', data1 = '$data1'\n");
 /******* link to Rgveda, Atharvaveda ***********/
 if (in_array($pfx,array('rv','av'))) {
  if (preg_match('|^(.*?)[.] *([^ ,]+)[ ,]+([0-9]+)[ ,]+([0-9]+)(.*)$|',$data1,$matches)) {
   $code0 = $matches[1];
   $mandala = $matches[2];  
   if ($this->dict == 'mw') {
    // in lower-case roman numerals for mw
    $imandala = $this->roman_int($mandala);
   }else if ($this->dict == 'gra') {
    $imandala = (int)($mandala);
   }
   $ihymn = (int)$matches[3];
   $iverse = (int)$matches[4];
   dbgprint($dbg,"ls_callback_mw_href. $code0, $mandala, $ihymn, $iverse\n");
   $rest = $matches[5];
   $hymnfilepfx = sprintf("%s%02d.%03d",$pfx,$imandala,$ihymn);
   $hymnfile = "$hymnfilepfx.html";
   $versesfx = sprintf("%02d",$iverse);
   $anchor = "$hymnfilepfx.$versesfx";
   $versesfx = sprintf("%02d",$iverse);
   $anchor = "$hymnfilepfx.$versesfx";
   $dir = sprintf("https://sanskrit-lexicon.github.io/%slinks/%shymns",$pfx,$pfx);
   $href = "$dir/$hymnfile#$anchor";
   return $href;
  }else if (preg_match('|^(.*?)[.] *([^ ,]+)[ ,]+([0-9]+)(.*)$|',$data1,$matches))
  { // two parameter version. Supply verse number = 1
   $code0 = $matches[1];
   $mandala = $matches[2];  // in lower-case roman numerals for mw
   $imandala = $this->roman_int($mandala);
   if ($imandala == 0) {return $href;}
   $ihymn = (int)$matches[3];
   $iverse = 1;  // line to verse 1.
   dbgprint($dbg,"ls_callback_mw_href. $code0, $mandala, $ihymn, $iverse\n");
   $rest = $matches[5];
   $hymnfilepfx = sprintf("%s%02d.%03d",$pfx,$imandala,$ihymn);
   $hymnfile = "$hymnfilepfx.html";
   $versesfx = sprintf("%02d",$iverse);
   $anchor = "$hymnfilepfx.$versesfx";
   $versesfx = sprintf("%02d",$iverse);
   $anchor = "$hymnfilepfx.$versesfx";
   $dir = sprintf("https://sanskrit-lexicon.github.io/%slinks/%shymns",$pfx,$pfx);
   $href = "$dir/$hymnfile#$anchor";
   return $href;
  }else {
   return $href; // failure to match
  }
 } // end for rv, av
 /******* link to Panini ***********/
 if (in_array($pfx,array('p'))) {
  //if(! preg_match('|^(.*?)[.] *([0-9]+)-([0-9]+)[ ,]+([0-9]+)(.*)$|',$data1,$matches)) 
  // Panini for mw.   10-07-2021
  if(! preg_match('|^(.*?)[.] *([iv]+)[ ,]+([0-9]+)[ ,]+([0-9]+)(.*)$|',$data1,$matches)) {
    return $href;
   }
   $code0 = $matches[1];
   //$ic = (int)$matches[2];
   $romanlo = $matches[2];
   $ic = $this->roman_int($romanlo); 
   $is = (int)$matches[3];
   $iv = (int)$matches[4];
   $dir = "https://ashtadhyayi.com/sutraani";
   $href = "$dir/$ic/$is/$iv";
   return $href;
 }
/******* link to R. 7,N,N,N for mw (bombay editition ***********/
 if (preg_match('|^(R[.]) *(vii), *([0-9]+), *([0-9]+), *([0-9]+)[^0-9,]?|',$data1,$matches)) {
  $pfx = $matches[1];
  $kand_roman = $matches[2];
  $k = $this->roman_int($kand_roman);
  $s = $matches[3]; // sarga
  $sp = $matches[4]; // sarga prakzipta
  $v = $matches[5]; // verse
  $href = "https://sanskrit-lexicon-scans.github.io/ramayanabom/app1/?$k,$s,$sp,$v";
  dbgprint($dbg,"ls_callback_mw_href: $pfx: href=$href\n");
  return $href;
 }

/******* link to R. 7,N,N for mw (bombay edition ***********/
 if (preg_match('|^(R[.]) *(vii), *([0-9]+), *([0-9]+)[^0-9,]?|',$data1,$matches)) {
  $pfx = $matches[1];
  $kand_roman = $matches[2];
  $k = $this->roman_int($kand_roman);
  $s = $matches[3]; // sarga
  $v = $matches[4]; // verse
  $href = "https://sanskrit-lexicon-scans.github.io/ramayanabom/app1/?$k,$s,$v";
  dbgprint($dbg,"ls_callback_mw_href: $pfx: href=$href\n");
  return $href;
 }
 /******* link to Ramayana, Gorresio or Schlegel edition  ***********/
 if (in_array($pfx,array('R'))) {
  // Ramayana, Goressio. Similar to 'p' (Panini), except for '$dir'
  dbgprint($dbg,"ls_callback_mw_href: data1=$data1\n");
  // data1 = code + data2.
  $data2 = substr_replace($data1,"",0,strlen($code));
  //$data2 = trim($data2);
  dbgprint($dbg,"ls_callback_mw_href: data2=$data2\n");
  if(! preg_match('| *([iv]+)[ ,]+([0-9]+)[ ,]+([0-9]+)(.*)$|',$data2,$matches)) {
    return $href;
   }
   $romanlo = $matches[1];
   $ic = $this->roman_int($romanlo); 
   $is = (int)$matches[2];
   $iv = (int)$matches[3];
   $dir = "https://sanskrit-lexicon-scans.github.io/ramayanagorr";
   // 01-19-2024. Ref: https://github.com/sanskrit-lexicon/MWS/issues/151
   if (($this->dict == 'mw') && (in_array($ic,array(1,2)))) {
    $dir = "https://sanskrit-lexicon-scans.github.io/ramayanaschl";
   }
   $href = "$dir/?$ic,$is,$iv";
   return $href;
 }
 dbgprint($dbg,"ls_callback_mw_href: data1=$data1\n");

 /******* link to R. ed. Bomb. for mw***********/
 if (in_array($pfx,array('ramayanabom'))) {
  $data2 = substr_replace($data1,"",0,strlen($code));
  //$data2 = trim($data2);
  dbgprint($dbg,"ls_callback_mw_href: data2=$data2\n");
  if(! preg_match('| *([iv]+)[ ,]+([0-9]+)[ ,]+([0-9]+)(.*)$|',$data2,$matches)) {
    return $href;
   }
   $romanlo = $matches[1];
   $k = $this->roman_int($romanlo); 
   $s = (int)$matches[2];
   $v = (int)$matches[3];
   $href = "https://sanskrit-lexicon-scans.github.io/ramayanabom/app1/?$k,$s,$v";
   return $href;
 }
 /******* link to PAÑCATANTRA, Kosegarten, 1849 mw  ***********/
 $temparr = array("Pañcat.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // page
   $s = $matches[3]; // linenum
   $href = "https://sanskrit-lexicon-scans.github.io/pantankose/app2?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }else if (preg_match("|^($temp) ([vi]+), *([0-9]+)|",$data,$matches)) {
   return $href; // pantankose not the right source !
   $tantra = $matches[2]; // roman-numeral
   $t = $this->romanToInt($tantra);
   $v = $matches[3]; // verse
   dbgprint($dbg,"$pfx: href=$href\n");
   $href = "https://sanskrit-lexicon-scans.github.io/pantankose/app1?$t,$v";
   return $href;
  }else if (preg_match("|^($temp) *(Pr\.) *([0-9]+)|",$data,$matches)) {
   $t = 0; // tantra = 0 is convention of app1 for prastAva
   $v = $matches[3]; // verse
   dbgprint($dbg,"$pfx: href=$href\n");
   $href = "https://sanskrit-lexicon-scans.github.io/pantankose/app1?$t,$v";
   return $href;
  }
 }
 /******* link to Hitopadeśa, ed. Schlegel und Lassen, 1829 mw  ***********/
 $temparr = array("Hit.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // page
   $s = $matches[3]; // linenum
   $href = "https://sanskrit-lexicon-scans.github.io/hitopadesha/app2?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }else if (preg_match("|^($temp) ([iv]+), *([0-9]+)|",$data,$matches)) {
   $tantra = $matches[2]; // roman-numeral
   $t = $this->romanToInt($tantra);
   $v = $matches[3]; // verse
   dbgprint($dbg,"$pfx: href=$href\n");
   $href = "https://sanskrit-lexicon-scans.github.io/hitopadesha/app1?$t,$v";
   return $href;
  }else if (preg_match("|^($temp) (Introd\.) *([0-9]+)|",$data,$matches)) {
   $tantra = $matches[2]; // prastAva
   $t = 0; 
   $v = $matches[3]; // verse
   dbgprint($dbg,"$pfx: href=$href\n");
   $href = "https://sanskrit-lexicon-scans.github.io/hitopadesha/app1?$t,$v";
   return $href;
  }
 }

/******* link to  Mahabharata, Calcutta edition for mw. ***********/
 if (preg_match('|^(MBh[.]) *([^ ,]+) *, *([0-9]+)[.]?$|',$data1,$matches)) {
  // Mahabharata, Calcutta edition for mw.
  $pfx = $matches[1];
  $parvan_roman = $matches[2];
  $parvan = $this->roman_int($parvan_roman);
  $verse = $matches[3];
  $href = "https://sanskrit-lexicon-scans.github.io/mbhcalc?$parvan.$verse";
  dbgprint($dbg,"ls_callback_mw_href: $pfx: href=$href\n");
  return $href;
 }
/******* link to  Mahabharata, Bombay edition for mw.  3 parameters***********/
 if (preg_match('|^(MBh[.]) *([xiv]+), *([0-9]+), *([0-9]+)|',$data1,$matches)) {
  // Mahabharata, Calcutta edition for mw.
  $pfx = $matches[1];
  $parvan_roman = $matches[2];
  $parvan = $this->roman_int($parvan_roman);
  $adhy = $matches[3];
  $verse = $matches[4];
  $href = "https://sanskrit-lexicon-scans.github.io/mbhbomb/app1/?$parvan,$adhy,$verse";
  dbgprint($dbg,"ls_callback_mw_href: $pfx: href=$href\n");
  return $href;
 }

/******* link to harivamsa  ***********/
 if (preg_match('|^(Hariv[.]) *([0-9]+)[.]?$|',$data,$matches)) {
  // Mahabharata, Calcutta edition for harivamsa. For MW.
  $pfx = $matches[1];
  $verse = $matches[2];
  $href = "https://sanskrit-lexicon-scans.github.io/hariv?$verse";
  dbgprint($dbg,"$pfx: href=$href\n");
  return $href;
 }
 /******* link to meghaduta for mw  ***********/
 if (preg_match('|^(Megh[.]) *([0-9]+)[.]?$|',$data,$matches)) {
  $pfx = $matches[1];
  $verse = $matches[2];
  $href = "https://sanskrit-lexicon-scans.github.io/meghasrnga/app1?$verse";
  dbgprint($dbg,"$pfx: href=$href\n");
  return $href;
 }
 /******* link to Srngatilaka for mw  ***********/
 if (preg_match('|^(Śṛṅgār[.]) *([0-9]+)[.]?$|',$data,$matches)) {
  $pfx = $matches[1];
  $verse = $matches[2];
  $href = "https://sanskrit-lexicon-scans.github.io/meghasrnga/app2?$verse";
  dbgprint($dbg,"$pfx: href=$href\n");
  return $href;
 }

 /******* link to Westergaard Dhatupatha  ***********/
 if (in_array($pfx,array('dp'))) {
  // ## one roman numeral 
  if(!preg_match('|^(.*?)[.] *([ixv]+)(.*)$|',$data1,$matches)) {
    return $href;
   }
  $pfx = $matches[1];
  $section_roman = $matches[2];
  $section = $this->romanToInt($section_roman);
  
  if ($section == 0) {
   // error condition
   return $href;
  }
  $dir = "https://www.sanskrit-lexicon.uni-koeln.de/scans/csl-westergaard/disp/index.php";
  $href = "$dir?section=$section";
  return $href;
 }
 /******* link to Katha Sarit Sagara  ***********/
 if (in_array($pfx,array('kathas'))) {
  // ## two parameters 
  if(!preg_match("|^$code +([^.,]+), *([0-9]+)|",$data1,$matches)) {
    return $href;
   }
  $taranga_raw = $matches[1];
  $s = $matches[2];
  // normally, in mw taranga is in lower-case roman numeral,
  // but in a few cases, taranga is a digit sequence
  // The link target requires digit sequence
  if (preg_match("|^[0-9]+$|",$taranga_raw,$matches_temp)) {
   $t = $taranga_raw;
  } else {
   $t = $this->romanToInt($taranga_raw);
   if ($t == 0) {
    // error condition tar
    return $href;
   }
  }
  $href = "https://sanskrit-lexicon-scans.github.io/kss/index.html?$t,$s";
  return $href;
 }
 /******* link to Vājasaneyisaṃhitā for mw  ***********/
 if (in_array($pfx,array('vajasasa'))) {
  // ## two parameters 
  if(!preg_match("|^$code +([ivx]+), *([0-9]+)|",$data1,$matches)) {
    return $href;
   }
  $adhy_raw = $matches[1];
  $s = $matches[2];
  $a = $this->romanToInt($adhy_raw);
  if ($a == 0) {
   // error condition 
   return $href;
  }
  $href = "https://sanskrit-lexicon-scans.github.io/vajasasa/app1?$a,$s";
  return $href;
 }

 /******* link to Gitagovinda for mw  ***********/
 if (in_array($pfx,array('Gīt.'))) {
  // ## two parameters 
  if(!preg_match("|^$code +([ivx]+), *([0-9]+)|",$data1,$matches)) {
    return $href;
   }
  $adhy_raw = $matches[1];
  $v = $matches[2];
  $a = $this->romanToInt($adhy_raw);
  if ($a == 0) {
   // error condition 
   return $href;
  }
  $href = "https://sanskrit-lexicon-scans.github.io/gitagov/app1?$a,$v";
  return $href;
 }
 /******* link to malavikagni for mw  ***********/
 if (in_array($pfx,array('malavikagni'))) {
  if (preg_match("|^$code +([ivx]+), *([0-9]+)|",$data1,$matches)) {
   // ## two parameters anka, shloka --
   $adhy_raw = $matches[1];
   $s = $matches[2];
   $a = $this->romanToInt($adhy_raw);
   if ($a == 0) {
    // error condition 
    return $href;
   }
   $href = "https://sanskrit-lexicon-scans.github.io/malavikagni/app3?$a,$s";
   return $href;
  }
 }
 /******* link to Rājataraṅgiṇī for mw  ***********/
 if (in_array($pfx,array('rajatar'))) {
  // ## two parameters 
  if(!preg_match("|^$code +([ivx]+), *([0-9]+)|",$data1,$matches)) {
    return $href;
   }
  $adhy_raw = $matches[1];
  $s = $matches[2];
  $a = $this->romanToInt($adhy_raw);
  if ($a == 0) {
   // error condition 
   return $href;
  }
  $href = "https://sanskrit-lexicon-scans.github.io/rajatar/app1?$a,$s";
  return $href;
 }
 /******* link to bhattikavya for mw  ***********/
 if (in_array($pfx,array('bhattikavya'))) {
  // ## two parameters 
  if(!preg_match("|^$code +([ivx]+), *([0-9]+)|",$data1,$matches)) {
    return $href;
   }
  $adhy_raw = $matches[1];
  $s = $matches[2];
  $a = $this->romanToInt($adhy_raw);
  if ($a == 0) {
   // error condition 
   return $href;
  }
  $href = "https://sanskrit-lexicon-scans.github.io/bhattikavya/app1?$a,$s";
  return $href;
 }
 /******* link to Bhartṛhariśataka for mw  ***********/
 if (in_array($pfx,array('Bhartṛhariśataka'))) {
  // ## two parameters 
  if(!preg_match("|^$code +([ivx]+), *([0-9]+)|",$data1,$matches)) {
    return $href;
   }
  $adhy_raw = $matches[1];
  $s = $matches[2];
  $a = $this->romanToInt($adhy_raw);
  if ($a == 0) {
   // error condition 
   return $href;
  }
  $href = "https://sanskrit-lexicon-scans.github.io/bhartrhari/app2?$a,$s";
  return $href;
 }
 /******* link to Caurapañcāśikā for mw 1 parameter ***********/
 if (in_array($pfx,array('Caurapañcāśikā'))) {
  // ## two parameters 
  if(!preg_match("|^$code +([0-9]+)|",$data1,$matches)) {
    return $href;
   }
  $s = $matches[1];
  $href = "https://sanskrit-lexicon-scans.github.io/bhartrhari/app1?$s";
  return $href;
 }
/******* link to kumarasambhava for mw  ***********/
 if (in_array($pfx,array('kumaras'))) {
  // ## two parameters 
  if(!preg_match("|^$code +([ivx]+), *([0-9]+)|",$data1,$matches)) {
    return $href;
   }
  $adhy_raw = $matches[1]; // sarga
  $s = $matches[2];
  $a = $this->romanToInt($adhy_raw);
  if ($a == 0) {
   // error condition 
   return $href;
  }
  $href = "https://sanskrit-lexicon-scans.github.io/kumaras/app1?$a,$s";
  return $href;
 }
  /******* link to katyasr for mw 3 parameters ***********/
 if (in_array($pfx,array('katyasr'))) {
  // ## two parameters 
  if(!preg_match("|^$code +([ivx]+), *([0-9]+), *([0-9]+)|",$data1,$matches)) {
    return $href;
   }
  $adhy_raw = $matches[1];
  $k = $matches[2];
  $v = $matches[3];
  $a = $this->romanToInt($adhy_raw);
  if ($a == 0) {
   // error condition 
   return $href;
  }
  $href = "https://sanskrit-lexicon-scans.github.io/katyasr/app1?$a,$k,$v";
  return $href;
 }
  /******* link to pancar for mw 3 parameters ***********/
 if (in_array($pfx,array('pancar'))) {
  if(!preg_match("|^$code +([ivx]+), *([0-9]+), *([0-9]+)|",$data1,$matches)) {
    return $href;
   }
  $r_raw = $matches[1];
  $a = $matches[2];
  $v = $matches[3];
  $r = $this->romanToInt($r_raw);
  if ($r == 0) {
   // error condition 
   return $href;
  }
  $href = "https://sanskrit-lexicon-scans.github.io/pancar/app1?$r,$a,$v";
  return $href;
 }
/******* link to Taittirīya-Sam̃hitā for mw  ***********/
 if (in_array($pfx,array('taittiriyas'))) {
  // ## 4 parameters 
  if(!preg_match("|^$code +([ivx]+), *([0-9]+), *([0-9]+), *([0-9]+)|",$data1,$matches)) {
    return $href;
   }
  $a_raw = $matches[1];
  $b = $matches[2];
  $c = $matches[3];
  $d = $matches[4];
  $a = $this->romanToInt($a_raw);
  if ($a == 0) {
   // error condition 
   return $href;
  }
  $href = "https://sanskrit-lexicon-scans.github.io/taittiriyas/app1?$a,$b,$c,$d";
  return $href;
 }
/******* link to Taittirīya-Brahmana for mw  ***********/
 if (in_array($pfx,array('taittiriyabr'))) {
  // ## 4 parameters 
  if(!preg_match("|^$code +([ivx]+), *([0-9]+), *([0-9]+), *([0-9]+)|",$data1,$matches)) {
    return $href;
   }
  $a_raw = $matches[1];
  $b = $matches[2];
  $c = $matches[3];
  $d = $matches[4];
  $a = $this->romanToInt($a_raw);
  if ($a == 0) {
   // error condition 
   return $href;
  }
  $href = "https://sanskrit-lexicon-scans.github.io/taittiriyabr/app1?$a,$b,$c,$d";
  return $href;
 }

 /******* link to yajnavalkya for mw ***********/
 if ( (in_array($pfx,array('yajn'))) && (in_array($this->dict,array('mw'))) ) {
  // ## two parameters 
  if(!preg_match("|^$code +([^.,]+), *([0-9]+)|",$data1,$matches)) {
    return $href;
   }
  $taranga_raw = $matches[1];
  $s = $matches[2];
  // normally, in mw taranga is in lower-case roman numeral,
  // but in a few cases, taranga is a digit sequence
  // The link target requires digit sequence
  if (preg_match("|^[0-9]+$|",$taranga_raw,$matches_temp)) {
   $t = $taranga_raw;
  } else {
   $t = $this->romanToInt($taranga_raw);
   if ($t == 0) {
    // error condition tar
    return $href;
   }
  }
  $href = "https://sanskrit-lexicon-scans.github.io/yajnavalkya/app1?$t,$s";
  return $href;
 }
 /******* link to bhagavadgita for mw ***********/
 if ( (in_array($pfx,array('bhagavadgita'))) && (in_array($this->dict,array('mw'))) ) {
  // ## two parameters 
  if(!preg_match("|^$code +([vix]+), *([0-9]+)|",$data1,$matches)) {
    return $href;
   }
  $taranga_raw = $matches[1];
  $s = $matches[2];
  // normally, in mw taranga is in lower-case roman numeral,
  // but in a few cases, taranga is a digit sequence
  // The link target requires digit sequence
  if (preg_match("|^[0-9]+$|",$taranga_raw,$matches_temp)) {
   $t = $taranga_raw;
  } else {
   $t = $this->romanToInt($taranga_raw);
   if ($t == 0) {
    // error condition tar
    return $href;
   }
  }
  $href = "https://sanskrit-lexicon-scans.github.io/bhagavadgita/app1?$t,$s";
  return $href;
 }

 /******* link to Raghuvṃśa for mw ***********/
 if ( (in_array($pfx,array('ragh'))) && (in_array($this->dict,array('mw'))) ) {
  // ## two parameters 
  if(!preg_match("|^$code +([^.,]+), *([0-9]+)|",$data1,$matches)) {
    return $href;
   }
  $taranga_raw = $matches[1];
  $s = $matches[2];
  // normally, in mw taranga is in lower-case roman numeral,
  // but in a few cases, taranga is a digit sequence
  // The link target requires digit sequence
  if (preg_match("|^[0-9]+$|",$taranga_raw,$matches_temp)) {
   $t = $taranga_raw;
  } else {
   $t = $this->romanToInt($taranga_raw);
   if ($t == 0) {
    // error condition tar
    return $href;
   }
  }
  $href = "https://sanskrit-lexicon-scans.github.io/raghuvamsa/app1?$t,$s";
  return $href;
 }

 /******* link to Raghuvṃśa Calcutta edition for mw ***********/
 if ( (in_array($pfx,array('raghuvamsacalc'))) && (in_array($this->dict,array('mw'))) ) {
  // ## two parameters 
  //if (!preg_match("|^$code +([ivx]+), *([0-9]+)|",$data1,$matches)) {
  if (!preg_match("|^Ragh. \(C\) +([ivx]+), *([0-9]+)|",$data1,$matches)) {
    return $href;
  }
  $taranga_raw = $matches[1];
  $s = $matches[2];
  $t = $this->romanToInt($taranga_raw);
  if ($t == 0) {
   // error condition tar
   return $href;
  }
  $href = "https://sanskrit-lexicon-scans.github.io/raghuvamsacalc/app1?$t,$s";
  return $href;
 }

 /******* link to markandeya purana for mw ***********/
 if ( (in_array($pfx,array('markandeyap'))) && (in_array($this->dict,array('mw'))) ) {
  // ## two parameters 
  if(!preg_match("|^$code +([^.,]+), *([0-9]+)|",$data1,$matches)) {
    return $href;
   }
  $taranga_raw = $matches[1];
  $s = $matches[2];
  // normally, in mw taranga is in lower-case roman numeral,
  // but in a few cases, taranga is a digit sequence
  // The link target requires digit sequence
  if (preg_match("|^[0-9]+$|",$taranga_raw,$matches_temp)) {
   $t = $taranga_raw;
  } else {
   $t = $this->romanToInt($taranga_raw);
   if ($t == 0) {
    // error condition tar
    return $href;
   }
  }
  $href = "https://sanskrit-lexicon-scans.github.io/markandeyapurana/app1?$t,$s";
  return $href;
 }

 /******* link to BṚHATSAM̃HITĀ for mw ***********/
 if ( (in_array($pfx,array('brihatsam'))) && (in_array($this->dict,array('mw'))) ) {
  // ## two parameters 
  if(!preg_match("|^$code +([^.,]+), *([0-9]+)|",$data1,$matches)) {
    return $href;
   }
  $taranga_raw = $matches[1];
  $s = $matches[2];
  // normally, in mw taranga is in lower-case roman numeral,
  // but in a few cases, taranga is a digit sequence
  // The link target requires digit sequence
  if (preg_match("|^[0-9]+$|",$taranga_raw,$matches_temp)) {
   $t = $taranga_raw;
  } else {
   $t = $this->romanToInt($taranga_raw);
   if ($t == 0) {
    // error condition tar
    return $href;
   }
  }
  $href = "https://sanskrit-lexicon-scans.github.io/brihatsam/app1?$t,$s";
  return $href;
 }
 /******* link to Śatapatha-brāhmaṇa for mw ***********/
 if ( (in_array($pfx,array('shatapathabr'))) && (in_array($this->dict,array('mw'))) ) {
  // ## four parameters 
  if(!preg_match("|^$code +([^.,]+), *([0-9]+), *([0-9]+), *([0-9]+)|",$data1,$matches)) {
    return $href;
   }
  $k_raw = $matches[1];
  $a = $matches[2];
  $b = $matches[3];
  $v = $matches[4];
  // normally, in mw kanda is in lower-case roman numeral,
  // but in a few cases, kanda is a digit sequence
  // The link target requires digit sequence
  if (preg_match("|^[0-9]+$|",$k_raw,$matches_temp)) {
   $k = $k_raw;
  } else {
   $k = $this->romanToInt($k_raw);
   if ($k == 0) {
    // error condition tar
    return $href;
   }
  }
  $href = "https://sanskrit-lexicon-scans.github.io/shatapathabr/app1?$k,$a,$b,$v";
  return $href;
 }
 /******* link to mugdhabodha for mw ***********/
 if ( (in_array($pfx,array('vop'))) && (in_array($this->dict,array('mw'))) ) {
  // ## two parameters 
  if(!preg_match("|^$code +([^.,]+), *([0-9]+)|",$data1,$matches)) {
    return $href;
   }
  $taranga_raw = $matches[1];
  $s = $matches[2];
  // normally, in mw taranga is in lower-case roman numeral,
  // but in a few cases, taranga is a digit sequence
  // The link target requires digit sequence
  if (preg_match("|^[0-9]+$|",$taranga_raw,$matches_temp)) {
   $t = $taranga_raw;
  } else {
   $t = $this->romanToInt($taranga_raw);
   if ($t == 0) {
    // error condition tar
    return $href;
   }
  }
  $href = "https://sanskrit-lexicon-scans.github.io/mugdhabodha/app1?$t,$s";
  return $href;
 }
 /******* link to sahityadarpana for mw ***********/
 if ( (in_array($pfx,array('sahitya'))) && (in_array($this->dict,array('mw'))) ) {
  // case R(oman),N
  if(preg_match("|^$code +([xivcl]+), *([0-9]+)|",$data1,$matches)) { 
   $p_raw = $matches[1]; // pariccheda
   $k = $matches[2]; //kArikA
   $p = $this->romanToInt($p_raw);
   if ($p == 0) {
    // error condition tar
    return $href;
   }
   // 02-27-2025 Jim doesn't know how to find this in sahityadarpana
   $href = "https://sanskrit-lexicon-scans.github.io/sahityadarpana_mw/app1?$p,$k";
   return $href;
  }
  // case N,N
  if(preg_match("|^$code +([0-9]+), *([0-9]+)|",$data1,$matches)) { 
   $t = $matches[1];
   $s = $matches[2];
   $href = "https://sanskrit-lexicon-scans.github.io/sahityadarpana/app1?$t,$s";
   return $href;
  }
  // case N
  if(preg_match("|^$code +([0-9]+)|",$data1,$matches)) { 
   $t = $matches[1];
   $href = "https://sanskrit-lexicon-scans.github.io/sahityadarpana/app1?$t";
   return $href;
  }
  return $href;  // no href found
 }
 /******* link to Halay for mw ***********/
 if ( (in_array($pfx,array('halay'))) && (in_array($this->dict,array('mw'))) ) {
  // ## two parameters 
  if(!preg_match("|^$code +([^.,]+), *([0-9]+)|",$data1,$matches)) {
    return $href;
   }
  $taranga_raw = $matches[1];
  $s = $matches[2];
  // normally, in mw taranga is in lower-case roman numeral,
  // but in a few cases, taranga is a digit sequence
  // The link target requires digit sequence
  if (preg_match("|^[0-9]+$|",$taranga_raw,$matches_temp)) {
   $t = $taranga_raw;
  } else {
   $t = $this->romanToInt($taranga_raw);
   if ($t == 0) {
    // error condition tar
    return $href;
   }
  }
  $href = "https://sanskrit-lexicon-scans.github.io/armh2/app1?$t,$s";
  return $href;
 }

/******* link to manusmrti  ***********/
 if (in_array($pfx,array('M.'))) {
  // ## two parameters 
  if(!preg_match("|^$code +([^.,]+), *([0-9]+)|",$data1,$matches)) {
    return $href;
   }
  $adhyaya_raw = $matches[1];
  $s = $matches[2];
  // normally, in mw adhyaya is in lower-case roman numeral,
  // but in a few cases (?), adhyaya is a digit sequence
  // The link target requires digit sequence
  if (preg_match("|^[0-9]+$|",$adhyaya_raw,$matches_temp)) {
   $t = $adhyaya_raw;
  } else {
   $t = $this->romanToInt($adhyaya_raw);
   if ($t == 0) {
    // error condition
    return $href;
   }
  }
  $href = "https://sanskrit-lexicon-scans.github.io/manu/index.html?$t,$s";
  return $href;
 }
 /******* link to Bhagavata Purana 11-27-2024 ***********/
 if (in_array($pfx,array('bhp'))) {
  // First parameter is lower-case roman numeral
  if(! preg_match('|^(.*?)[.] *([ivx]+)[ ,]+([0-9]+)[ ,]+([0-9]+)(.*)$|i',$data1,$matches)) {
    return $href;
   }
   $code0 = $matches[1];
   //$ic = (int)$matches[2];
   $roman = $matches[2];
   $skanda = $this->romanToInt($roman); 
   $adhyaya = $matches[3];
   $verse = $matches[4];
  /* 01-13-2025 when skandha is 1-9, PWG links to bhagp_bur
    and for 10-12, PWG links to bhagp_bom
  */
  $iskanda = intval($skanda);
  if (in_array($iskanda,array(10,11,12))) {
   $dir = "https://sanskrit-lexicon-scans.github.io/bhagp_bom/app1";
  } else {
   $dir = "https://sanskrit-lexicon-scans.github.io/bhagp_bur/app1";
  }
   $href = "$dir/?$skanda,$adhyaya,$verse";
   return $href;
 }

 return $href; 
}
public function ls_callback_sch_href($code,$n,$data) {
 $href = null; // default if no success
 $dbg = false;
 dbgprint($dbg,"ls_callback_sch_href. code=$code, n=$n, data=$data\n");
 $code_to_pfx = array('ṚV.' => 'rv', 'AV.' => 'av', 'P.' => 'p', 'Hariv.' => 'hariv', 'R. Gorr.' => 'rgorr','R.' => 'rschl', 'Dhātup.' => 'dp', 'Spr.' => 'spr',
 'Verz. d. Oxf. H.' => 'verzoxf', 'Kathās.' => 'kathas', 'M.' => 'M.',
 'Bhāg. P.' => 'bhagp','Yājñ.' => 'yajn', 'Ragh.' => 'ragh','Sāh. D.' => 'sahitya', 'Vop.' => 'vop',
 'Med.' => 'med', 'Trik.' => 'trik', 'Hār.' => 'har', 'Halāy.' => 'halay',
 'Varāh. Bṛh. S.' => 'brihatsam', 'Mārk. P.' => 'markandeyap', 'H. an.' => 'anekarthaS',
 'Śāk.' => 'shakuntala', 'Śat. Br.' => 'shatapathabr',
 'Sāh. D.' => 'sahityadarpana', 'Bhag.' => 'bhagavadgita', 
 'R. ed. Bomb.' => 'ramayanabom', 
 'Pañcat.' => 'pantankose', 'VS.' => 'vajasasa', 'TS.' => 'taittiriyas',
 'Ragh. ed. Calc.' => 'raghuvamsacalc',
 'Rājat.' => 'rajatar', 'Bhaṭṭ.' => 'bhattikavya',
 'Tbr.' => 'taittiriyabr','Kāty. Śr.' => 'katyasr', 'Kumāras.' => 'kumaras',
 'Mālav.' => 'malavikagni', 'Megh.' => 'meghaduta',
 'Caurap. (A.)' => 'Caurapañcāśikā', // sch
 'MBh.' => 'MBH', 'Hit.' => 'Hit.', 'AK.' => 'AK.', 'Gīt.' => 'Gīt.',
 'Pañcar.' => 'pancar', 
 );
 if (!isset($code_to_pfx[$code])) {
  return $href;
 }
 $pfx = $code_to_pfx[$code];
 if ($n == '') {
  $data1 = $data;
 }else {
  $data1 = "$n $data";
 }
 dbgprint($dbg,"ls_callback_sch_href: data1=$data1\n");
  if (preg_match('|^(Spr[.]) ([0-9]+)|',$data1,$matches)) { // 09-08-2024
   // Indische Sprüche in sch is assumed to be volume 2
   $code0 = $matches[1];
   $verse = $matches[2];
   $href = "https://sanskrit-lexicon-scans.github.io/boesp2/web1/boesp.html?$verse";
   dbgprint($dbg,"Spr: href=$href\n");
   return $href;
  }
 /******* link to Verz. D. Oxf. H. ***********/
 $temparr = array("Verz. d. Oxf. H[.]");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+)|",$data1,$matches)) {
   $pfx = $matches[1];
   $page = $matches[2];
   $href = "https://sanskrit-lexicon-scans.github.io/Oxf_Cat_Aufrecht/index.html?$page";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
 /******* link to kathasaritsagara (for sch) ***********/
 $temparr = array("Kathās.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data1,$matches)) {
   $t = $matches[2]; // taranga
   $s = $matches[3]; // shloka
   $href = "https://sanskrit-lexicon-scans.github.io/kss/index.html?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }  
 }
 /******* link to Vājasaneyisaṃhitā for sch ***********/
 $temparr = array("VS.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // adhyAya
   $s = $matches[3]; // shloka
   $href = "https://sanskrit-lexicon-scans.github.io/vajasasa/app1?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
 /******* link to Rājataraṅgiṇī for sch ***********/
 $temparr = array("Rājat.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // taranga
   $s = $matches[3]; // shloka
   $href = "https://sanskrit-lexicon-scans.github.io/rajatar/app1?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
 /******* link to bhattikavya for sch ***********/
 $temparr = array("Bhaṭṭ.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // sarga
   $s = $matches[3]; // shloka
   $href = "https://sanskrit-lexicon-scans.github.io/bhattikavya/app1?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
 /******* link to kumaras for sch ***********/
 $temparr = array("Kumāras.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // sarga
   $s = $matches[3]; // shloka
   $href = "https://sanskrit-lexicon-scans.github.io/kumaras/app1?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
/******* link to Taittirīya-Sam̃hitā for sch ***********/
 $temparr = array("TS.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+), *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $a = $matches[2];
   $b = $matches[3];
   $c = $matches[4];
   $d = $matches[5];
   $href = "https://sanskrit-lexicon-scans.github.io/taittiriyas/app1?$a,$b,$c,$d";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
/******* link to Taittirīya-Brahmana for sch ***********/
 $temparr = array("Tbr.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+), *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $a = $matches[2];
   $b = $matches[3];
   $c = $matches[4];
   $d = $matches[5];
   $href = "https://sanskrit-lexicon-scans.github.io/taittiriyabr/app1?$a,$b,$c,$d";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
/******* link to katyasr for sch ***********/
 $temparr = array("Kāty. Śr.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $a = $matches[2];
   $b = $matches[3];
   $c = $matches[4];
  $href = "https://sanskrit-lexicon-scans.github.io/katyasr/app1?$a,$b,$c";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
/******* link to pancar for sch ***********/
 $temparr = array("Pañcar.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $a = $matches[2];
   $b = $matches[3];
   $c = $matches[4];
  $href = "https://sanskrit-lexicon-scans.github.io/pancar/app1?$a,$b,$c";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
 /******* link to yajnavalkya (for sch) ***********/
 $temparr = array("Yājñ.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data1,$matches)) {
   $t = $matches[2]; // adhyaya
   $s = $matches[3]; // shloka
   $href = "https://sanskrit-lexicon-scans.github.io/yajnavalkya/app1?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }  
 }
 /******* link to Ramayana, Bombay edition (for sch) ***********/
 $temparr = array("R. ed. Bomb.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+), *([0-9]+)|",$data1,$matches)) {
   $k = $matches[2];
   $s = $matches[3];
   $v = $matches[4];
   $href = "https://sanskrit-lexicon-scans.github.io/ramayanabom/app1?$k,$s,$v";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }  
 }
 /******* link to Ramayana, Bombay edition (for sch) ***********/
 $temparr = array("R. ed. Bomb.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+), *([0-9]+)|",$data1,$matches)) {
   $k = $matches[2];
   $s = $matches[3];
   $v = $matches[4];
   $href = "https://sanskrit-lexicon-scans.github.io/ramayanabom/app1?$k,$s,$v";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }  
 }
 /******* link to mahabharata, Bombay edition (for sch) ***********/
 $temparr = array("MBh.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+), *([0-9]+)|",$data1,$matches)) {
   $p = $matches[2]; 
   $a = $matches[3];
   $v = $matches[4];
   $href = "https://sanskrit-lexicon-scans.github.io/mbhbomb/app1?$p,$a,$v";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }  
 }
 /******* link to PAÑCATANTRA, Kosegarten, 1849 sch  ***********/
 $temparr = array("Pañcat.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // page
   $s = $matches[3]; // linenum
   $href = "https://sanskrit-lexicon-scans.github.io/pantankose/app2?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }else if (preg_match("|^($temp) ([vi]+), *([0-9]+)|",$data,$matches)) {
   $tantra = $matches[2]; // roman-numeral
   $t = $this->romanToInt($tantra);
   $v = $matches[3]; // verse
   dbgprint($dbg,"$pfx: href=$href\n");
   $href = "https://sanskrit-lexicon-scans.github.io/pantankose/app1?$t,$v";
   return $href;
  }else if (preg_match("|^($temp) *(Pr\.) *([0-9]+)|",$data,$matches)) {
   $t = 0; // tantra = 0 is convention of app1 for prastAva
   $v = $matches[3]; // verse
   dbgprint($dbg,"$pfx: href=$href\n");
   $href = "https://sanskrit-lexicon-scans.github.io/pantankose/app1?$t,$v";
   return $href;
  }
 }
/******* link to Hitopadeśa, ed. Schlegel und Lassen, 1829 sch  ***********/
 $temparr = array("Hit.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // page
   $s = $matches[3]; // linenum
   $href = "https://sanskrit-lexicon-scans.github.io/hitopadesha/app2?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }else if (preg_match("|^($temp) ([IV]+), *([0-9]+)|",$data,$matches)) {
   $tantra = $matches[2]; // roman-numeral
   $t = $this->romanToInt($tantra);
   $v = $matches[3]; // verse
   dbgprint($dbg,"$pfx: href=$href\n");
   $href = "https://sanskrit-lexicon-scans.github.io/hitopadesha/app1?$t,$v";
   return $href;
  }else if (preg_match("|^($temp) (Pr\.) *([0-9]+)|",$data,$matches)) {
   $tantra = $matches[2]; // prastAva
   $t = 0; 
   $v = $matches[3]; // verse
   dbgprint($dbg,"$pfx: href=$href\n");
   $href = "https://sanskrit-lexicon-scans.github.io/hitopadesha/app1?$t,$v";
   return $href;
  }
 }
/******* link to amarakosha, deslongchamp, 1839 sch  ***********/
 $temparr = array("AK.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+), *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $b = $matches[2]; // book
   $c = $matches[3]; // chapter
   $s = $matches[4]; // section
   $v = $matches[5]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/amara_dlc/app1?$b,$c,$s,$v";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }else if (preg_match("|^($temp) *([0-9]+), *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $b = $matches[2]; // book
   $c = $matches[3]; // chapter
   $v = $matches[4]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/amara_dlc/app1?$b,$c,$v";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
/******* link to gitagov, sch  ***********/
 $temparr = array("Gīt.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $a = $matches[2]; // adhyaya
   $v = $matches[3]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/gitagov/app1?$a,$v";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
 /******* link to raghuvamsa (for sch) ***********/
 $temparr = array("Ragh.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data1,$matches)) {
   $t = $matches[2]; // adhyaya
   $s = $matches[3]; // shloka
   $href = "https://sanskrit-lexicon-scans.github.io/raghuvamsa/app1?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }  
 }
 /******* link to raghuvamsacalc (for sch) ***********/
 $temparr = array("Ragh. ed. Calc.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data1,$matches)) {
   $t = $matches[2]; // adhyaya
   $s = $matches[3]; // shloka
   $href = "https://sanskrit-lexicon-scans.github.io/raghuvamsacalc/app1?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }  
 }

 /******* link to markandeya purana (for sch) ***********/
 $temparr = array("Mārk. P.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data1,$matches)) {
   $t = $matches[2]; // adhyaya
   $s = $matches[3]; // shloka
   $href = "https://sanskrit-lexicon-scans.github.io/markandeyapurana/app1?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }  
 }
 /******* link to bhagavadgita (for sch) ***********/
 $temparr = array("Bhag.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data1,$matches)) {
   $t = $matches[2]; // adhyaya
   $s = $matches[3]; // shloka
   $href = "https://sanskrit-lexicon-scans.github.io/bhagavadgita/app1?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }  
 }
 /******* link to shatapathabr (for sch) ***********/
 $temparr = array("Śat. Br.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+), *([0-9]+), *([0-9]+)|",$data1,$matches)) {
   $k = $matches[2]; 
   $a = $matches[3];
   $b = $matches[4];
   $v = $matches[5];
   $href = "https://sanskrit-lexicon-scans.github.io/shatapathabr/app1?$k,$a,$b,$v";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }  
 }
 /******* link to anekarthasamgraha (for sch) ***********/
 $temparr = array("H. an.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data1,$matches)) {
   $t = $matches[2]; // adhyaya
   $s = $matches[3]; // shloka
   $href = "https://sanskrit-lexicon-scans.github.io/anekarthasamgraha/app1?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }  
 }
/******* link to ŚĀKUNTALA (Bohtlingk edition)  ***********/
 // sch  
 $temparr = array("Śāk.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // page
   $s = $matches[3]; // linenum
   $href = "https://sanskrit-lexicon-scans.github.io/shakuntala/app2?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }else if (preg_match("|^($temp) *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/shakuntala/app1?$t";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }
/******* sch link to malavikagni  ***********/
 // sch  Mālav. N,N  or Mālav. N
 $temparr = array("Mālav.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // page
   $s = $matches[3]; // linenum
   $href = "https://sanskrit-lexicon-scans.github.io/malavikagni/app2?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }else if (preg_match("|^($temp) *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/malavikagni/app1?$t";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }

/******* link to  SĀHITYADARPAṆA for sch ***********/
 // sch  
 $temparr = array("Sāh. D.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // page
   $s = $matches[3]; // linenum
   $href = "https://sanskrit-lexicon-scans.github.io/sahityadarpana/app1?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }else if (preg_match("|^($temp) *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/sahityadarpana/app1?$t";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 } 
 /******* link to  VARĀHAMIHIRA'S BṚHATSAM̃HITĀ (for sch) ***********/
 $temparr = array("Varāh. Bṛh. S.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data1,$matches)) {
   $t = $matches[2]; // adhyaya
   $s = $matches[3]; // shloka
   $href = "https://sanskrit-lexicon-scans.github.io/brihatsam/app1?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }  
 }
 /******* link to mugdhabodha (for sch) ***********/
 $temparr = array("Vop.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data1,$matches)) {
   $t = $matches[2]; // adhyaya
   $s = $matches[3]; // shloka
   $href = "https://sanskrit-lexicon-scans.github.io/mugdhabodha/app1?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }  
 }
 /******* link to sahityadarpana for sch ***********/
 if ( (in_array($pfx,array('sahitya'))) && (in_array($this->dict,array('sch'))) ) {
  // case N,N
  if(preg_match("|^$code +([0-9]+), *([0-9]+)|",$data1,$matches)) { 
   $t = $matches[1];
   $s = $matches[2];
   $href = "https://sanskrit-lexicon-scans.github.io/sahityadarpana/app1?$t,$s";
   return $href;
  }
  // case N
  if(preg_match("|^$code +([0-9]+)|",$data1,$matches)) { 
   $t = $matches[1];
   $href = "https://sanskrit-lexicon-scans.github.io/sahityadarpana/app1?$t";
   return $href;
  }
  return $href;  // no href found
 }
 /******* link to Medinikosha (for sch) ***********/
 $temparr = array("Med.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([khgṅcjñṭḍṇtdnpbmyrlvśṣsao]+)[.] *([0-9]+)|",$data1,$matches)) {
   $t = $matches[2]; // adhyaya
   $s = $matches[3]; // shloka
   $href = "https://sanskrit-lexicon-scans.github.io/medini/app1?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }  
 }
 /******* link to Trikandashesha (for sch) ***********/
 $temparr = array("Trik.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+), *([0-9]+)|",$data1,$matches)) {
   $k = $matches[2]; // kand
   $t = $matches[3]; // varga
   $s = $matches[4]; // shloka
   $href = "https://sanskrit-lexicon-scans.github.io/medini/app2?$k,$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }  
 }
 /******* link to Haravali (for sch) ***********/
 $temparr = array("Hār.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+)|",$data1,$matches)) {
   $s = $matches[2]; // shloka
   $href = "https://sanskrit-lexicon-scans.github.io/medini/app3?$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }  
 }
 /******* link to Halayudha (for sch) ***********/
 $temparr = array("Halāy.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data1,$matches)) {
   $t = $matches[2]; // adhyaya
   $s = $matches[3]; // shloka
   $href = "https://sanskrit-lexicon-scans.github.io/armh2/app1?$t,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }  
 }

 /******* link to manava dharmashastra (for sch) ***********/
 $temparr = array("M.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+), *([0-9]+)|",$data1,$matches)) {
   $a = $matches[2]; // adhyAya
   $s = $matches[3]; // shloka
   $href = "https://sanskrit-lexicon-scans.github.io/manu/index.html?$a,$s";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }  
 }
 /******* link to Bhagavata Purana 12-09-2024**********/
 if ($pfx == 'bhagp') {
  //  sch : Bhāg. P.
  // use '|i' for case-insensitive
  // Three parameters,
  //dbgprint(true,"Bhāg. P.: data=$data\n");
  if(preg_match("|^$code ([0-9]+)[ ,]+([0-9]+)[ ,]+([0-9]+)(.*)$|i",$data1,$matches)) {
  $skanda = $matches[1];
  $adhyaya = $matches[2];
  $verse = $matches[3];
  $dir = "https://sanskrit-lexicon-scans.github.io/bhagp_bur/app1";
  /* 01-13-2025 when skandha is 1-9, PWG links to bhagp_bur
    and for 10-12, PWG links to bhagp_bom
  */
  $iskanda = intval($skanda);
  if (in_array($iskanda,array(10,11,12))) {
   $dir = "https://sanskrit-lexicon-scans.github.io/bhagp_bom/app1";
  } else {
   $dir = "https://sanskrit-lexicon-scans.github.io/bhagp_bur/app1";
  }
  $href = "$dir/?$skanda,$adhyaya,$verse";
 }
 return $href;
}

 /******* link to Rgveda, Atharvaveda ***********/
 //$dbg = true;
 dbgprint($dbg,"pfx=$pfx, data1=::$data1::, code=$code\n");
 if (in_array($pfx,array('rv','av'))) {
  // #, #, #  (three decimal numbers, separated by commas)
  $regex = "|^($code) *([0-9]+)[,] *([0-9]+)[,] *([0-9]+)(.*)$|";
  if (!preg_match($regex,$data1,$matches)) {
   dbgprint($dbg,"No match to data1\n");
   dbgprint($dbg,"regex=$regex\n");
   return $href;
  }
  $code0 = $matches[1];
  $imandala = (int)$matches[2];
  $ihymn = (int)$matches[3];
  $iverse = (int)$matches[4];
  dbgprint($dbg,"ls_callback_ap90_href. $code0, $mandala, $ihymn, $iverse\n");
  $rest = $matches[5];
  $hymnfilepfx = sprintf("%s%02d.%03d",$pfx,$imandala,$ihymn);
  $hymnfile = "$hymnfilepfx.html";
  $versesfx = sprintf("%02d",$iverse);
  $anchor = "$hymnfilepfx.$versesfx";
  $versesfx = sprintf("%02d",$iverse);
  $anchor = "$hymnfilepfx.$versesfx";
  $dir = sprintf("https://sanskrit-lexicon.github.io/%slinks/%shymns",$pfx,$pfx);
  $href = "$dir/$hymnfile#$anchor";
  return $href;
 } // end for rv, av
 /******* link to Panini ***********/
 if (in_array($pfx,array('p'))) {
  // #, #, # (three decimal numbers, separated by commas)
  //if(!preg_match('|^(.*?)[.] *([0-9]+)[,] +([0-9]+)[,] +([0-9]+)(.*)$|',$data1,$matches)) {
  $regex = "|^($code) *([0-9]+)[,] *([0-9]+)[,] *([0-9]+)(.*)$|";
  if (!preg_match($regex,$data1,$matches)) {
    return $href;
   }
   $code0 = $matches[1];
   $ic = (int)$matches[2];
   $is = (int)$matches[3];
   $iv = (int)$matches[4];
   $dir = "https://ashtadhyayi.com/sutraani";
   $href = "$dir/$ic/$is/$iv";
   return $href;
 }
/******* link meghaduta sch ***********/
 // pwg,pw,pwkvn  Megh. N;  
 $temparr = array("Megh.");
 foreach($temparr as $temp) {
  if (preg_match("|^($temp) *([0-9]+)|",$data,$matches)) {
   $t = $matches[2]; // verse
   $href = "https://sanskrit-lexicon-scans.github.io/meghasrnga/app1?$t";
   dbgprint($dbg,"$pfx: href=$href\n");
   return $href;
  }
 }

 /******* link to HarivaMSa ***********/
 if (in_array($pfx,array('hariv'))) {
  // ## one decimal numbers
  if(!preg_match('|^(.*?)[.] *([0-9]+)(.*)$|',$data1,$matches)) {
    return $href;
   }
  // Mahabharata, Calcutta edition for harivamsa
  $pfx = $matches[1];
  $verse = $matches[2];
  $href = "https://sanskrit-lexicon-scans.github.io/hariv?$verse";
  dbgprint($dbg,"$pfx: href=$href\n");
  return $href;
 }
 /******* link to Caurapañcāśikā, Ariel edition ***********/
 // The code is 'Caurap. (A.)'  That paren causes problems
 if (in_array($pfx,array('Caurapañcāśikā'))) {
  // ## one decimal numbers
  if(!preg_match('|^(.*?) *([0-9]+)(.*)$|',$data1,$matches)) {
    return $href;
   }
  // This is the Ariel edition. Currently no link target, so return null 
  return $href;
  $pfx = $matches[1];
  $verse = $matches[2];
  $href = "https://sanskrit-lexicon-scans.github.io/bhartrhari/app1?$verse";
  dbgprint($dbg,"$pfx: href=$href\n");
  return $href;
 }
 /******* link to Ramayana, Gorresio edition  ***********/
 if (in_array($pfx,array('rgorr'))) {
  // #, #, # (three decimal numbers, separated by commas)
  if(!preg_match('|^(.*?)[.] *([0-9]+)[,] *([0-9]+)[,] *([0-9]+)(.*)$|',$data1,$matches)) {
    return $href;
   }
   $code0 = $matches[1];
   $ic = (int)$matches[2];
   $is = (int)$matches[3];
   $iv = (int)$matches[4];
   $dir = "https://sanskrit-lexicon-scans.github.io/ramayanagorr";
   $href = "$dir/?$ic,$is,$iv";
   return $href;
 }
 /******* link to Ramayana, Schlegel edition  ***********/
 if (in_array($pfx,array('rschl'))) {
  // #, #, # (three decimal numbers, separated by commas)
  if(!preg_match('|^(.*?)[.] *([0-9]+)[,] *([0-9]+)[,] *([0-9]+)(.*)$|',$data1,$matches)) {
    return $href;
   }
   $code0 = $matches[1];
   $ic = (int)$matches[2];
   $is = (int)$matches[3];
   $iv = (int)$matches[4];
   if (in_array($ic,array(1,2))) {
    $dir = "https://sanskrit-lexicon-scans.github.io/ramayanaschl";
   } else {
    $dir = "https://sanskrit-lexicon-scans.github.io/ramayanagorr";
   }
   $href = "$dir/?$ic,$is,$iv";
   return $href;
 }
 /******* link to Westergaard Dhatupatha  ***********/
 if (in_array($pfx,array('dp'))) {
  if(preg_match('|^(Dhātup[.]) *([0-9]+)(.*)$|',$data1,$matches)) {
   // the 'subsection' number is present in the reference, but unused here
   // Example: <ls>Dhātup. 10,18.</ls>  Only 10 is used in link, 18 ignored.
   $pfx = $matches[1];
   $section = $matches[2];  // int
   if ($section == 0) {
    // error condition
    return $href;
   }
   $dir = "https://www.sanskrit-lexicon.uni-koeln.de/scans/csl-westergaard/disp/index.php";
   $href = "$dir?section=$section";
   return $href;
  }
 }
 return $href; 
}
public function romanToInt($s0) {
 // suggested by edge copilot, with additional error checking
 $s = strtoupper($s0);
 $romans = array(
     'I' => 1,
     'V' => 5,
     'X' => 10,
     'L' => 50,
     'C' => 100,
     'D' => 500,
     'M' => 1000
 );
 $result = 0;
 for ($i = 0; $i < strlen($s); $i++) {
  $si = $s[$i];
  if (! array_key_exists($si,$romans)) {
   // input error condition. Return 0
   return 0;
  }
  $ri = $romans[$si];
  if ($i == 0) {
   $result += $ri;
   continue;
  }
  $j = $i - 1;
  $sj = $s[$j];
  $rj = $romans[$sj];
  if ($i > 0 && $ri > $rj) {
      $result += $ri - 2 * $rj;
  } else {
      $result += $ri;
  }
 }
 return $result;
}

public function ls_callback_ap90_href($code,$n,$data) {
 $href = null; // default if no success
 $dbg = false;
 dbgprint($dbg,"ls_callback_ap90_href. code=$code, n=$n, data=$data\n");
 $code_to_pfx = array('Rv.' => 'rv', 'Av.' => 'av', 'P.' => 'p');
 if (!isset($code_to_pfx[$code])) {
  return $href;
 }
 $pfx = $code_to_pfx[$code];
 if ($n == '') {
  $data1 = $data;
 }else {
  $data1 = "$n $data";
 }
 if (in_array($pfx,array('rv','av'))) {
  // #. #. #  (three numbers,
  if (!preg_match('|^(.*?)[.] *([0-9]+)[.] +([0-9]+)[.] +([0-9]+)(.*)$|',$data1,$matches)) {
   return $href;
  }
  $code0 = $matches[1];
  //$mandala = $matches[2];  // in 
  //$imandala = $this->roman_int($mandala);
  $imandala = (int)$matches[2];
  $ihymn = (int)$matches[3];
  $iverse = (int)$matches[4];
  dbgprint($dbg,"ls_callback_ap90_href. $code0, $mandala, $ihymn, $iverse\n");
  $rest = $matches[5];
  $hymnfilepfx = sprintf("%s%02d.%03d",$pfx,$imandala,$ihymn);
  $hymnfile = "$hymnfilepfx.html";
  $versesfx = sprintf("%02d",$iverse);
  $anchor = "$hymnfilepfx.$versesfx";
  $versesfx = sprintf("%02d",$iverse);
  $anchor = "$hymnfilepfx.$versesfx";
  $dir = sprintf("https://sanskrit-lexicon.github.io/%slinks/%shymns",$pfx,$pfx);
  $href = "$dir/$hymnfile#$anchor";
  return $href;
 } // end for rv, av
 if (in_array($pfx,array('p'))) {
  // I. 2. 3
  if(!preg_match('|^(.*?)[.] *([IV]+)[.] +([0-9]+)[.] +([0-9]+)(.*)$|',$data1,$matches)) {
    return $href;
   }
   $code0 = $matches[1];
   $roman = $matches[2];  // upper-case
   $romanlo = strtolower($roman);
   $ic = $this->roman_int($romanlo);
   $is = (int)$matches[3];
   $iv = (int)$matches[4];
   $dir = "https://ashtadhyayi.com/sutraani";
   $href = "$dir/$ic/$is/$iv";
   return $href;
 }
 return $href; 
}

 public function abbrv_callback($matches) {
 /* <ab n="{tran>}">{data}</ab>
  <ab{attrib}>{data)</ab>
  |<ab(.*?)>(.*?)</ab>|
 */
 $x = $matches[0]; // full string
 $a = $matches[1];
 $data = $matches[2];
 $dbg=false;
 dbgprint($dbg,"  abbrv_callback: \n  x=$x\n a=$a,\n   data=$data\n"); // 07-04-2024
 if(preg_match('/n="(.*?)"/',$a,$matches1)) {
  dbgprint($dbg," abbrv_callback case 1\n");
  $ans = $x; // local abbreviation
  // for pwk, prepare for displaying the tooltip without the abbreviation
  if (in_array($this->dict,array('pwg','pw','pwkvn'))) {
   $tip = $matches1[1];
   $style = "color:blue;";
   $tipa = "$tip";  // for debugging use "@$tip"
   $ans = "<span style='$style'>$tipa</span>";
  }
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
  $gra is called 'mandala' in rgveda_verse_callback
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
public function rgveda_verse_callback($matches0) {
/* 
    Adds 'gralink' element to xml. These need
    to be converted to html in basicdisplay.php
*/
 $dbg=false;
 $x0 = $matches0[0];
 $x1 = $matches0[1];
 if(! preg_match('|^([0-9]+)[ ,]+([0-9]+)(.*)$|',$x1,$matches)) {
  dbgprint($dbg,"rgveda_verse_callback: error. x1=$x1\n");
  return $x0;
 }
 $gra1 = $matches[1];  // mandala
 $gra2 = $matches[2];  // hymn
 $gra3 = $matches[3];  // rest of stuff before closing }
 dbgprint($dbg,"rgveda_verse_callback: gra1=$gra1, gra2=$gra2, gra3=$gra3\n");
 $modern = $this->rgveda_verse_modern((int)$gra1);
 # This version provides a link
 list($rvfile,$rvanchor) = $this->rgveda_link($modern,$gra2);
 # 2018-08-30  use github location
 $dir = "https://sanskrit-lexicon.github.io/rvlinks/rvhymns";
 $href = "$dir/$rvfile#$rvanchor";
 $modern1 = "$modern.$gra2";
 //$tooltip = "=$modern1 (mandala,hymn,verse)";
 $tooltip = "Rg Veda $modern1 (mandala,hymn,verse)";
 // 04-03-2021
 $x = "<gralink href='$href' n='$tooltip'>$gra1,$gra2$gra3</gralink>";
 return $x;
}
public function avveda_verse_callback($matches0) {
/* 
    Adds 'gralink' elements to xml. These need
    to be converted to html in basicdisplay.php
*/
 $dbg=false;
 $x0 = $matches0[0];
 $x1 = $matches0[1];
 if(! preg_match('|^AV[.] ([0-9]+),([0-9]+),([0-9]+)(.*)$|',$x1,$matches)) {
  dbgprint($dbg,"avveda_verse_callback: error. x1=$x1\n");
  return $x0;
 }
 $gra1 = $matches[1];  // mandala
 $gra2 = $matches[2];  // hymn
 $gra3 = $matches[3];  // verse
 $gra4 = $matches[4];  // rest of stuff before closing }
 dbgprint($dbg,"avveda_verse_callback: gra1=$gra1, gra2=$gra2, gra3=$gra3\n");

 $imandala = (int)$gra1;
 $ihymn = (int)$gra2;
 $hymnfilepfx = sprintf("av%02d.%03d",$imandala,$ihymn);
 $hymnfile = "$hymnfilepfx.html";
 $iverse = (int)$gra3;
 $versesfx = sprintf("%02d",$iverse);
 $anchor = "$hymnfilepfx.$versesfx";

 # 2018-08-30  use github location
 $dir = "https://sanskrit-lexicon.github.io/avlinks/avhymns";
 $href = "$dir/$hymnfile#$anchor";
 $tooltip = sprintf("Atharva Veda %02d.%03d.%02d",$imandala,$ihymn,$iverse);
 // 04-03-2021
 $x = "<gralink href='$href' n='$tooltip'>$x1</gralink>";
 return $x;
}

public function roman_int($roman) {
 $a = array("i" => 1,"ii" => 2,"iii" => 3,"iv" => 4,"v" => 5,"vi" => 6,"vii" => 7,"viii" => 8,"ix" => 9,"x" => 10,
"xi" => 11,"xii" => 12,"xiii" => 13,"xiv" => 14,"xv" => 15,"xvi" => 16,"xvii" => 17,"xviii" => 18,"xix" => 19,"xx" => 20 ); 
 try {
  if(isset($a[$roman])) {
   return $a[$roman];
  }else {
   return 0;
  }
 } catch (exception $e)  {
 return 0; // error
 }
 return 0;
}
public function lanman_link_callback($matches) {
/* 
    Adds 'lanlink' or 'wglink'  elements to xml. These need
    to be converted to html in basicdisplay.php
*/
 $x0 = $matches[0];
 $n0 = $matches[1]; # lan,16,4   or wg,1235
 $txt = $matches[2]; # text of <ls> tag}
 $parts = explode(",",$n0);
 if ($parts[0] == "lan") {
  $page = $parts[1];
  $linenum = $parts[2];
  $url = 'https://www.sanskrit-lexicon.uni-koeln.de/scans/csl-apidev/servepdf.php?dict=LAN'; #&page=111-a
  # This ampersand causes problems in basicdisplay parsing!
  #$href = "$url" . "&page=$page";
  $href = "$url" . "_page=$page";
  # It is useful to also have the line number visible in the url of the displayed url
  $href = "$href" . "_line=$linenum";
  $tooltip = "Lanman Sanskrit Reader, page $page, line $linenum";
  $x = "<lanlink href='$href' n='$tooltip' target='_lanlink'>$txt</lanlink>";
 }else if ($parts[0] == "wg") {
  // https://funderburkjim.github.io/WhitneyGrammar/step1/pages2c.html#section_1234
  $section = $parts[1];
  $url = 'https://funderburkjim.github.io/WhitneyGrammar/step1/pages2c.html';
  $href = "$url#section_$section";
  $tooltip = "Whitney Grammar, section $section";
  $x = "<lanlink href='$href' n='$tooltip' target='_wglink'>$txt</lanlink>";
 }else { // $n0 mal-formed
  $x = $x0; // return unchanged
 }
 return $x;
}
public function move_L_mw($line) {
 /* 04-12-2018. For MW. Logic to place Cologne record ID at END
  of displays for <H1X> records. This acomplished by changing the
  name of the <L> tag to <L1>
 */
 $dbg=false;
 $dict = $this->getParms->dict;
 dbgprint($dbg,"basicadjust.move_L_mw enter: dict=$dict, line=\n$line\n");
 //if (preg_match('|<(H[1-4].)>.*(<L>.*?</L>)|',$line,$matches)) {
 if (preg_match('|(<L>.*?</L>)|',$line,$matches)) {
  //$H = $matches[1];
  $Ltag = $matches[1];
  $revsup = "";
  if ($dict == "mw") { // 07-07-2024
   // Add markers for rev or sup Ⓡ, Ⓢ
   if (preg_match('|<info n="sup"/>|',$line,$matches1)) {
    $revsup = " sup";
   } else if (preg_match('|<info n="rev" pc="(.*?)"/>|',$line,$matches1)) {
    $pc = $matches1[1];
    $revsup = " rev ($pc)";
   } else {
    $revsup = "";
   }
  }
  //dbgprint(true,"basicadjust: revsup = '$revsup'\n");
  // remove L element
  $line = preg_replace("|$Ltag|","",$line);
  // construct L1 tag
  $L1tag = preg_replace("|L>|","L1>",$Ltag);
  // add in $revsup
  if ($revsup != "") {
   $L1tag = preg_replace("|</L1>|", "$revsup</L1>",$L1tag);
  }
  dbgprint(false,"basicadjust: Ltag=$Ltag,  revsup=$revsup, L1tag=$L1tag\n");
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
 public function infovn_markup($matches) {
  /* As of 5-2-2023, only present in 'gra' dictionary
   <info vn="X"/>
    [vn X]
  */
  $vn = $matches[1];
  $ans = "<span style='color:red;'>[vn $vn]</span>";
  return $ans;
 }
 public function infovn_markup_pw($matches) {
  /* <info n="sup_1"/> 
  */
  $vol = $matches[1];
  $ans = "<span style='color:red; font-size: smaller;'> [supplement volume $vol]</span>";
  return $ans;
 }
 
 public function chg_markup($matches) {
 /* <chg type="TYPE" n="CHGID" src="SRC">{chgdata}</chg>
   attrib:  ' type="TYPE" n="CHGID" src="SRC"
 */
 $dbg = false;
 $x = $matches[0]; // full <chg>Z</chg> string
 $type = $matches[1];
 $chgid = $matches[2];
 $src = $matches[3];
 $chgdata = $matches[4];
 dbgprint($dbg,"chg_markup: type=$type, chgid=$chgid, src=$src\n  chgdata=$chgdata\n");
 if ($type == 'chg') {
  // $anshead = "CHG type=$type, chgid=$chgid, src=$src";
  $anshead = '';
  if (preg_match('|<old>(.*?)</old> *<new>(.*?)</new>|',$chgdata,$matches1)) {
   $old = $matches1[1];
   $new = $matches1[2];
   $styleold = 'text-decoration:line-through;';
   $ansold = "<span style='$styleold'>$old</span>";
   $stylenew = 'color:green;';
   $msgstyle = "color:red; display:inline; text-decoration:underline red dotted;";
   $ansnew = "<abbr title='source=$src' style='$msgstyle'>[Correction: </abbr><span style='$stylenew'>$new</span><span style='color:red;'>]</span>";
   $ans = "$anshead : $ansold $ansnew";
   dbgprint($dbg,"ansold=$ansold\nansnew=$ansnew\n");
   return $ans;
  }else {
   return $x; // form not recognized
  }
 }else  if ($type == 'del') {
  // $anshead = "CHG type=$type, chgid=$chgid, src=$src";
  $anshead = '';
  if (preg_match('|<old>(.*?)</old>|',$chgdata,$matches1)) {
   $old = $matches1[1];
   $styleold = 'text-decoration:line-through;';

   $msgstyle = "color:red; display:inline;";
   $ansold = "<abbr title='source=$src' style='$msgstyle'>Deletion: </abbr><span style='$styleold'>$old</span><span style='color:red;'>]</span>";
   $ans = "$ansold";
   dbgprint($dbg,"Deletion: ansold=$ansold\n");
   return $ans;
  }else {
   return $x; // form not recognized
  }
 }else { // unknown type
  return $x; 
 }
 $dbg=false;
 }

public function slp_cmp($a,$b) {
// $a, $b are strings in SLP1 coding of Sanskrit. Return -1,0,1 according to
// whether $a<$b, $a==$b, or $a>$b
// order per PMS (Sep 25, 2012): L after q, | after Q
 $from = "aAiIuUfFxXeEoOMHkKgGNcCjJYwWqLQ|RtTdDnpPbBmyrlvSzsh";
 $to =   "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxy";

 $a1 = strtr($a,$from,$to);
 $b1 = strtr($b,$from,$to);
 return strcmp($a1,$b1);
}

public function kosha_meaning_callback($matches) {
 // OLD: <hwdetail>naga-pum</hw><meaning>sarpa,gaja,sirsa</meaning><hwdetail>
 // NEW: <div><b>naga-pum</b> meaning(s) sarpa, gaja, sirsa</div>
 $hw = $matches[1];
 $meanings = $matches[2];  // a,b,c
 $meanings1 = str_replace(',', ', ',$meanings);
 $ans = "<div><b>$hw</b> meaning(s) $meanings1</div>";
 return $ans;
}

public function kosha_syns_callback($matches) {
 // 10-25-2023  variation v5.1
 // OLD: <hwdetail><eid>A</eid><syns>B</syns></hwdetail>
 // NEW: 
 $eid = $matches[1]; 
 $synstr = $matches[2];
 if (! (preg_match('|^<s>(.*?)</s>$|',$synstr,$tempmatch))) {
  // should not occur
  $synstr1 = str_replace(',', ', ',$synstr);
  $ans = "<div> synset $eid:  $synstr1</div>";
  return $ans;
 }
 $key = $this->key;
 $synstr1 = $tempmatch[1];
 $items = preg_split('| *, *|',$synstr1);
 // -------------------------------------
 // $gensyns and $gens from $items
 $gensyns = array(); // associative array
 $gens = array();  // list of genders. 
 foreach($items as $item) {
  list($syn,$gen) = preg_split('|-|',$item);
  if (! array_key_exists($gen,$gensyns)) {
   array_push($gens,$gen);  // append $gen to list all
   $gensyns[$gen] = array(); // list of syns with this gen
  }
  array_push($gensyns[$gen],$syn);
 }
 // ------------------------------------
 // sort $gens by Sanskrit alphabetical order
 usort($gens,'BasicAdjust::slp_cmp');

 // ------------------------------------
 // $htmls
 $htmls = array();
 array_push($htmls,"<table style='border-collapse: collapse;'>");
 array_push($htmls,"<tr style='border-bottom: 1px solid #999;'><th>Gender</th><th>syns</th></tr>");
 foreach($gens as $gen) {
  $syns = $gensyns[$gen];
  // sort syns in Sanskrit alphabetical order
  usort($syns,'BasicAdjust::slp_cmp');
  $genname = $gen; // maybe later provide the name of the gender abbreviation
  array_push($htmls,"<tr style='border-bottom: 1px solid #999;'>");
  array_push($htmls,"<td><s>$genname</s></td>");
  // output the syns, with at most 5 per line
  $j = 0;
  $nsyns = count($syns);
  $i = 1;
  array_push($htmls,"<td>");
  foreach($syns as $syn) {
   if ($j == 5) {
    array_push($htmls,"<br/>");
    $j = 0;
   }
   $syn1 = "<s>$syn</s>";
   if ($syn == $key) {
    // emphasize the display for the user request
    $syn1 = "<span style='font-size:larger;'>$syn1</span>";
   }
   array_push($htmls,$syn1);
   if ($i != $nsyns) {
    array_push($htmls,", ");
   }
   $i = $i + 1;
   $j = $j + 1;
  }
  array_push($htmls,"</td>");
  array_push($htmls,"</tr>");
 }
 array_push($htmls,"</table>");
 
 $html = join(" ",$htmls);
 $ans = "synset $eid:$html";
 // dbgprint(true,"basicadjust syn callback html=\n  $html\n"); // xxx
 return $ans;
}

} // end of BasicAdjust class

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
   dbgprint(true,"line1=$line1\n");
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

}
?>
