<?php
function get_parent_dirpfx($base) {
 $dirpfx = "../../"; // apidev  Not portable
 #$ds = DIRECTORY_SEPARATOR;
 //echo("get_parent_dirpfx: __FILE__ = " . __FILE__ . "\n");
 for($i=1;$i<10;$i++) {
  $d = dirname(__FILE__,$i);
  $b = basename($d);
  if ($b == $base) {
   $d = dirname(__FILE__,$i+1);
   $dirpfx = "$d/";
   break;
  }
 }
 return $dirpfx;
}
?>
