<?php
require_once(__DIR__ . '/security_headers.php');
error_reporting(E_ALL & ~E_NOTICE );
?>
<?php
/* servepdf.php  Apr 27, 2015 Multidictionary display of scanned images
  Similar to servepdf for the dictionaries
Parameters:
 dict: one of the dictionary codes (case insensitive)
 page: a specific page of the dictionary.  In the form of the contents
       of a <pc> element
 key: a headword, in SLP1.  
  Only one of 'page' and 'key' should be used.  If both are present, then
  'key' parameter is ignored and 'page' parameter prevails.
*/
if (isset($_GET['callback'])) {
 header('content-type: application/json; charset=utf-8');
}
header("Access-Control-Allow-Origin: *");
require_once('servepdfClass.php');

function servepdfCall() {
  try {
   $temp = new ServepdfClass();
  } catch (Throwable $e) {
   // H3636 A10: data anomalies now throw; serve a truthful 500 page.
   http_response_code(500);
   header('content-type: text/html; charset=utf-8');
   echo "<p>servepdf error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES) . "</p>";
   return;
  }
  $table1 = $temp->html;
  // H3636 A8: page-not-found / dict-error degraded to the error page;
  // surface it as a truthful HTTP status instead of 200.
  if (isset($temp->status) && $temp->status != 200) {
   http_response_code($temp->status);
  }
  if (isset($_GET['callback'])) {
   $json = json_encode($table1);
   $callback = $_GET['callback'];
   // Only allow a safe JSONP callback identifier. Echoing the raw callback
   // is a reflected-XSS / JSONP-injection vector, so reject anything else.
   if (!preg_match('/^[A-Za-z_$][A-Za-z0-9_$.]{0,127}$/',$callback)) {
    header('content-type: text/plain; charset=utf-8');
    http_response_code(400);
    echo "invalid callback";
    return;
   }
   echo htmlentities($callback) . "($json)";
  }else {
   echo $table1;
  }
}
servepdfCall();
?>

