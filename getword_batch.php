<?php
require_once(__DIR__ . '/security_headers.php');
// Exclude WARNING messages also, to solve Peter Scharf Mac version.
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
?>
<?php
/* getword_batch.php
   Additive batch endpoint (doc/roadmap_lookup.md D2/Wave1, closes the
   429-throttling driver: dalglob1.php fires one getword.php fetch per
   homonym key in parallel). Parameters: dict, keys (comma-separated),
   input, output, accent. Returns a JSON array of {key,status,html},
   built by looping the existing GetwordClass once per key -- so
   getword.php / getwordClass.php stay byte-identical for current
   consumers.
*/
header("Access-Control-Allow-Origin: *");
header('content-type: application/json; charset=utf-8');
require_once('getwordClass.php');

function getword_batch_call() {
 // A comma-separated 'keys' list is this endpoint's only new surface vs
 // getword.php, so cap it defensively -- callers needing more should
 // issue another batch, not overload a single request.
 $max_keys = 200;
 $keysParam = (isset($_REQUEST['keys']) && is_string($_REQUEST['keys'])) ? $_REQUEST['keys'] : '';
 $keys = array_filter(array_map('trim', explode(',', $keysParam)), function($k) {
  return $k !== '';
 });
 $keys = array_slice(array_values($keys), 0, $max_keys);

 // GetwordClass reads its single key from $_REQUEST['key'] (via Parm());
 // loop it here, restoring the original request afterwards.
 $savedKey = isset($_REQUEST['key']) ? $_REQUEST['key'] : null;
 $savedDispcss = isset($_REQUEST['dispcss']) ? $_REQUEST['dispcss'] : null;
 // Intent: suppress the per-fragment <link href='css/basic.css'> that
 // getwordDisplay() emits, since the lookup page links css/basic.css
 // once itself. NOTE (verified live 03-07-2026): this currently has NO
 // effect -- GetwordClass::getwordDisplay() unconditionally overwrites
 // $linkcss based on $this->basicOption right after checking dispcss,
 // and Parm() always sets basicOption=false, so the <link> always comes
 // through regardless of this parameter. Left set for forward
 // compatibility if that dead branch is ever fixed; the operative fix
 // today is the client-side path rewrite in lookup.js (same technique
 // sample/dalglob1.php already uses for the same reason).
 $_REQUEST['dispcss'] = 'no';

 $results = array();
 foreach ($keys as $key) {
  $_REQUEST['key'] = $key;
  $temp = new GetwordClass();
  $results[] = array(
   'key' => $key,
   'status' => $temp->status ? 200 : 404,
   'html' => $temp->table1,
  );
 }

 if ($savedKey === null) { unset($_REQUEST['key']); } else { $_REQUEST['key'] = $savedKey; }
 if ($savedDispcss === null) { unset($_REQUEST['dispcss']); } else { $_REQUEST['dispcss'] = $savedDispcss; }

 echo json_encode($results);
}
getword_batch_call();
?>
