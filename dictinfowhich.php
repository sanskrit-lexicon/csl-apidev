<?php
 /* dictinfowhich is used so we know how to construct
    paths to various files.  There are assumed to be two
    file structures: one like that at Cologne sanskrit-lexicon-uni-koeln.de
    web site, and another simpler structure based on that devised for 
    use on other servers, such as xampp or a typical Linux php setup.
*/
 $dir = dirname(__DIR__);
 if (preg_match('/afs/',$dir)) {
  $dictinfowhich = "cologne"; 
 }else {
  $dictinfowhich = "xampp";
 }
?>
