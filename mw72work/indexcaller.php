<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "//www.w3.org/TR/html4/loose.dtd">
<html>
 <head>
    <META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=iso-8859-1">
   <title>Monier-Williams, 1872</title>
    <link rel="stylesheet" href="main.css" type="text/css">
  <script type="text/javascript" src="../js/jquery.min.js"></script>
  <script type="text/javascript" src="../js/jquery.cookie.js"></script>

  <script type="text/javascript" src="main_webtc.js"> </script>
 </head>
 <body>
 <table width="100%">
  <tbody>
   <tr>
    <td>
    <table width="100%"> 
     <tr><td>
      <a href="//www.sanskrit-lexicon.uni-koeln.de/">
      <img id="unilogo" src="../images/cologne_univ_seal.gif"
           alt="University of Cologne" width="60" height="60" 
           title="Cologne Sanskrit Lexicon"/>
      </a>
      </td>
      <td>
      <table>
	<tr><td>
        <span style='font-size:normal'>Monier-Williams 1872 Dictionary</span>
      </td></tr>
	<tr><td>
      (2014 edition)
      </td></tr>
      </table>
     </td>
     </tr>
    </table>
    </td>
   </tr>
  </tbody>
 </table>
<?php init_inputs(); ?>
  <table width="100%" cellpadding="5">
   <tr>
   <td>citation:&nbsp;
<?php
global $inithash;
 $init=$inithash['word'];
 echo '<input type="text" name="key" size="20" id="key" ';
 echo "value=\"$init\" />\n";
?>
   </td>
   <td>input:&nbsp;
    <select name="transLit" id="transLit">
<?php
global $inithash;
 $init=$inithash['translit'];
 output_option("hk","Kyoto-Harvard",$init);
 output_option("slp1","SLP1",$init);
 output_option("itrans","ITRANS",$init);
?>
    </select>
   </td>
  </tr>

  <tr>
   <td>
 <input type="button" onclick="getWord();" value="Search" id="searchbtn" />

   </td>
   <td>output:
    <select name="filter" id="filter">
<?php
global $inithash;
$init = $inithash['filter'];
output_option("deva","Devanagari Unicode",$init);
 output_option("hk","Kyoto-Harvard",$init);
 output_option("slp1","SLP1",$init);
 output_option("itrans","ITRANS",$init);
 output_option("roman","Roman Unicode",$init);
?>
    </select>

   </td>
   <td>
   <table><tr>
   <td>
   <a href="/php/correction_form.php?dict=MW72" target="Corrections">Corrections</a>


  </td>

   <td>
     <a href="download.html" target="output"><b>Downloads</b></a>
   </td>
   <td><a href="help.html" target="_top">Help</td>
   </td>
   </tr></table>
  </tr>
 </tbody>
</table>
 <div id="disp" class="disp">
 </div>
   <input name="input" id="input_input" value="hk" style="visibility:hidden" /> 
   <input name="output" id="input_output" value="deva" style="visibility:hidden" />
 <?php 
 // set invisible 'indexcaller' 
 $x = $_GET['translit'];
 if (!$x) {$x = $_GET['input'];}
 $y = $_GET['filter'];
 if (!$y) {$y = $_GET['output'];}
 if ($x ||$y) {
  $val="YES";
 }else {
  $val="NO";
 }
 $id = "indexcaller";
 echo "<input name=\"$id\"  id=\"$id\" value=\"$val\"  style=\"visibility:hidden\" />";
 ?>
<script type="text/javascript" src="/js/piwik_analytics.js"></script>

</body>
</html>
<?php 
function init_inputs() {
// from GET parameters, initialize $inithash
global $inithash;
$inithash=array();
 // word = citation
 $x = $_GET['word'];
 if (!$x) {$x = $_GET['citation'];}
 if (!$x) {$x = $_GET['key'];}
 if (!$x) {$x = "";}
 $inithash['word'] = $x;

 // translit = input
 $x = $_GET['translit'];
 if (!$x) {$x = $_GET['input'];}
 if (!$x) {$x = "";}
 $translit0 = $x;
 // filter = output
 $x = $_GET['filter'];
 if (!$x) {$x = $_GET['output'];}
 if (!$x) {$x = "";}
 $filter0=$x;

 // normalization of translit and filter.
 // translit0 may have substrings HK,SLP,IT which are converted
 // to translit = hk,slp1,itrans
 // filter0 may have substring HK,SLP2,IT,DEVA,ROMAN, which are converted
 // to filter = hk,slp1,itrans,deva,roman
 $x = strtoupper($translit0);
 if (preg_match('/SL/',$x)) {
  $x="slp1";
 }else if (preg_match('/IT/',$x)) {
  $x="itrans";
 }else {
  $x="hk";
 }
 $translit = $x;
 // normalization of filter, using old parameters
 // slp1 is default
 $x = strtoupper($filter0);
 if (preg_match('/SL/',$x)) {
  $x="slp1";
 }else if (preg_match('/IT/',$x)) {
  $x="itrans";
 }else if (preg_match('/DEVA/',$x)) {
  $x="deva";
 }else if (preg_match('/HK/',$x)) {
  $x="hk";
 }else {
  $x="roman";
 }
 $filter = $x;

 // initializing $inithash
 $inithash['translit'] = $translit;
 $inithash['filter'] = $filter;

}

 function output_option ($value,$display,$initvalue) {
  echo "  <option value='$value'";
  if ($initvalue == $value) {
   echo " selected='selected'";
  }
  echo ">$display</option>\n";
}

?>
