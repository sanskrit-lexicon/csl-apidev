<?php
/* 06-05-2017   
   08-03-2017. Generate grammar variants AFTER all other variants.
   10-12-2017. Revise based on changes to hwnorm1c. 
               Also, revise ../ngram data
               Also, revise dalnorm.normalize_key
               Add 't,tt' transition rule.
*/
$dirpfx = "../../";
require_once($dirpfx . "utilities/transcoder.php"); // initializes transcoder
require_once($dirpfx . "dbgprint.php");
require_once('ngram_check.php');
require_once('dalnorm.php');

class Simple_Search{

 public $transitionTable = [ // slp1
   ["a","A"],
   ["i","I"],
   ["u","U"],
   ["r","f","F","ri","ar","ru","rI"],
   ["l","x","X","lri",],
   ["h","H"],
   ["M","n","R","Y","N","m"],
   ["S","z","s","zh","sh"],
   ["b","v"],
   ["k","K"],
   ["g","G"],
   #["c","C","Ch","ch"],
   ["c","C","cC"],
   ["j","J"],
   ["w","W","t","T"],
   ["q","Q","d","D"],
   ["p","P","f"],
   ["b","B","v","V"],
   ["t","tt"]
];
 public $keysin,$keys,$normkeys;
 public $searchdict;
 public $dbg;
 public $ngram2_check;
 public $ngram2beg_check;  // beginning 2-gram
 public $ngram3_check;
 public $ngram3beg_check;  // beginning 2-gram
 public $dict;  // 20170725
 public function __construct($keyin00,$dict) {
  $this->dict = strtolower($dict);
  $this->ngram_initialilze();

  # hwnorm1c copied from awork/sanhw1/ to ../hwnorm1/
  #$this->dalnorm = new Dalnorm('hwnorm1c','../../../sanhw1');
  $this->dalnorm = new Dalnorm('hwnorm1c','../hwnorm1');
  $this->dbg = false;
  #$this->dbg = true;
  dbgprint($this->dbg,"Simple_construct: keyin00 = $keyin00\n");
  // searchdict is associative array which is modified by doVariant
  //  It's keys are the different variants
  $this->searchdict = array();
  $keyin0 = $this->convert_nonascii($keyin00);
  dbgprint($this->dbg,"Simple_construct: keyin0 = $keyin0\n");
  // $keyin0 is assumed to generally follow HK spelling. However,
  // the 'generate_hkalternates' makes certain 'correction' or adjustments
  // to this spelling.
  $alternates = $this->generate_hkalternates($keyin0);
  // Add alternates by removing certain last letters
  $alternates = $this->generate_alternate_endings($alternates);
  dbgprint($this->dbg,"Simple_construct: alternates=" . join(',',$alternates)."\n");
  // Generate variants for each of the HK alternates.
  //  The spelling of the alternates is in SLP1.
  foreach($alternates as $alt) {
   // First step is to convert alternate to slp1 spelling.
   $keyin = transcoder_processString($alt,"hk","slp1");
   dbgprint($this->dbg,"Simple_construct: BEGIN alt=$alt, keyin=$keyin\n");
   // Now, the main step, use transition table to generate variants
   $this->doVariant("",$keyin);
  }
  // add grammar variants to searchdict. This may depend on dictionary.
  foreach($this->searchdict as $k=>$v){
   $galts = $this->grammar_variants($k);
   foreach($galts as $keyina) {
    //$this->searchdict[$keyina] = true;
    $this->searchdict_add($keyina);
   }
  }
  // Linearize searchdict keys into keysin array
  $this->keysin = [];
  foreach($this->searchdict as $k=>$v){
   $this->keysin[] = $k;
  }
  // Generate distinct normalized keys
  $this->generate_normkeys();
  //$this->user_key_first($keyin0); // 11-01-2017. not used. see comment below
  // provide keyin_norm for user
  $this->user_keyin = transcoder_processString($keyin0,"hk","slp1");
  $this->user_keyin_norm = $this->dalnorm->normalize($this->user_keyin);
  // close dalnorm connection
  $this->dalnorm->close();
 }
 public function unused_user_key_first($keyin0) {
  /* 11-01-2017
   if the user key is one of the normkeys, put it first in the list
   This is not quite what is needed, since the caller sorts the
   results by word frequency, and also checks whether they are 
   known words.
  */
  // Let's get the normalized slp1 spelling of $keyin0
  $user_key_slp1 = transcoder_processString($keyin0,"hk","slp1");
  $user_key_slp1_norm = $this->dalnorm->normalize($user_key_slp1);
  $i0 = -1;  // subscript where user key found
  for($i=0;$i<count($this->normkeys);$i++) {
   if ($this->normkeys[$i] == $user_key_slp1_norm ) {
    $i0 = $i;
    break;
   }
  }
  dbgprint($this->dbg,"user_key_first: $keyin0, $user_key_slp1, $user_key_slp1_norm, $i0\n");
  for($i=0;$i<count($this->normkeys);$i++) {
   $temp = $this->normkeys[$i];
   dbgprint($this->dbg,"user_key_first. normkeys[$i] = $temp\n");
  }
  if ($i0 != -1) {
   if ($i0 != 0) {
    // Nothing to do if user key already first
    // $ a is temporary array, a reordering of normkeys
    $a = array();
    $a[] = $this->normkeys[$i0]; // put user key first
    for($i=0;$i<count($this->normkeys);$i++) {
     if ($i != $i0) {
      $a[] = $this->normkeys[$i];
     }
    }
    // reset normkeys to a
    $this->normkeys = $a;
   }
  }
 }
 public function generate_normkeys() {
  // normkeys is the array that the caller sees. It contains
  // normalized spellings that 'solve' the search
  $this->normkeys = [];
  $dbg=false;
  
  // $foundkeysin is associative array, to make the 
  // search for duplicate normalized keys simpler.
  $foundkeysin = array();
  foreach($this->keysin as $key) {
   $normkey = $this->dalnorm->normalize($key);
   if (!isset($foundkeysin[$normkey])) {
    $foundkeysin[$normkey] = true;
    $this->normkeys[] = $normkey;
    dbgprint($dbg,"NORMKEY: $key -> $normkey\n");
   }
  }
 }

