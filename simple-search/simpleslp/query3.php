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
 try {
  $dirpfx = get_parent_dirpfx("simple-search");
  $query3 = $dirpfx . "simple-search/simpleslp/query3.py";
  $cmd = "python3 $query3 $word";
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
