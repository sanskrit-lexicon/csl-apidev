<!DOCTYPE html>
<!-- MW is handled separately -->
<html>
 <head>
  <META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">
  <title>Monier-Williams Dictionary</title>
  <link rel="stylesheet" type="text/css" href="css/listview.css" />
  <script src="js/jquery.min.js"></script>
<?php
 $dict = $_GET['dict'];
 $dictup = strtoupper($dict);
 if ($dictup == 'MW') {
?>
  <script src="js/listviewmw.js"> </script>
<?php
 }else {
?>
  <script src="js/listview.js"> </script>
<?php
 }
?>
 </head>
 <body>
<?php 
/* Set cookies so JS can read them when listhier clicks on things
  Technical note: From //php.net/manual/en/features.cookies.php,
  "Cookies are part of the HTTP header, so setcookie() must be called before any output is sent to the browser."
  This is why this cookie setting code appears before the rest of the
  display generation.
*/
 $cookienames = array('dict','accent','filter','transLit','options');
 foreach($cookienames as $name) {
  setcookie($name,$_GET[$name]);
 }

?>
<?php
 $dict = $_GET['dict'];
 $dictup = strtoupper($dict);
 if ($dictup == 'MW') {
?>
<div id="disp">
 <?php include "disphier.php"; /*"disphiermw.php";*/?>
</div>
<div id="dispgutter"></div>
<div id="displist" class="displist">
 <?php include "listhiermw.php";?>
</div>
<?php 
 }else {
?>
<div id="disp">
 <?php include "disphier.php";?>
</div>
<div id="dispgutter"></div>
<div id="displist" class="displist">
 <?php include "listhier.php";?>
</div>
<?php
 }
?>
<script src="/js/piwik_analytics.js"></script> 
</body>
</html>


