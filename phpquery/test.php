<?php
require_once("phpQuery-onefile.php");
print "hello\n";
$xml = "<x><p>Hello</p></x>";
$doc = phpQuery::newDocument($xml);
echo  pq('x') . "\n";
echo  pq('p')->attr('class','good') . "\n";
echo pq('p')->addclass('bad') . "\n";
echo pq('p')->html('<q>this is q</q>')  . "\n";
$newdoc = pq('p')->HTML('<q>this is q</q>');
if (is_string($newdoc)) { // Not a string
 $flag = "true";
}else {
 $flag = "false";
 echo var_dump($newdoc);
}
print "newdoc is a string? " . $flag . "\n";
print strlen($newdoc) . "\n";
$newdoc1 = $newdoc->serialize();  // A string
if (is_string($newdoc1)) {
 $flag = "true";
}else {
 $flag = "false";
}
print "newdoc1 is a string? " . $flag . "\n";
print strlen($newdoc) . "\n";
print strlen($newdoc1) . "\n";
?>
