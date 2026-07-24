<?php
function getword_simpleslp1($word) {
/* Assumes two functions are available in calling environment:
 get_parent_dirpfx  and dbgprint
 Also assumes 'python3'  is available in the shell.
 dbgprint outputs will go in directory where dbgprint is defined.
*/
 $dbg = false;
 dbgprint($dbg, "entering query3.php\n");
 $cwd = getcwd(); // v1.1a
 dbgprint($dbg,"getword_simpleslp1.php: cur dir = $cwd\n");
 // H1523: reject shell metacharacters before shell_exec. Word is SLP1-ish
 // Sanskrit from simple-search; keep alnum + common SLP1 accent/mark chars.
 if (!is_string($word) || !preg_match('/^[A-Za-z0-9_^~\\\\\/\'.-]{1,80}$/', $word)) {
  dbgprint($dbg,"getword_simpleslp1: rejected unsafe word\n");
  return '{"status": 400, "result": []}';
 }
 try {
  $dirpfx = get_parent_dirpfx("simple-search");
  $query3 = $dirpfx . "simple-search/simpleslp/query3.py";
  // escapeshellarg on path + word: prior form interpolated $word raw into
  // the shell command (command-injection if search input contained ;|& etc.).
  $cmd = "python3 " . escapeshellarg($query3) . " " . escapeshellarg($word);
  dbgprint($dbg,"calling shell exec: $cmd\n");
  $out = shell_exec($cmd);
  if ($out == null) {
   $out = '{"status": 404, "result": []}';
  }
  dbgprint($dbg,"back from shell exec. out=$out\n");
 }catch(Exception $e) {
  // default json string indicating error
  $out = '{"status": 404, "result": []}';
  dbgprint($dbg,"error from simpleslp/query3.php\n");
  return $out;
 }
 return $out;
}
?>
