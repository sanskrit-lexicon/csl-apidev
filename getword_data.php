<?php
error_reporting(E_ALL & ~E_NOTICE );
?>
<?php 
/* getword_data.php
 class to get the html data for getword.php
*/
require_once('dbgprint.php');
require_once('dal.php');
require_once('basicadjust.php');
require_once('basicdisplay.php');

class Getword_data {
 /* $matches contains array of records. 
   Each record is an array with three elements:
   - key
   - lnum (cologne id)
   - computed html string
 */
 public $matches, $basicOption;
 public $xmlmatches;
 public $dict; // H6478: for ogmeta_html()
 //public $basicdisplaydbg;
 public function __construct($basicOption = true) {
 $dbg=false;
 $getParms = new Parm();
 $this->basicOption = $basicOption;
 $dict = $getParms->dict;
 $this->dict = $dict; // H6478: for ogmeta_html()
 $dal = new Dal($dict);
 $key = $getParms->key;
  // xmlmatches is array of records rec, where
  // rec is array with 3 items:
  //  0: key0  the headword of the record (usu. but not always same as key)
  //  1: lnum0 The Cologne id
  //  2: data:  xml string from xxx.xml
 $xmlmatches = $dal->get1_mwalt($key); 
 $this->xmlmatches = $xmlmatches;
 $dal->close();

  $xmldata = [];
  for ($i=0;$i<count($xmlmatches);$i++) {
   $xmlmatch = $xmlmatches[$i];
   list($key0,$lnum0,$xmldata0) = $xmlmatch;
   $xmldata[] = $xmldata0;
  }
  
  $adjxml = new BasicAdjust($getParms,$xmldata);
  $adjmatches = $adjxml->adjxmlrecs;
  $htmlmatches = [];
  //$htmlbasics = []; // for debugging
  for($i=0;$i<count($xmlmatches);$i++) {
   $xmlmatch = $xmlmatches[$i];
   list($key0,$lnum0,$xmldata0) = $xmlmatch;
   $adjxmldata0 = $adjmatches[$i];
   $html = $this->getword_data_html_adapter($key0,$lnum0,$adjxmldata0,$dict,$getParms,$xmldata0);
   dbgprint(false,"getword_data:  html=\n  $html\n");
   //$htmlbasics[] = $this->basicdisplaydbg;
   $html1 = $html;
   // dbgprint(false,"getword_data: i = $i, html=\n$html\n\n");
   // 10-23-2023 For koshas, use $L instead of $lnum0.
   // 07-09-2024 For koshas, use $L1
   // Also,use L1 for mw (to get sup,rev)
    if (in_array($dict,array('mw','abch', 'acph', 'acsj', 'nmmb'))) {
    if(preg_match('|<L1>(.*?)</L1>|',$html,$tempmatch)) {
     $lnum0 = $tempmatch[1];
     // remove L1 element from html1
     $html1 = preg_replace('|<L1>(.*?)</L1>|','',$html);
    }
   }
   $htmlmatches[] = array($key0,$lnum0,$html1);    
  }
 if ($dbg) {
  dbgprint($dbg,"getword_data returns:\n");
  for($i=0;$i<count($htmlmatches);$i++) {
    dbgprint($dbg,"xmldata[$i]=\n  {$xmldata[$i]}\n\n");
    dbgprint($dbg,"adjmatches[$i]=\n  {$adjmatches[$i]}\n\n");
    // dbgprint($dbg,"htmlbasics[$i]=\n  {$htmlbasics[$i]}\n\n");
    dbgprint($dbg,"htmlmatches[$i][0]= {$htmlmatches[$i][0]}\n"); 
    dbgprint($dbg,"htmlmatches[$i][1]= {$htmlmatches[$i][1]}\n"); 
    dbgprint($dbg,"htmlmatches[$i][2]=\n {$htmlmatches[$i][2]}\n"); 
   }
   
  }
 $this->matches = $htmlmatches;
}
/* ------------------------------
 Open Graph meta for entry pages (H6478, 11-10-2026).
 Builds the canonical og: + twitter:card meta set (PLAYBOOK_OG_PREVIEW_
 CARDS_2026-10-11) from the FIRST matched record -- same entry fields the
 kosha word cards use:
  og:title       = headword Devanagari + IAST (English dicts: headword only)
  og:description = first sense line (display body, tags stripped, whitespace
                   collapsed, 160-char cap -- kosha app/word_page.py _plain)
  og:image       = <image_base>/og/<token>.png -- the kosha og card service;
                   <token> is the filesystem-safe SLP1 encoding (kosha
                   card_token twin: [a-z0-9] verbatim, every other UTF-8
                   byte as _<hex>)
  og:url         = <page_url_base>?dict=<dict>&key=<key>&input=slp1
 Host-independence (kosha R1 pattern): both bases are caller-supplied, no
 host is hardcoded here; both bases empty -> '' (no og: block, pages stay
 byte-identical for callers that pass nothing). og: meta is decoration:
 any internal failure returns '' rather than breaking the entry display.
*/
public function ogmeta_html($page_url_base='',$image_base='') {
 try {
  if (($page_url_base == '') && ($image_base == '')) {return '';}
  $nmatches = count($this->matches);
  if ($nmatches == 0) {return '';}
  list($key0,$lnum0,$html0) = $this->matches[0];
  $key0 = (string)$key0;
  $dict = $this->dict;
  if (in_array($dict,array('ae','mwe','bor'))) {
   // English headwords: not SLP1, no transcoding
   $title = $key0;
  }else {
   if (!function_exists('transcoder_processString')) {return '';}
   $deva = transcoder_processString($key0,'slp1','deva');
   $iast = transcoder_processString($key0,'slp1','roman'); // 'roman' = IAST
   $title = "$deva $iast";
  }
  // description: first sense line from the first record's display body
  $desc = $title; // fallback -- never an empty og:description
  if (preg_match('|<body>(.*?)</body>|s',$html0,$m)) {
   $text = html_entity_decode(strip_tags($m[1]),ENT_QUOTES,'UTF-8');
   $text = trim(preg_replace('|\s+|',' ',$text));
   if (preg_match('|^(.{160})|u',$text,$m2)) {
    // kosha _plain cap: 160 chars + ellipsis (UTF-8-safe)
    $text = rtrim($m2[1]) . '…';
   }
   $text = trim($text);
   if ($text != '') {$desc = $text;}
  }
  $meta = "<meta property=\"og:type\" content=\"article\">\n";
  $meta .= "<meta property=\"og:site_name\" content=\"Cologne Sanskrit Lexicon\">\n";
  $meta .= "<meta property=\"og:title\" content=\"" .
   htmlspecialchars($title,ENT_QUOTES,'UTF-8') . "\">\n";
  $meta .= "<meta property=\"og:description\" content=\"" .
   htmlspecialchars($desc,ENT_QUOTES,'UTF-8') . "\">\n";
  if ($page_url_base != '') {
   $url = $page_url_base . '?dict=' . urlencode($dict) .
    '&key=' . urlencode($key0) . '&input=slp1';
   $meta .= "<meta property=\"og:url\" content=\"" .
    htmlspecialchars($url,ENT_QUOTES,'UTF-8') . "\">\n";
  }
  if ($image_base != '') {
   $token = preg_replace_callback('|[^a-z0-9]|',
    function($mm) {return sprintf('_%02x',ord($mm[0]));},$key0);
   $img = rtrim($image_base,'/') . '/og/' . $token . '.png';
   $meta .= "<meta property=\"og:image\" content=\"" .
    htmlspecialchars($img,ENT_QUOTES,'UTF-8') . "\">\n";
   $meta .= "<meta property=\"og:image:width\" content=\"1200\">\n";
   $meta .= "<meta property=\"og:image:height\" content=\"630\">\n";
   // Telegram renders the large preview only when twitter:card rides next
   // to og:image (PLAYBOOK_OG_PREVIEW_CARDS_2026-10-11 section 1)
   $meta .= "<meta name=\"twitter:card\" content=\"summary_large_image\">\n";
   $meta .= "<meta name=\"twitter:image\" content=\"" .
    htmlspecialchars($img,ENT_QUOTES,'UTF-8') . "\">\n";
  }
  return $meta;
 }catch (Throwable $e) {
  // og: meta must never break the entry display
  return '';
 }
}
/* ------------------------------
  getword_data_html_adapter and related functions
*/
public function getword_data_html_adapter($key,$lnum,$adjxml,$dict,$getParms,$xmldata)
{
 // 08-07-2020.  This is the only place where  BasicDisplay is called.
 // We don't need to have arrays of strings, but only one string
 // BasicDisplay is written to allow a string for the second argument.

 $filter = $getParms->filter;
 dbgprint(false,"getword_data_html_adapter, adjxml=\n  $adjxml\n");
 $display = new BasicDisplay($key,array($adjxml),$filter,$dict);
 // H3636 A12: BasicDisplay->status=false means the record's XML failed to
 // parse and the display silently degraded to stripped text. Surface the
 // failure to the caller (error envelope) instead of swallowing it.
 if ($display->status === false) {
  throw new RuntimeException("BasicDisplay: XML parse failure for key $key (dict $dict)");
 }
 $row1 = $display->row1;
 $row1x = $display->row1x; 
 $row = $display->row;
 $info = $row1;
 if ($row1x == '') { // True except for some mw verbs
  $body = "$row";
 } else {
  $body = "$row1x<br>$row";
 }
 $dbg=false;
 dbgprint($dbg,"adapter\n");
 dbgprint($dbg,"info = $info\n");
 dbgprint($dbg,"body = $body\n");
 // $this->basicdisplaydbg = $body;
  if (in_array($dict,['abch', 'acph', 'acsj', 'nmmb'])) {
  // no adjust body
 }else {
  // adjust body
  $body = preg_replace('|<td.*?>|','',$body);
  $body = preg_replace('|</td></tr>|','',$body);
  if ($dict == 'mw') {
   // in case of MW, we remove [ID=...]</span>
   $body = preg_replace('|<span class=\'lnum\'.*?\[ID=.*?\]</span>|','',$body);
  }
 }
 // adjust $info - keep only the dislayed page
  if ($dict == 'mw') {
   if(!preg_match('|>([^<]*?)</a>,(.*?)\]|',$info,$matches)) {
    dbgprint(false,"html ERROR 2: \n" . $info . "\n");
    // H3636 A10: fail loud with an envelope instead of a blank HTTP 200.
    throw new RuntimeException("getword_data: malformed MW page-info: $info");
   }
   $page=$matches[1];
   $col = $matches[2];
   $pageref = "$page,$col";
  }else {
   if(!preg_match('|>([^<]*?)</a>|',$info,$matches)) {
    dbgprint(false,"html ERROR 2: \n" . $info . "\n");
    // H3636 A10: fail loud with an envelope instead of a blank HTTP 200.
    throw new RuntimeException("getword_data: malformed page-info: $info");
   }
   $pageref=$matches[1];
  }
 if ($dict == 'mw') {
  // 08-17-2024. $hui 
  list($hcode,$key2,$hom,$hui) = $this->adjust_info_mw($xmldata); 
  // construct return value as colon-separated values
  
  if ($this->basicOption) {
   //dbgprint(false,"getword_data: changing hom to blank; $key2,$hom\n");
   $hom="";
  }
  $infoval = "$pageref:$hcode:$key2:$hom:$hui";
  $ans = "<info>$infoval</info><body>$body</body>";
 }else {
  # construct return value
  $ans = "<info>$pageref</info><body>$body</body>";
 }
 return $ans;
}

public function adjust_info_mw($data) {
 # In case of MW, also retrieve Hcode and hom from head of $data
 $hom='';
 if (preg_match('|</key2><hom>(.*?)</hom>|',$data,$matches)) {
  $hom = $matches[1];
 }
 $hcode='';
 if (preg_match('|^<(H.*?)>|',$data,$matches)) { // always matches
  $hcode=$matches[1];
 }
 $key2='';
 if (preg_match('|<key2>(.*?)</key2>|',$data,$matches)) {
  $key2 = $matches[1];
 }
 // 08-17-2024
 $hui = '';
 if (preg_match('|<info hui="(.*?)"/>|',$data,$matches)) {
  $hui = $matches[1];
 }

 $key2a = $this->adjust_key2_mw($key2);
 return array($hcode,$key2a,$hom,$hui);
}
public function adjust_key2_mw($key2) {
 $ans = preg_replace('|--+|','-',$key2);  // only 1 dash
 $ans = preg_replace('|<sr1?/>|','~',$ans); # ~ not in key1 for MW (?)
 $ans = preg_replace('|<srs1?/>|','@',$ans); # @ not in SLP1
 // Leave some xml in place:
 // <root>kf</root>
 // <root/>daMh
 // dA<hom>1</hom>
 // <shortlong/>
 $ans1 = preg_replace('|</?root/?>|','',$ans);
 $ans1 = preg_replace('|</?hom>|','',$ans1);
 $ans1 = preg_replace('|<shortlong/>|','',$ans1);
  if (preg_match('|<|',$ans1)) {
   //dbgprint(false,"adjust_key2: $ans1\n");
   // H3636 A10: fail loud with an envelope instead of a blank HTTP 200.
   throw new RuntimeException("adjust_key2_mw: leftover markup in key2: $ans1");
  }
  return $ans;
}
}
?>
