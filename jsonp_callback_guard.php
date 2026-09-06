<?php
// jsonp_callback_guard.php
//
// Shared JSONP callback validation + reply, consolidated from the identical
// inline blocks that H1523 / PR #52 added to 17 endpoints (getword,
// getsuggest, servepdf, listhier, dalglob, getword_xml, api_trial,
// pwkvn/01-03, simple-search v1.0d/v1.0/v1.1/v1.1a, api1 salt_*).
//
// Contract (unchanged from the inline idiom):
//  - Only a safe JS identifier passes: ^[A-Za-z_$][A-Za-z0-9_$.]{0,127}$
//  - Anything else -> 400 text/plain "invalid callback"
//  - The whitelisted charset makes htmlentities() a no-op; it stays as
//    defence-in-depth and clears the Semgrep echoed-request taint sink.
if (!function_exists('jsonp_callback_ok')) {
 function jsonp_callback_ok($callback) {
  return is_string($callback) &&
   preg_match('/^[A-Za-z_$][A-Za-z0-9_$.]{0,127}$/', $callback) === 1;
 }
}
if (!function_exists('jsonp_reply')) {
 // $json must be an ALREADY-ENCODED JSON string (json_encode the payload at
 // the call site, exactly as the inline blocks did). Returns true when the
 // JSONP body was echoed, false when the callback was rejected (400 sent).
 function jsonp_reply($callback, $json, $jsContentType = false) {
  if (!jsonp_callback_ok($callback)) {
   header('content-type: text/plain; charset=utf-8');
   http_response_code(400);
   echo "invalid callback";
   return false;
  }
  if ($jsContentType) {
   header('content-type: application/javascript; charset=utf-8');
  }
  echo htmlentities($callback) . "($json)";
  return true;
 }
}
?>
