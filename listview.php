<?php
if (isset($_GET['callback'])) {
 header('content-type: application/json; charset=utf-8');
 header("Access-Control-Allow-Origin: *");
}
?>

<!DOCTYPE html>
<html>
 <head>
  <META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">
  <title>Monier-Williams Dictionary</title>
  <link rel="stylesheet" type="text/css" href="http://www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/css/listview.css" />
  <script src="js/jquery.min.js"></script>
  <script src="js/listview.js"> </script>
 </head>
 <body>
<?php 
/* Set cookies so JS can read them when listhier clicks on things
  Technical note: From http://php.net/manual/en/features.cookies.php,
  "Cookies are part of the HTTP header, so setcookie() must be called before any output is sent to the browser."
  This is why this cookie setting code appears before the rest of the
  display generation.
  Aug 17, 2015. Remove 'options' from cookienames
*/
 $cookienames = array('dict','accent','input','output');
 foreach($cookienames as $name) {
  setcookie($name,$_GET[$name]);
 }

?>
<div id="CologneListview">
<div class="dispdiv">
 <?php include "disphier.php";?>
</div>
<div class="dispgutter"></div>
<div class="dispdivlist" class="displist">
 <?php include "listhier.php";?>
</div>
</div>

<script src="http://www.sanskrit-lexicon.uni-koeln.de/js/piwik_analytics.js"></script> 

</body>
</html>


