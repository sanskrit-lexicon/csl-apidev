<?php
 require_once("listhierClass.php");
 function listhierCall() {
  $temp_listhier = new ListhierClass();
  $temp_table1 = $temp_listhier->table1;
  echo($temp_table1);
 }
 listhierCall();
?> 