 public function searchdict_add($k) {
  $this->searchdict[$k]=true;
  #$dbg=true;
  #dbgprint($dbg,"searchdict_add: $k\n");
 }
 public function doVariant($pref,$word) {
  /* This is the most important function of the class.
     Uses transitionTable to generate alternates to '$word', using the
     given prefeix '$pref'.
     Alternates are entered into the $this->searchdict associative array.
     It is a recursive routine, called externally with $pref as the
     empty string;  in this case $word goes into searchdict.
     The function bottoms out when $word is the empty string.
     Otherwise, it uses getChar to retrieve prefixes of $word
     that occurs in transitionTable; this prefix is $varChar.
     When
  */
  dbgprint($this->dbg,"doVariant: '$pref' + '$word'\n");
  if (strlen($pref) == 0) {
   // This occurs only when doVariant is called externally.  Recursive
   // Calls have strlen($pref)>0.
   //$this->searchdict[$word] = true;
   $this->searchdict_add($word);
   dbgprint($this->dbg,"doVariant: (a) Add $word to searchdict\n");
  }
  if (!$this->ngramValidate($pref)) {
   // '$pref' is not validated by ngrams, return with no further analysis
   dbgprint($this->dbg,"doVariant:  non-valid ngram $pref\n");
   return;
  }
  if (strlen($word) == 0) {
   // Recursive calls bottom out here.
   // Add $pref to searchdict
   // Check if $pref is NGram-valid, and if so add to searchdict results
   //$this->searchdict[$pref] = true;
   $this->searchdict_add($pref);
   return; // done
  }
  // Find variants for the beinning of $word
  // $varCharsData is an array.
  // WHAT IF THERE ARE NO VARIANTS?? CAN THIS HAPPEN?
  $varCharsData = $this->getChar($word);
  if ($this->dbg) {
    $tempstrs = [];
    foreach($varCharsData as $temp) {
     list($itransition,$varChar) = $temp;
     $tempstrs[] = "$itransition,$varChar";
    }
   dbgprint($this->dbg,"doVariant: varChars=" . count($varCharsData) . "  " . join(' ; ',$tempstrs) . "\n");
  }
  if (count($varCharsData) == 0) {
   // e.g., if first character of word is 'e'
   $varChar = substr($word,0,1);
   $newChar = $varChar;
   $subWord = substr($word,strlen($varChar)); 
   $pref1 = $pref . $newChar;
   $this->doVariant($pref1,$subWord);
  }
  foreach($varCharsData as $varCharsDatum) {
   list($itransition,$varChar) = $varCharsDatum;
   // remove varChar from beginning of word
   $subWord = substr($word,strlen($varChar)); 
   // Try each of the variants
   $variants = $this->transitionTable[$itransition];
    foreach($variants as $newChar) {
     $pref1 = $pref . $newChar;
     $this->doVariant($pref1,$subWord);
    }
  }
 }
 public function getChar($word) {
  /* word is assumed to be an SLP1 spelling.
     transitionTable is a list of variants.
     For a given variant list, find the longest of its variants which
     is a prefix to $word.  If there is such, add the index of the
     transition table and the longest word to the return values
     And return the longest such string.
  */
  $newChars = array();
  $ntransition = count($this->transitionTable);
  for($itransition=0;$itransition<$ntransition;$itransition++) {
   $variants = $this->transitionTable[$itransition];
   $ln0 = 0;
   $variant0 = "";
   foreach($variants as $variant) {
    $ln = strlen($variant);
    if (substr($word,0,$ln) == $variant) {
     if ($ln > $ln0) {
      $variant0 = $variant;
      $ln0 = $ln;
     }
    }
   }
   if($ln0 > 0) {
    $newChars[] = array($itransition,$variant0);
   }
  }
  return $newChars;
 }

