<?php
function accent_adjust($line,$accent,$dict) {
 list($key,$lnum,$data) = $line;
 if ($dict == 'MW') {
  return accent_adjust_MW($line,$accent,$dict);
 }
 if($accent == 'yes') {
  $new = preg_replace_callback("|<span class='sdata'><SA>(.*?)</SA></span>|",
         "accent_yes",$data);
 }else {
  $new = preg_replace_callback("|<span class='sdata'><SA>(.*?)</SA></span>|",
         "accent_no",$data);
  //echo "<p>accent_no ends</p>";
  //$newx = preg_replace("|<|","&lt;",$new);
  //$newx = preg_replace("|>|","&gt;",$newx);
  //echo "<p>$newx</p>";
 }
 return array($key,$lnum,$new);
}
function accent_adjust_MW($line,$accent) {
 // Also must adjust key2, 
 list($key,$lnum,$data) = $line;
  if (!preg_match('|<info>(.*?)</info><body>(.*?)</body>|',$data,$matchrec)) {
  return $line; // cannot proceed. Unexpected
 }
 $info = $matchrec[1];
 $html = $matchrec[2];

 if($accent == 'yes') {
  $newhtml = preg_replace_callback("|<span class='sdata'><SA>(.*?)</SA></span>|",
         "accent_yes",$html);
  $newinfo = $info;
 }else {
  $newhtml = preg_replace_callback("|<span class='sdata'><SA>(.*?)</SA></span>|",
         "accent_no",$html);
  list($pginfo,$hcode,$key2,$hom) = preg_split('/:/',$info);
  // A little more subtle. (Sep 3, 2015) Example:
  // key2 = vi-<root>vf<hom>1</hom></root>:
  // Only remove accents after a vowel.  Note this is still an approximation.
  // which works to NOT remove '/' in </hom> and </root>
  #$newkey2 = preg_replace('|[\/\^\\\]|','',$key2);
  $newkey2 =  preg_replace('|([aAiIuUfFxXeEoO])[\/\^\\\]|','\1',$key2);
  $newinfo = join(':',array($pginfo,$hcode,$newkey2,$hom));
 }
 $new = "<info>$newinfo</info><body>$newhtml</body>";
 return array($key,$lnum,$new);
}
function accent_yes($matches) {
 $old = $matches[0];
 $new = preg_replace("|class='sdata'|","class='sdata_siddhanta'",$old);
 return $new;
}
function accent_no($matches) {
 $olddata = $matches[1];
 $newdata = preg_replace('|[\/\^\\\]|','',$olddata);
 $new = "<span class='sdata'><SA>$newdata</SA></span>";
 return $new;
}
?>
