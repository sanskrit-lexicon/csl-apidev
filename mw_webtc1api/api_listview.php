<!DOCTYPE html>
<html>
 <head>
  <META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">
  <title>Monier-Williams Dictionary</title>
  <link rel="stylesheet" type="text/css" href="api_listview.css" />
  <script src="../js/jquery.min.js"></script>
  <script src="api_listview.js"> </script>
 </head>
 <body>
<?php 
/* Set cookies so JS can read them when listhier clicks on things
  Technical note: From http://php.net/manual/en/features.cookies.php,
  "Cookies are part of the HTTP header, so setcookie() must be called before any output is sent to the browser."
  This is why this cookie setting code appears before the rest of the
  display generation.
*/
 $cookienames = ['phoneticInput','serverOptions','viewAs','accent',
 'filter','transLit'];
 foreach($cookienames as $name) {
  setcookie($name,$_GET[$name]);
 }

?>
<div id="disp">
 <?php include "disphier.php";?>
</div>
<div id="dispgutter"></div>
<div id="displist" class="displist">
 <?php include "listhier.php";?>
</div>
<script src="/js/piwik_analytics.js"></script> 
</body>
</html>


