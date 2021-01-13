<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
?>
<?php
require_once('dbgprint.php');
require_once('parm.php');
require_once('dictinfo.php');

class ServepdfClass {
 public $html;
 public $json;
 public $getParms, $dictinfo;
 public $request;
 public function __construct() {
  $getParms = new Parm();
  $dictinfo = new DictInfo($getParms->dict);

  if (isset($_REQUEST['api'])) {
   $this->apicall($getParms,$dictinfo);
   return;
  } else {
   $this->html_construct($getParms,$dictinfo);
  }
 }
 public function get_pdffiles_filename($dictinfo) {
  $webparent = $dictinfo->webparent;
  return "$webparent/web/webtc/pdffiles.txt";
 }
 public function get_pageinfos($getParms,$dictinfo) {
  /*  Return array of pageinfo objects
   Each pageinfo object has keys:
    'status'
    'page' - 
    'key'  :  may be empty string   
    'lnum' :  may be empty string
  Also, set request
  */
  $this->init_request($getParms,$dictinfo);

  $this->init_request($getParms,$dictinfo);
  $page = $this->request['page'];
  $key = $this->request['key'];
  #list($page,$key) = $getParms->servepdfParms();  
  
  if ($page != '') {
   // no key and lnum
   $pageinfo = array('page'=>$page, 'key' => '', 'lnum' => '');
   $pdffiles_filename = $this->get_pdffiles_filename($dictinfo);
   $lines = file($pdffiles_filename);
   $dictupper = $dictinfo->dictupper;
   $imageFiles = $this->getImagefiles($lines,$page,$dictupper);
   list($status,$filename,$pageprev,$pagenext)=$imageFiles;
   if (!$status) {
    return array(); // empty array
   }else {
    return array($pageinfo);
   }
  }
  if (($page == '') && ($key != '')) {
   // Try to get $page from 'key' parm 
   $pageinfos = $this->get_pageinfos_from_key($getParms);
   $this->request = array('dict' => $getParms->dict,
    'page'=>'',
    'key' => $getParms->keyin1, 
    'keyslp1' => $getParms->key,
    'input' => $getParms->filterin
    );

   return $pageinfos;
  }
 }
 public function html_construct_error($errormsg) { 
  #$errormsg = "<div>Servepdf error. No dictionary mentioned? </div>";
  $html = <<<EOF
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8" />
<title>$dictupper Cologne Scan</title>
<link rel='stylesheet' type='text/css' href='css/serveimg.css' />
</head>
<body>
 $errormsg
</body>
</html>
EOF;

  $this->html = $html;
 }
 public function html_construct($getParms,$dictinfo) {
  $dbg=False;
  # addional paramaters
  $dict = $getParms->dict;
  $dictupper = $dictinfo->dictupper;
  if ($getParms->status != 200) {
   if ($dictinfo->dictstatus != 200) {
    $errmsg = "servepdf ERROR: " . $dictinfo->dicterr;
   }else {
    $errmsg = "servepdf ERROR: "  . "Unknown error";
   }
   $this->html_construct_error($errmsg);
   return;  
  }
  $pageinfos = $this->get_pageinfos($getParms,$dictinfo);
  if (count($pageinfos) > 0) {
   $pageinfo = $pageinfos[0];
   $page = $pageinfo['page'];
  }else {
   $page = '0000';  # a string, but not a valid page.
  }

  $pdffiles_filename = $this->get_pdffiles_filename($dictinfo);
  $lines = file($pdffiles_filename);
  $dictupper = $dictinfo->dictupper;
  $imageFiles = $this->getImagefiles($lines,$page,$dictupper);
  list($status,$filename,$pageprev,$pagenext)=$imageFiles;

  $pdfpages_url = $dictinfo->get_pdfpages_url();
  dbgprint($dbg,"servepdf: pdfpages_url=$pdfpages_url\n");
  $pdf = "$pdfpages_url/$filename";

  $imageParms = array(
   'WIL' => "width ='1000' height='1500'",
   'PW'  => "width ='1600' height='2300'",
   'CCS' => "width ='1400' height='2000'",
   'MD'  => "width ='1000' height='1370'",
  );
  $imageParm = $imageParms[$dictinfo->dictupper];
  if ($imageParm) {
   $imageElt = "<img src='$pdf' $imageParm />";
  } else {
   $android = " <a href='$pdf' style='position:relative; left:100px;'>Click to load pdf</a>" ;
   $imageElt = "<object id='servepdf' type='application/pdf' data='$pdf'" . 
              "style='width: 98%; height:98%'>" . $android . "</object>" ;
  }
  // Use PHP 'heredoc' syntax to generate html
  $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8" />
<title>$dictupper Cologne Scan</title>
<link rel='stylesheet' type='text/css' href='css/serveimg.css' />
</head>
<body>
$imageElt

<div id='pagenav'>
<a href="servepdf.php?dict=$dict&page=$pageprev" 
   class='nppage'><span class='nppage1'>&lt;</span>&nbsp;</a>
<a href="servepdf.php?dict=$dict&page=$pagenext" 
   class='nppage'><span class='nppage1'>&gt;</span>&nbsp;</a>
</div>
</body>
</html>
HTML;

  $this->html = $html;
 }
 public function get_pageinfos_from_key($getParms) {
  /* This could be written to use $temp->xmlmatches;
    But then we would have to parse the xml for '<pc>' element
  */
  require_once('getword_data.php');
  #$page = '0000'; # default. a string but not a valid page 
  $dict = $getParms->dict;
  $temp = new Getword_data(); // Uses key
  $recs = $temp->matches;
  $dbg = false;
  dbgprint($dbg,count($recs). "  records for $key.\n");
  $pageinfos = array();
  foreach($recs as $dbrec) {
   $key1 = $dbrec[0];
   $lnum = $dbrec[1];
   $rec= $dbrec[2];  // html
   if (preg_match('|<info>(.*?)</info><body>(.*?)</body>|',$rec,$matchrec)) {
    $info = $matchrec[1];
    if($dict == 'mw') {
     list($pginfo,$hcode,$key2,$hom) = preg_split('/:/',$info);
    }else {
     $pginfo = $info;
    }
    $lnums = preg_split('/[,]/',$pginfo);  
    if(count($lnums)>0) {
     $page = $lnums[0];
    }
    $pageinfo = array('page' => $page, 'key' => $key1, 'lnum' => $lnum);
    // There can be duplicate pages.
    $pageinfos[] = $pageinfo;
   }
  }
  return $pageinfos;
 }

