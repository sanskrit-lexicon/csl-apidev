<?php
require_once('../../dbgprint.php');
class Ngram_Check {
 public $ngramdict;
 public $ngramlen;  // 2-grams
 public function __construct($len,$filein) {
  $this->ngramlen = $len;
  $lines = file($filein,FILE_IGNORE_NEW_LINES);
  //echo count($lines)," from $filein\n";
  $ans = array();
  foreach($lines as $line) {
   list($key,$val) = explode(':',$line);
   $ans[$key] = $val; // $val is frequency of occurrence
  }
  $this->ngramdict = $ans;
 }
 public function generate($word) {
  // generate ngrams of length ngramlen for word
  $ans = [];
  $n = strlen($word);
  $l = $this->ngramlen;
  for($i=0;$i<=($n-$l);$i++) {
   $ans[] = substr($word,$i,$l);
  }
  return $ans;
 }
 public function validate($word) {
  // are all the ngrams of word found in ngramdict?
  $ngrams = $this->generate($word);
  $ans = true;
  foreach($ngrams as $ngram) {
   if (! isset($this->ngramdict[$ngram])) {
    $ans = false;
    break;
   }
  }
  $dbg=false;
  dbgprint($dbg,"validate: $word -> $ans\n");
  return $ans; 
 }
 public function validate_beg($word) {
  // is the FIRST of word found
  $ngrams = $this->generate($word);
  $ans = true;
  foreach($ngrams as $ngram) {
   if (! isset($this->ngramdict[$ngram])) {
    $ans = false;
   }
   break;  // always break after the first
   
  }
/*
  $dbg=false;
  if (substr($word,0,1) == 'F') {
   $dbg = true;
   dbgprint($dbg,"Ngram.validate_beg: $word -> $ans\n");
  }
*/
  return $ans; 
 }
}

?>
