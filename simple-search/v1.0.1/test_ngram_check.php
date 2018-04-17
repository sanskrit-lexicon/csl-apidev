<?php
require_once('ngram_check.php');
$ngram_check = new Ngram_Check(2,"ngram_2_mw.txt");
$word = $argv[1];
$ngrams = $ngram_check->generate($word);
foreach($ngrams as $ngram) {
 echo "$ngram\n";
}
$ok = $ngram_check->validate($word);
if ($ok == false) {
 echo "$word NOT validated\n";
}else {
 echo "validate:  $word -> $ok\n";
}


?>