 public function getImagefiles($lines,$pagestr_in0,$dictupper) { 
  /* $lines are the lines of the pdffiles file for the current dictionary.
  Function returns a 4-element array 
   status: boolean regarding whether there was a 'real match
   Image filename corresponding to $pagestr_in0 (current page)
   page before current page (analogous to $pagestr_in0)
   page after current page. 
  */
  // Next line for MW, where pagestr_in0 may start with 'Page', which we remove
  $pagestr_in0 = preg_replace('|^[^0-9]+|','',$pagestr_in0);
  /* Recognize two basic cases: vol-page or page.
   The pdffiles cases are usually one of the two
   For these, we remove characters (such as column designations) 
   that may be present if pagestr_in0 comes from the <pc> elt of the dictionary
   as when the 'key' input GET parameter.
  */
  if (preg_match('|^([1-9]-[0-9]+)|',$pagestr_in0,$matches)) {
   $pagestr_in = $matches[1];
  }elseif (preg_match('|^([0-9]+)|',$pagestr_in0,$matches)) {
   $pagestr_in = $matches[1];
  }else {
   // not sure if this case ever obtains
   $pagestr_in = $pagestr_in0;
  }

  $pagestr_in = preg_replace('/^0+/','',$pagestr_in);
  $pagearr=array(); //sequential
  $pagehash=array(); // hash
  $n=0;
  foreach($lines as $line) {
   $line = trim($line);  // 08-21-2018 Removes end of line chars, and white spc
   list($pagestr,$pagefile,$pagetitle) = preg_split('|:|',$line);
   # pagetitle currently unused, and may be absent, eg. in Wilson
   $n++;
   //$pagehash[$pagestr]=$n;
   $pagestr_trim = preg_replace('/^0+/','',$pagestr);
   $pagehash[$pagestr_trim]=$n;
   $pagearr[$n]=array($pagestr,$pagefile);
  }
  $ncur = $pagehash[$pagestr_in];
  if (!$ncur) {
   $pagenum = intval($pagestr_in); // result is 0 if not a string of digits
   if (($pagenum % 2) == 1) {
    $pagenum = $pagenum - 1;
   }
   $pagestr = "$pagenum";
   $ncur = $pagehash[$pagestr];
  }
  
  if ((!$ncur) && ($dictupper == 'PWG')) {
   $lnum = $pagestr_in;
   list($vol,$page) =  preg_split('/[,-]/',$lnum);
   $pagestr=$lnum;
   $ipage = intval($page);
   if (($ipage % 2) == 0) {
    $ipage = $ipage - 1;
    $pagestr = sprintf('%s-%04d',$vol,$ipage);
    $ncur = $pagehash[$pagestr]; 
   }
  }
  if ((!$ncur) && ($dictupper == 'GRA')) {
   $page= $pagestr_in;
   $pagestr=$page;
   $ipage = intval($page);
   if (($ipage % 2) == 0) {
    $ipage = $ipage - 1;
    $pagestr = sprintf('%d',$ipage);
    $ncur = $pagehash[$pagestr]; 
   }
  }
 
  if(!$ncur) {
   $ncur=1;
   $status = false;
  }else {
   $status = true;
  }
  
  list($pagestrcur,$filecur) = $pagearr[$ncur];
  $nnext = $ncur + 1;
  if ($nnext > $n) {$nnext = 1;}
  $nprev = $ncur - 1;
  if ($nprev < 1) {$nprev = $n;}
  list($pagenext,$dummy) = $pagearr[$nnext];
  list($pageprev,$dummy) = $pagearr[$nprev];
  return array($status,$filecur,$pageprev,$pagenext);
 }
 public function apicall($getParms,$dictinfo) {
  $ans = array();
  $this->init_request($getParms,$dictinfo);
  $page = $this->request['page'];
  $key = $this->request['key'];
  $ans['request'] = $this->request;
  $ans['errorinfo'] = '';  
  if ($dictinfo->dictstatus != 200) {
   $ans['status'] = 404;
   $ans['errorinfo'] = "servepdf ERROR: " . $dictinfo->dicterr;
   $ans['links']=[];
   $this->json = json_encode($ans);
   return;  // go no further
  }
  $pageinfos = $this->get_pageinfos($getParms,$dictinfo);
  $pdffiles_filename = $this->get_pdffiles_filename($dictinfo);
  $lines = file($pdffiles_filename);
  $imageFileTriples = [];
  $dictupper = $dictinfo->dictupper;
  foreach($pageinfos as $pageinfo) {
   $page = $pageinfo['page'];
   $imageFileTriples[] = $this->getImagefiles($lines,$page,$dictupper);
  }
  $pdfpages_url = $dictinfo->get_pdfpages_url();
  // Construct final form
  if (count($pageinfos) == 0) {
   $ans['status'] = 404;  # HTML status code 'not found'
   $ans['errorinfo'] = "servepdf ERROR: " . "No pages found";
   $ans['links'] = []; # empty array
   $this->json = json_encode($ans);
   return;  // go no further
  } 
  $ans['status'] = 200;  # HTML status code 'success'
  $links = array();
  for($i=0;$i<count($pageinfos);$i++) {
   $link = array();
   $pageinfo = $pageinfos[$i];
   $page = $pageinfo['page'];
   $key1 =  $pageinfo['key'];
   $lnum = $pageinfo['lnum'];
   list($flag,$filename,$prev,$next) = $imageFileTriples[$i];
   $link['page'] = $page;
   $link['key1'] = $key1;
   $link['lnum'] = $lnum;
   $url = "$pdfpages_url/$filename";
   $link['pageurl'] = $url;
   $link['prevpage'] = $prev;
   $link['nextpage'] = $next;
   $links[] = $link;
  
  $ans['links'] = $links;
  }
  # convert to json
  $this->json = json_encode($ans);
 }
 public function init_request($getParms,$dictinfo) {
  list($page,$key) = $getParms->servepdfParms();  
  if ($page) {
   $this->request = array('dict' => $getParms->dict,
    'page'=>$page, 
    'key' => '', 
    'keyslp1' => '',
    'input' => $getParms->filterin );
  } else { //if ((!$page)&&$key){
   $this->request = array('dict' => $getParms->dict,
    'page'=>'',
    'key' => $getParms->keyin1, 
    'keyslp1' => $getParms->key,
    'input' => $getParms->filterin
    );
  }
 }
}
?>
