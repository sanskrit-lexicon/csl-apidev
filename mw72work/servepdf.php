<?php
// servepdf.php: 
// Cologne server

$page = $_GET['page'] ;
if (!$page) {$page = $argv[1];}
list($filename,$pageprev,$pagenext)=getfiles($page);

$HEADER='<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
$HEADER .= 
  '<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">';
$HEADER .= '<head>';
$HEADER .= '<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">' . "\n";
$HEADER .= '<title>Cologne Scan</title>';
 
$HEADER .= "<link rel='stylesheet' type='text/css' href=\"serveimg.css\" />";
$HEADER .= "</head><body>\n";
echo $HEADER ;
 $dir="../pdfpages"; // location of pdf files
 $pdf = "$dir/$filename";
 //$pdf = "$pdf&#zoom=200"; // try this for pwg.
 $style = "width: 98%; height:98%";
 $elt = "<object id='servepdf' type='application/pdf' data='$pdf' style='$style'> " .

  // " <a href='$pdf'>Click to load pdf</a>" .
  "</object>";
 echo $elt;

echo "<div id='pagenav'>\n";
genDisplayFile("&lt;",$pageprev);
genDisplayFile("&gt;",$pagenext);
echo "</div>\n";
echo "</body></html>\n";
exit;
function getfiles($pagestr_in) {
 $filename="pdffiles.txt";
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