 public function correcthk($wordin) {
  $wordin = preg_replace('/ii|ee/','i',$wordin);
  $wordin = preg_replace('/uu|oo/','u',$wordin);
  $wordin = preg_replace('/aa/','a',$wordin);
  $wordin = preg_replace('/chh|ch/','c',$wordin);
  #$wordin = preg_replace('/cc/','c',$wordin); # 10-23-2017
  $wordin = preg_replace('/E/','ai',$wordin);
  $wordin = preg_replace('/O/','au',$wordin);
  # 10-12-2017. Removed next
  #$wordin = preg_replace('|r(.)\1|','r\1',$wordin);
  $wordin = preg_replace('|R[R]?\^?i|','R',$wordin);
  $wordin = preg_replace('/w|W/','v',$wordin);
  return $wordin;
 }
 public function generate_hkalternates($wordin) {
  $ans=[];
  $wordin = $this->correcthk($wordin);
  return [$wordin];  // expect a list
 }
 public function generate_alternate_endings($alternates) {
  $ans = [];
  foreach($alternates as $alt) {
   $ans[] = $alt; 
   $alt1 = preg_replace('/^(.*)[msH]$/','\1',$alt);
   if (! in_array($alt1,$ans)){
    $ans[] = $alt1;
   }
  }
  return $ans;
 }
 public function grammar_variants($word) {
  // $word in SLP1 spelling. Return array, including $word and maybe
  // some other possibilities
  $ans = array();
  $ans[] = $word;  // start with given word
  $slp1_consonants = '/[kKgGNcCjJYwWqQRtTdDnpPbBmyrlvSzsh]$/';
  if (preg_match($slp1_consonants,$word)) {
   // 1. Jul 19, 2017. If $keyin ends in a consonant,
   // add an 'a' (schwa) to the end, and generate variants of that.
   $ans[] = $word . 'a';
  } else if (preg_match('|a$|',$word)) {
    // 2. word ending in 'a'.  Think 'karma' -> 'karman'
    $ans[] = $word . 'n';
  }
  // July 25, 2017.  Special logic for dictionaries
  if ($this->dict == 'skd') {
   $ans1 = $this->grammar_variants_skd($word);
   foreach($ans1 as $x) {
    $ans[] = $x;
   }
  }
  return $ans;
 }
 public function grammar_variants_skd($word) {
  // spelling of $word is slp1
  // skd tends to use nominative singular for headwords
  $ans=[];
 
  if (preg_match('|f$|',$word)) {
   // replace ending 'f' with 'A'  (kartf -> kartA)
   $word1 = substr($word,0,-1) . 'A';
   $ans[] = $word1;
  }else if (preg_match('|man$|',$word)) {
   // example: Atman -> AtmA
   $word1 = substr($word,0,-2) . 'A';
   $ans[] = $word1;
  }else if (preg_match('|[vm]at$|',$word)) {
   // examples guRavat -> guRavAn, hanumat -> hanumAn
   $word1 = substr($word,0,-2) . 'An';
   $ans[] = $word1;
  }else if (preg_match('|as$|',$word)) {
   // examples uzas -> uzA
   $word1 = substr($word,0,-2) . 'A';
   $ans[] = $word1;
  }
  return $ans;
 }
 public function ngram_initialilze(){
  $ngramdir = "ngram1";
  #$dbg=true;
  #dbgprint($dbg,"Using ngramdirectory $ngramdir\n");
  $this->ngram2_check = new Ngram_Check(2,"../$ngramdir/2gram.txt");
  $this->ngram3_check = new Ngram_Check(3,"../$ngramdir/3gram.txt");
  // beginning ngrams
  $this->ngram2beg_check = new Ngram_Check(2,"../$ngramdir/2gram_beg.txt");
  $this->ngram3beg_check = new Ngram_Check(3,"../$ngramdir/3gram_beg.txt");
 }

