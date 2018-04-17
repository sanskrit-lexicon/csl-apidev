<?php
/* 06-02-2017
*/
require_once('../../dbgprint.php');
class Simple_Search{
 public $transitionTable = [
	["a","A"],
	["i","I"],
	["u","U"],
	["r","R","RR"],
	["l","lR","lRR"],
	["h","H"],
	["M","n","N","J","G"], 
	["z","S","s"],
	["b","v"],
	["k","kh"],
	["g","gh"],
	["c","ch"],
	["j","jh"],
	["T","Th","t","th"],
	["D","Dh","d","dh"],
	["p","ph"],
	["b","bh"],
	["sh","z"]
];
 public $keysin,$keys;
 public $searchList;
 public $dbg;
 public function __construct($keyslp,$keyin) {
  $this->dbg = true;
  dbgprint($this->dbg,"Length of transitionTable=" . count($this->transitionTable) . "\n");
  $this->searchList = array();
  $this->doVariant("",$keyin);
  $this->keysin = $this->searchList;
  $this->keys = $this->searchList;  // temporary
 }
 public function doVariant($pref,$word) {
  dbgprint($this->dbg,"doVariant: '$pref', '$word'\n");
  if (strlen($pref) == 0) {
   $this->searchList[] = $word;
  }
  if (strlen($word) == 0) {
   return; // done
  }
  $varChar = $this->getChar($word);
  dbgprint($this->dbg,"doVariant: varChar=$varChar\n");
  if (strlen($word) > 1) {
   $this->doVariant($pref . substr($word,0,1),substr($word,1));
  }
  foreach($this->transitionTable as $variants) {
   $cases = array();
   $isExist = false;
   foreach($variants as $variant) {
    if ($variant == $varChar) {
     $isExist = true;
    } else {
      $cases[] = $variant;
    }
   }
   if ($isExist == true) {
    foreach($cases as $newChar) {
     $subWord = substr($word,strlen($varChar));
     $this->searchList[] = $pref . $newChar . $subWord;
     if (strlen($word) > 1) {
      $this->doVariant($pref . $newChar,$subWord);
     }
    }
   }
  }
 }
 public function getChar($word) {
  $newChar = "";
  foreach($this->transitionTable as $variants) {
   foreach($variants as $variant) {
    $ln = strlen($variant);
    if (substr($word,0,$ln) == $variant) {
     if ($ln > strlen($newChar)) {
      $newChar = $variant;
     }
    }
   }
  }
  return $newChar;
 }
}
?>
