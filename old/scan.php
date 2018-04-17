<?php
/* scanPage.php  Multidictionary display of scanned images
  Similar to servepdf for the dictionaries
Parameters:
 dict: one of the dictionary codes (case insensitive)
 page: a specific page of the dictionary.  In the form of the contents
       of a <pc> element
*/
$dict = $_GET['dict'];
$page = $_GET['page'];
require_once('dictinfo.php');
$dictinfo = new DictInfo($dict);
$year = $dictinfo->get_year();
$webpath = $dictinfo->get_webPath();
/* Header information **/
?>
<html>
<head>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">
<title><?= $dictinfo->dictupper?> Cologne Scan</title>
<link rel='stylesheet' type='text/css' href='css/serveimg.css' />
</head>
<body>
<?php
$dbg=False;
if ($dbg) { 
 echo "dict=$dict, year=$year<br/>";
 echo "webpath={$webpath}<br/>";
 echo "scanpath =".DictInfo::$scanpath."<br/>";
}
list($filename,$pageprev,$pagenext)=getfiles($page);

$pdfdir = "$webpath/pdfpages"; // location of pdf files
?>
</body>
</html>
<?php
function getfiles($pagestr_in) {
 $dir = "$webpath/webtc"
 $filename="$dir/pdffiles.txt";
 $lines = file($filename);
 $pagearr=array(); //sequential
 $pagehash=array(); // hash
 $n=0;
 foreach($lines as $line) {
  list($pagestr,$pagefile,$pagetitle) = preg_split('|:|',$line);
  # pagetitle currently unused
  $n++;
  $pagehash[$pagestr]=$n;
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
 if(!$ncur) {
  $ncur=1;
 }
 list($pagestrcur,$filecur) = $pagearr[$ncur];
 $nnext = $ncur + 1;
 if ($nnext > $n) {$nnext = 1;}
 $nprev = $ncur - 1;
 if ($nprev < 1) {$nprev = $n;}
 //echo "nprev,ncur,nnext = $nprev,$ncur,$nnext\n";
 list($pagenext,$dummy) = $pagearr[$nnext];
 list($pageprev,$dummy) = $pagearr[$nprev];
 return array($filecur,$pageprev,$pagenext);
}
function genDisplayFile($text,$file) {
    $server = "servepdf.php"; // relative web address of this program
    $href = $server . "?page=$file";
    $a = "<a href='$href' class='nppage'><span class='nppage1'>$text</span>&nbsp;</a>";
   echo "$a\n";
}

?>
