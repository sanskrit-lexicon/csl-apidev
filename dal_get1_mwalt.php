<?php
require_once("dal.php"); 
require_once("dispitem.php");
require_once("dbgprint.php");
function dal_get1_mwalt($dal,$key) {
$dict="mw";
#$dbg=true;
#dbgprint($dbg,"dal_get1_mwalt: call dal->get1($key)\n");
$recs = $dal->get1($key);
$nrecs = count($recs);
#dbgprint($dbg,"dal_get1_mwalt: return dal->get1($key)  nrecs=$nrecs\n");
//echo  "step0: $nrecs records for key=$key\n";
$newrecs=array();
// Step 0: parse recs into DispItem array
$dispitems=array();
for($i=0;$i<$nrecs;$i++) {
 $rec = $recs[$i];
 $dispitem = new DispItem($dict,$rec);
 $dispitem->rec=$rec;  // add extra attribute
 $dispitem->newrec=False; // add extra attribute
 $dispitems[]=$dispitem;
}
// Step 1: fill in forward gaps in $recs
$newitems=array();
for($i=0;$i<$nrecs-1;$i++) {
 #dbgprint($dbg,"dal_get1_mwalt: irec = $i\n");

 $dispitem0 = $dispitems[$i];
 $dispitem1 = $dispitems[$i+1];
 $newitems[] = $dispitem0;
 // find records between $lnum and $lnum1 that 'belong'
 $more=True; // actually not used.  Loop ends with 'break'
 while($more) {
  $temprecs = $dal->get4b($dispitem0->lnum,1);
  if (count($temprecs) != 1) { // should not happen
   //echo "ERROR 1\n";
   break;
  }
  $rec = $temprecs[0];
  $dispitem = new DispItem($dict,$rec);
  $dispitem->rec = $rec;
  if ($dispitem->lnum == $dispitem1->lnum) {
   break;
  }
  if (strlen($dispitem->hcode)!= 3) {
   //echo "BREAK 2: {$dispitem->key} , {$dispitem->hcode}\n";
   break;
  }
  if (substr($dispitem0->hcode,0,2) == substr($dispitem->hcode,0,2)) {
   // we have an extra one!
   $dispitem->newrec = True;
   $newitems[] = $dispitem;
   // reset dispitem0 for next iteration of while(more)
   $dispitem0 = $dispitem;
   continue;
  }
  //echo "BREAK 3: {$dispitem->key} , {$dispitem->hcode}\n";
  break;
 }
}
// Add the last record of $dispItems
$newitems[]= $dispitems[$nrecs-1];
 #dbgprint($dbg,"dal_get1_mwalt: #2\n");

// Add any records after last record of $dispItems
 $dispitem0 = $dispitems[$nrecs-1];
 $more=True; // actually not used.  Loop ends with 'break'
 while($more) {
  $temprecs = $dal->get4b($dispitem0->lnum,1);
  if (count($temprecs) != 1) { // should not happen
   //echo "ERROR 1\n";
   break;
  }
  $rec = $temprecs[0];
  $dispitem = new DispItem($dict,$rec);
  $dispitem->rec = $rec;
/*
  if ($dispitem->lnum == $dispitem1->lnum) {
   break;
  }
*/
  if (strlen($dispitem->hcode)!= 3) {
   //echo "BREAK 2: {$dispitem->key} , {$dispitem->hcode}\n";
   break;
  }
  if (substr($dispitem0->hcode,0,2) == substr($dispitem->hcode,0,2)) {
   // we have an extra one!
   $dispitem->newrec = True;
   $newitems[] = $dispitem;
   // reset dispitem0 for next iteration of while(more)
   $dispitem0 = $dispitem;
   continue;
  }
  //echo "BREAK 3: {$dispitem->key} , {$dispitem->hcode}\n";
  break;
 }
 #dbgprint($dbg,"dal_get1_mwalt: #3\n");
// Reset $dispitems as newitems
$dispitems = $newitems;
$nitems = count($dispitems);
/*
// Do similar as above, but backwards
   2017-07-24.  This has a problem with L=99930.1  (DarmeRa). Somehow,
   the number appears as L=99930.1000000001.
   This is something to do with PHP and sqlite3, not sure exactly what.
   But the upshot is that there is an infinite loop kind of situation.
   The final solution a change in dal.php,  get4a and get4b
*/
//echo "after step 1, nitems=$nitems\n";
$newitems=array();
 #dbgprint($dbg,"dal_get1_mwalt: #3a nitems=$nitems\n");
for($i=$nitems-1;$i>0;$i--) {
 $dispitem0 = $dispitems[$i];
 $dispitem1 = $dispitems[$i-1];
 #dbgprint($dbg,"dal_get1_mwalt: #3b dispitem1->lnum=" .$dispitem1->lnum . "\n");
 $newitems[] = $dispitem0;
 // find records between $lnum and $lnum1 that 'belong'
 $more=True; // actually not used.  Loop ends with 'break'
 /*
 $ntries = 0;
 $mtries = 1000;
 $lnum00 = $dispitem0->lnum;
 */
 while($more) {
  /*
  $ntries = $ntries + 1;
  if ($ntries > $mtries) {
   dbgprint($dbg,"dal_get1_mwalt: #3c: quitting after ntries=$ntries\n");
   break;
  }
  dbgprint($dbg,"dal_get1_mwalt: #3c: lnum=".$dispitem0->lnum . "\n");
  */
  $temprecs = $dal->get4a($dispitem0->lnum,1);
  if (count($temprecs) != 1) { // should not happen
   //echo "ERROR 1\n";
   break;
  }
  $rec = $temprecs[0];
  $dispitem = new DispItem($dict,$rec);
  $dispitem->rec = $rec;
  if ($dispitem->lnum == $dispitem1->lnum) {
   //echo "Break 1 at case A {$dispitem->lnum} {$dispitem->key}\n";
   break;
  }
  if (strlen($dispitem0->hcode)!= 3) {
   //echo "BREAK 2: {$dispitem0->key} , {$dispitem0->hcode}\n";
   break;
  }
  if (substr($dispitem0->hcode,0,2) == substr($dispitem->hcode,0,2)) {
   // we have an extra one!
   $dispitem->newrec = True;
   $newitems[] = $dispitem;
   // reset dispitem0 for next iteration of while(more)
   if ($dispitem0->lnum == $dispitem->lnum) {
    break;  // 2017-07-24
   }
   $dispitem0 = $dispitem;
   continue;
  }
  //echo "BREAK 3: {$dispitem->key} , {$dispitem->hcode}\n";
  break;
 }
}
 #dbgprint($dbg,"dal_get1_mwalt: #4\n");
// Add the first record of $dispitems
$newitems[]= $dispitems[0];
// Get ones occurring Before first record of $dispitems
 $more=True; // actually not used.  Loop ends with 'break'
 $dispitem0=$dispitems[0];
 while($more) {
  #dbgprint($dbg,"dal_get1_mwalt: #4a lnum=" . $dispitem0->lnum . "\n");
  $temprecs = $dal->get4a($dispitem0->lnum,1);
  #dbgprint($dbg,"dal_get1_mwalt: #4b lnum=" . $dispitem0->lnum . "\n");
  if (count($temprecs) != 1) { // should not happen
   //echo "ERROR 1\n";
   break;
  }
  $rec = $temprecs[0];
  $dispitem = new DispItem($dict,$rec);
  $dispitem->rec = $rec;

  if (strlen($dispitem0->hcode)!= 3) {
   //echo "BREAK 2: {$dispitem0->key} , {$dispitem0->hcode}\n";
   break;
  }
  if (substr($dispitem0->hcode,0,2) == substr($dispitem->hcode,0,2)) {
   // we have an extra one!
   $dispitem->newrec = True;
   $newitems[] = $dispitem;
   // reset dispitem0 for next iteration of while(more)
   $dispitem0 = $dispitem;
   continue;
  }
  //echo "BREAK 3: {$dispitem->key} , {$dispitem->hcode}\n";
  break;
 }
 #dbgprint($dbg,"dal_get1_mwalt: #5\n");

// newitems is 'backwards' lnum order. Get it back in forward lnum order
$nitems = count($newitems);
$newitems1=$newitems;
$newitems=array();
for($i=$nitems-1;$i>=0;$i--) {
 $newitems[]=$newitems1[$i];
}
 $ans=array();
 foreach($newitems as $item) {
  $ans[]=$item->rec;
 }
#dbgprint($dbg,"dal_get1_mwalt: #6 returns\n");
 return $ans;
}
?>
