<?php
/* June 12, 2015getCommon.php */
/* Jul 10, 2015 - revised to use Parm class */
require_once('parm.php');
require_once('dal.php');
$getParms = new Parm();

$dictinfo = $getParms->dictinfo;
$webpath = $dictinfo->get_webPath();
$dict = $getParms->dict;
$dal = new Dal($dict);
$dictup = $dictinfo->dictupper;

$english = $getParms->english;
$keyin1 = $getParms->keyin1;
$keyin = $getParms->keyin;
$key = $getParms->key;

?>
