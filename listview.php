<?php
error_reporting( error_reporting() & ~E_NOTICE );
 header("Access-Control-Allow-Origin: *");
/* removed 08-15-2019.
if (isset($_GET['callback'])) {
 header('content-type: application/json; charset=utf-8');
}
*/
?>
<?php 
/* Set cookies so JS can read them when listhier clicks on things
  Technical note: From //php.net/manual/en/features.cookies.php,
  "Cookies are part of the HTTP header, so setcookie() must be called before any output is sent to the browser."
  This is why this cookie setting code appears before the rest of the
  display generation.
  Aug 18, 2018. add error reporting to remove warnings; Peter's MacBookPro shows warning messages
    for the calls to setcookie. Hopefully this will suppress those warning messages.
*/
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
 $cookienames = array('dict','accent','input','output');
 foreach($cookienames as $name) {
  setcookie($name,$_GET[$name]);
 }

?>

<!DOCTYPE html>
<html>
 <head>
  <META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">
  <title>Cologne apidev/Listview </title>
  <link rel="stylesheet" type="text/css" href="css/listview.css" />

  <script src="js/jquery.min.js"></script>
  <script src="js/listview.js"> </script>
 </head>
 <body>
<div id="CologneListview">
<div class="dispdiv">
 <?php require_once("getword.php");?>
</div>
<div class="dispgutter"></div>
<div class="dispdivlist" class="displist">
 <?php require_once("listhier.php");
 ?>
</div>
</div>

<?php 
 $piwik = 
'<script src="//www.sanskrit-lexicon.uni-koeln.de/js/piwik_analytics.js"></script>';
 include('dictinfowhich.php');
 if ($dictinfowhich == "cologne") {
  echo "$piwik\n";
 }
?>
 <script type="text/javascript" src="js/orphus.customized.js"></script>

<script type="text/javascript">
  $(window).load(function() {
  var correctionsUrl = 'https://www.sanskrit-lexicon.uni-koeln.de/php/correction_form_response.php';
  <?php 
   require_once("parm.php");
   $getParms = new Parm();
   $dict = $getParms->dict;
   $key = $getParms->key;
  ?>
  var key = <?php echo "'$key'";?>;
  var dict = <?php echo "'$dict'";?>;
  //console.log('listview.php script: key=',key,'dict=',dict);
  orphus.init({
    correctionsUrl: correctionsUrl,
    params: {
      entry_hw: key,
      entry_new: '',
      entry_old: '',
      entry_email: '',
      entry_L: '',
      entry_dict: dict,
      entry_comment: '',
    }
  });
 });
</script>

</body>
</html>


