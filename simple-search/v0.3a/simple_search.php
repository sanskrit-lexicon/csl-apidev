<?php
/* 06-05-2017   
*/
$dirpfx = "../../";
require_once($dirpfx . "utilities/transcoder.php"); // initializes transcoder
require_once($dirpfx . "dbgprint.php");
require_once('ngram_check.php');

class Simple_Search{

 public $transitionTable = [ // slp1
   ["a","A"],
   ["i","I"],
   ["u","U"],
   ["r","f","F","ri","ar","ru"],
   ["l","x","X","lri",],
   ["h","H"],
   ["M","n","R","Y","N","m"],
   ["S","z","s","zh","sh"],
   ["b","v"],
   ["k","K"],
   ["g","G"],
   ["c","C","Ch"],
   ["j","J"],
   ["w","W","t","T"],
   ["q","Q","d","D"],
   ["p","P","f"],
   ["b","B","v"],
];
 public $keysin,$keys;
 public $searchdict;
 public $dbg;
 public $ngram2_check;
 public $ngram2beg_check;  // beginning 2-gram
 public $ngram3_check;
 public $ngram3beg_check;  // beginning 2-gram
 public function __construct($keyin0) {
  $this->ngram2_check = new Ngram_Check(2,"ngram_2_mw.txt");
  $this->ngram2beg_check = new Ngram_Check(2,"ngram_2_beg_mw.txt");
  $this->ngram3_check = new Ngram_Check(3,"ngram_3_mw.txt");
  $this->ngram3beg_check = new Ngram_Check(3,"ngram_3_beg_mw.txt");

  $this->dbg = false;
  dbgprint($this->dbg,"Length of transitionTable=" . count($this->transitionTable) . "\n");
  $this->searchdict = array(); // associative
  $alternates = $this->generate_hkalternates($keyin0);
  foreach($alternates as $alt) {
   $keyin = transcoder_processString($alt,"hk","slp1");
   $this->doVariant("",$keyin);
  }
  $this->keysin = [];
  foreach($this->searchdict as $k=>$v){
   $this->keysin[] = $k;
  }
  //$this->keys = $this->searchList;  // temporary
 }
 public function doVariant($pref,$word) {
  dbgprint($this->dbg,"doVariant: '$pref', '$word'\n");
  if (strlen($pref) == 0) {
   $this->searchdict[$word] = true;
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
     $pref1 = $pref . $newChar;
     if (strlen($pref1) == 2) {
      if (! $this->ngram2beg_check->validate($pref1)) {
       // $pref1 has a bad initial ngram. skip all words beginning with $pref1
       continue;
      }
     }
     if (strlen($pref1) == 3) {
      if (! $this->ngram3beg_check->validate($pref1)) {
       // $pref1 has a bad initial ngram. skip all words beginning with $pref1
       continue;
      }
     }
     if (! $this->ngram2_check->validate($pref1)) {
       // $pref1 has a bad 2gram. skip all words beginning with $pref1
      continue;
     }
     if (! $this->ngram3_check->validate($pref1)) {
       // $pref1 has a bad 2gram. skip all words beginning with $pref1
      continue;
     }
     $subWord = substr($word,strlen($varChar));
     $this->searchdict[$pref1 . $subWord] = true;
     if (strlen($word) > 1) {
       $this->doVariant($pref1,$subWord);
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

 public function generate_letter_alts($letter) {
  $letter_alts=array();
  $letter_alts['f'] = ['p','ph'];

  if (isset($letter_alts[$letter])) {
   return $letter_alts[$letter];
  }else {
   return array($letter);
  }
 }
 public function correcthk($wordin) {
  $wordin = preg_replace('/ii|ee/','i',$wordin);
  $wordin = preg_replace('/uu|oo/','u',$wordin);
  $wordin = preg_replace('/aa/','a',$wordin);
  $wordin = preg_replace('/chh/','ch',$wordin);
  $wordin = preg_replace('/E/','ai',$wordin);
  $wordin = preg_replace('/O/','au',$wordin);
  $wordin = preg_replace('|r(.)\1|','r\1',$wordin);
  $wordin = preg_replace('|R[R]?\^?i|','R',$wordin);
  return $wordin;
 }
 public function generate_hkalternates($wordin) {
  $ans=[];
  $dbg=false;
  // coerce to correct hk
  $wordin = $this->correcthk($wordin);
  $letters = str_split($wordin);
  dbgprint($dbg,"corrected wordin -> " . join(',',$letters) . "\n");
  $letter_alts = [];
  foreach($letters as $letter) {
   $letter_alts[] = $this->generate_letter_alts($letter);
  }
  $prevwords = [''];
  $n = strlen($wordin);
  for($i=0;$i<$n;$i++) {
   $alts = $letter_alts[$i];
   $nextwords=[];
   foreach($prevwords as $prevword) {
    foreach($alts as $alt) {
     $nextwords[] = $prevword . $alt;
    }
   }
   $prevwords = $nextwords;
  }
  return $prevwords;
 }
 
}
?>
