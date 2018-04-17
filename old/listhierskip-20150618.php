<?php
// listhierskip.php  ejf  Apr 14, 2013
// May 23, 2013. Refactored to use 'type' attribute of 'see' element.
// June 2015 - removed this condition.  Now, listhierskip_data 
// always returns false
function listhierskip_data($data) {
 return false;
/*
 // Return Boolean. True if <see type="nonhier"/> is part of $data
 if (preg_match('|<see type="nonhier"/>|',$data)) {
  return True;
 }else {
  return False;
 }
*/
}
?>
