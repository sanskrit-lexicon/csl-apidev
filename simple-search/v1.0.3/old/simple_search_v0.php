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
  $this->dbg = true;
  dbgprint($this->dbg,"Length of transitionTable=" . count($this->transitionTable) . "\n");
  // searchdict is associative array which is modified by doVariant
  //  It's keys are the different variants
  $this->searchdict = array();
  $alternates = $this->generate_hkalternates($keyin0);
  foreach($alternates as $alt) {
   $keyin = transcoder_processString($alt,"hk","slp1");
   $this->doVariant("",$keyin);
   dbgprint($this->dbg,"alt=$alt\n");
   // Jul 19, 2017. If $keyin ends in a consonant,
   // add an 'a' (schwa) to the end, and generate variants of that.
   if (true) {  // in case we want to make this an option
    $slp1_consonants = '/[kKgGNcCjJYwWqQRtTdDnpPbBmyrlvSzsh]$/';
    if (preg_match($slp1_consonants,$keyin)) {
     $keyina = $keyin . 'a';
     $this->doVariant("",$keyina);
    }
   }
  }
  // keysin is linear array of variants
  $this->keysin = [];
  foreach($this->searchdict as $k=>$v){
   $this->keysin[] = $k;
  }
  //$this->keys = $this->searchList;  // temporary
 }
 public function doVariant($pref,$word) {
  /* This is the most important function of the class.
     Uses transitionTable to generate alternates to '$word', using the
     given prefeix '$pref'.
     Alternates are entered into the $this->searchdict associative array.
     It is a recursive routine, called externally with $pref as the
     empty string;  in this case $word goes into searchdict.
     The function bottoms out with $word is the empty string.
     Otherwise, it uses getChar to retrieve the longest prefix of $word
     that occurs in transitionTable; this prefix is $varChar.
     When
  */
  dbgprint($this->dbg,"doVariant: '$pref', '$word'\n");
  if (strlen($pref) == 0) {
   $this->searchdict[$word] = true;
   dbgprint($this->dbg,"doVariant: (a) Add $word to searchdict\n");
  }
  if (strlen($word) == 0) {
   return; // done
  }
  $varChar = $this->getChar($word);
  dbgprint($this->dbg,"doVariant: varChar=$varChar\n");
  /* This logic removes 1 (one) character from $word, and recurses.
     Should it remove len($varChar) characters ?
  */
  if (strlen($word) > 1) {
   $this->doVariant($pref . substr($word,0,1),substr($word,1));
  }
  foreach($this->transitionTable as $variants) {
   $cases = array();
   $isExist = false;
   /* if our $varChar (next prefix) is in $variants,
      set $isExist to true, and $cases to the other items of variants
      besides $varChar
   */
   foreach($variants as $variant) {
    if ($variant == $varChar) {
     $isExist = true;
    } else {
      $cases[] = $variant;
    }
   }
   // Theoretically, $varChar could occur in more than one $variants list.
   // We might be able simplify by demanding the different $variants lists
   // of transitionTable be disjoint sets (no items in common).
   // Regardless, if $isExist is false, there's nothing more to do here.
   if ($isExist == true) {
    foreach($cases as $newChar) {
     // $newChar is one of the alternates to $varChar.
     $pref1 = $pref . $newChar;
     // For efficiency, we exclude cases which are not valid
     // ngrams.  This is a slightly inaccurate representation of that
     // intent.  We should improve the code here.
     // CONFUSING: Looks like we check validity of $pref1 as a 2-gram
     //  twice; and similarly as a 3-gram.
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
     // We've accepted $pref1 as a valid character sequence for Sanskrit
     $subWord = substr($word,strlen($varChar));
     // We remove the $varChar prefix from $word (getting $subWord)
     // and concatenate $pref1 and $subWord as a new entry in searchdict
     $newWord = $pref1 . $subWord;
     $this->searchdict[$newWord] = true;
     dbgprint($this->dbg,"doVariant: (b) Add $newWord to searchdict\n");
     // Recurse.  Why do this check on strlen($word) ?
     if (strlen($word) > 1) {
       $this->doVariant($pref1,$subWord);
     }
    }
   }
  }
 }
 public function getChar($word) {
  /* word is assumed to be an SLP1 spelling.
     Find strings in transitionTable that are prefixes to word;
     And return the longest such string.
  */
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
  $dbg=true;
  dbgprint($dbg,"Enter generate_hkalternates($wordin)\n");
  // coerce to correct hk
  $wordin = $this->correcthk($wordin);
  $letters = str_split($wordin);
  dbgprint($dbg,"corrected wordin -> " . join(',',$letters) . "\n");
  $letter_alts = [];
  foreach($letters as $letter) {
   $temp=$this->generate_letter_alts($letter);
   $letter_alts[] = $temp;
   if ($dbg) {
    $tempstr = join(',',$temp);
    dbgprint($dbg,"Letter alts[$letter] = $tempstr\n");
   }
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
  if ($dbg) {
   dbgprint($dbg,"Return from generate_hkalternates($wordin) ...\n");
   $i=0;
   foreach($prevwords as $x) {
    $i = $i + 1;
    dbgprint($dbg," word[$i] = $x\n");
   }
  }
  return $prevwords;
 }
 
}
?>
