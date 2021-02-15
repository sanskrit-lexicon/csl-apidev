<?php
 $_REQUEST['input']='simple';
 $_REQUEST['output']='slp1';
 $_REQUEST['accent']='no';
 $_REQUEST['dict']=$argv[2];
 $_REQUEST['key']=$argv[1];
 $_REQUEST['input_simple'] = 'default';
//require_once('getword_list_1.0.php');
require_once('getword_list_1.0_main.php');
$ans = getword_list_processone(); // Gets arguments from $_REQUEST
print_r($ans);
$json = json_encode($ans);
echo $json . "\n";
?>