 public function ngramValidate($word) {
   if (! $this->ngram2beg_check->validate_beg($word)) {
    // $word has a bad initial ngram. 
    return false;
   }
   if (! $this->ngram3beg_check->validate_beg($word)) {
    // $word has a bad initial ngram. 
    return false;
   }
  if (! $this->ngram2_check->validate($word)) {
    // $word has a bad 2gram. 
   return false;
  }
  if (! $this->ngram3_check->validate($word)) {
    // $word has a bad 2gram. 
   return false;
  }
  // No problem found.
  return true;
 }
 public function convert_nonascii($wordin) {
 // Devanagari and IAST to HK
 if (preg_match('/^[a-zA-Z]+$/',$wordin)) {
  // no conversion needed
  return $wordin;
 }
 // transcode Devanagari to HK
 $wordin1 = transcoder_processString($wordin,'deva','slp1');
 if ($wordin1 != $wordin) {
  // $wordin has characters in the Devanagari character set
  // Further convert $wordin1 to HK transliteration (from slp1)
  $wordin1 =  transcoder_processString($wordin1,'slp1','hk');
  // Assume there is no mixture of Devanagari and IAST. Thus return
  return $wordin1;
 }
 // There are no Devanagari characters in $wordin, but there are
 // some non-alphabetical characters.  ASSUME this is due to the
 // presence of IAST diacritics.
 // transcode IAST to HK
 // 'w' has special sense in slp1.  Can't be part of regular IAST.
 $wordin1 = preg_replace('/w|W/','v',$wordin1);

 $wordin2 = transcoder_processString($wordin1,'roman','slp1');
 if ($wordin2 != $wordin1) {
  $wordin2 =  transcoder_processString($wordin2,'slp1','hk');
 }
 return $wordin2;
 }
}
?>
