<?php
// CLI harness: run ListhierClass against staged sqlite for a given key.
// usage: php tools/listhier_harness.php <key>
$key = isset($argv[1]) ? $argv[1] : 'gur';
$_GET = array('dict'=>'mw','key'=>$key,'filter'=>'deva','transLit'=>'deva','input'=>'slp1');
$_REQUEST = $_GET;
$_SERVER['REQUEST_METHOD'] = 'GET';
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
require_once(__DIR__ . '/../listhierClass.php');
try {
 $t = new ListhierClass();
 echo $t->table1;
} catch (Throwable $e) {
 echo "THROW: " . $e->getMessage() . PHP_EOL;
 exit(2);
}
