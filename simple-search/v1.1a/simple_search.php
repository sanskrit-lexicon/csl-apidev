<?php
/* 06-05-2017   
   08-03-2017. Generate grammar variants AFTER all other variants.
   10-12-2017. Revise based on changes to hwnorm1c. 
               Also, revise ../ngram data
               Also, revise dalnorm.normalize_key
               Add 't,tt' transition rule.
called by getword_list_1.0_main.php, which uses public variables
user_keyin  // in slp1
user_keyin_norm
normkeys
status :  200 (OK), 404 (not found),
          201 (found by partialmatches only)  02-05-2021. False start. not used
02-05-2021. partialmatches not found useful. Code is deactivated but present.

*/
require_once('get_parent_dirpfx.php');
$dirpfx = get_parent_dirpfx("simple-search");
require_once($dirpfx . "utilities/transcoder.php"); // initializes transcoder
require_once($dirpfx . "dbgprint.php");
require_once($dirpfx . "simple-search/simpleslp/query3.php");
require_once('dalnorm.php');

class Simple_Search{
 /*
 public $transitionTable_default_orig = [
   // The spellings are in slp1, but they are to applied when
   // the user input spelling is default
   ["a","A","ah"],  // ah tamilish ?
   ["i","I"],
   ["u","U"], 
   ["o","O"],
   ["e","E"],
   ["r","f","F","ri","ar","ru","rI","R","RI"],
   ["l","x","X","lri"],
   
   ["h","H"],
   ["n","Y","N","m","R","M"],  
   ["S","z","s","zh","sh"],
   ["b","v"],
   ["k","K"],
   ["g","G"],
   ["c","C","Ch","ch","cC"],
   ["j","J"],
   ["jy","jY"],
   ["w","W","t","T"],
   ["q","Q","d","D"],
   //["p","P","f","b","B"],
   ["p","P"],
   ["b","B","v","V"],
   ["t","tt"]
 ];
 */
 public $transitionTable_default = [  /// in process -- not used 02-13-2021 3PM
   // The spellings are in slp1, but they are to applied when
   // the user input spelling is default
   ["r","f","F","ri","ar","ru","rI","R","RI"],
   ["l","x","X","lri"],
   
   ["h","H"],
   ["n","Y","N","m","R","M"],  
   ["S","z","s","zh","sh"],
   ["b","v"],
   ["k","K"],
   ["g","G"],
   ["c","C","Ch","ch","cC"],
   ["j","J"],
   ["jy","jY"],
   ["w","W","t","T"],
   ["q","Q","d","D"],
   //["p","P","f","b","B"],
   ["p","P"],
   ["b","B","v","V"],
   ["t","tt"]
 ];
 public $transitionTable_slp1 = [
   // The spellings are in slp1. Apply these when
   // the user input spelling is NOT default
   ["a","A"],
   ["i","I"],
   ["u","U"],
   ["o","O"],
   ["e","E"],
   ["r","f","F","ri","ar","ru","rI"],   # Not R, since slp1 R is cerebral nasal
   ["l","x","X","lri"],
   ["n","R","Y","N","M","m"],  
   ["S","z","s"],
   ["k","K"],
   ["g","G"],
   ["c","C","cC"],
   ["j","J"],
   ["jy","jY"],
   ["w","W","t","T"],
   ["q","Q","d","D"],
   ["p","P","b","B"],
   ["b","B","v"],
   ["t","tt"]
 ];
 public $keysin,$keys,$normkeys;
 public $searchdict,$input_simple;
 public $dbg;
 public $dict;  // 20170725
 public $badprefs; // prefixes known to be non-valid for this search
 public $transitionTable;
 public $dirpfx;
 /* input00 is the 'input_simple' from list-0.2s_rw.php.  It is the
  spelling assumption of $keyin00.  The values can be 'default' or
  or slp1, deva, iast,hk,itrans
 */
 public function __construct($keyin00,$input00,$dict) {
  $this->dbg = false;
  dbgprint($this->dbg,"simple_search: construct: $keyin00, $input00, $dict\n");
  $this->input_simple = $input00;
  // sets $this->transitionTable
  $this->setTransitionTable($this->input_simple);
  $this->dict = strtolower($dict);

  # hwnorm1c copied from awork/sanhw1/ to ../hwnorm1/
  $dirpfx = get_parent_dirpfx("simple-search");
  $this->dirpfx = $dirpfx;
  dbgprint($this->dbg,"dirpfx = $dirpfx\n");
  $hwnorm1 = $dirpfx . "simple-search/hwnorm1";
  $this->dalnorm = new Dalnorm('hwnorm1c',$hwnorm1);
  // searchdict is associative array which is modified by doVariant
  //  It's keys are the different variants
  $this->searchdict = array();

  dbgprint($this->dbg,"calling convert_nonascii\n");
  $keyin0 = $this->convert_nonascii($keyin00);
  dbgprint($this->dbg,"Simple_construct: keyin00 = $keyin00, keyin0 = $keyin0\n");
  $alternates0 = [$keyin0];
  $normkey = $this->dalnorm->normalize($keyin0);
  if (!in_array($normkey,$alternates0)) {
   $alternates0[] = $normkey;
  }
  // Add alternates by removing certain last letters
  #$alternates = $this->generate_alternate_endings($alternates);
  #dbgprint($this->dbg,"Simple_construct: alternates=" . join(',',$alternates)."\n");
  // Generate variants for each of the alternates.
  //  The spelling of the alternates is in SLP1.
  foreach($alternates0 as $alt) {
   $keyin = $alt;
   // Use transition table to generate variants
   // Keep track of unknown prefixes in $badprefs array
   //$this->badprefs = [];
   //$this->doVariant("",$keyin);
   $this->getVariants_from_db($keyin);
  }
  if (count($this->searchdict) == 0) {  // number of keys in searchdict
   // add grammar variants to searchdict.
   $this->add_grammar_variants();
  } else { // Always:  e.g. veda has also vedana
   // $this->add_grammar_variants();
  }
  // Linearize searchdict keys into keysin array
  $this->keysin = [];
  foreach($this->searchdict as $k=>$v){
   $this->keysin[] = $k;
  }
  // Generate distinct normalized keys
  $this->generate_normkeys();
  $nfound = count($this->normkeys);
  dbgprint($this->dbg,"number of normalized keys found = $nfound\n");
  if ($nfound > 0 ) {
   $this->status = 200; // ok
  }else {
   $this->status = 404;  // not found
  }
  dbgprint($this->dbg,"status set to {$this->status}\n");
  //$this->normalize_keyin($keyin0);
  $this->normalize_keyin($keyin00);
  // close dalnorm connection
  $this->dalnorm->close();
  dbgprint($this->dbg,"simple_search v1.1a\n");
  if ($this->dbg) {
   $this->dbgprint_api_values();
  }
 } // end construct
 public function dbgprint_api_values() {
  dbgprint(true,"user_keyin = {$this->user_keyin}\n");
  dbgprint(true,"user_keyin_norm = {$this->user_keyin_norm}\n");
  $keys = $this->normkeys;
  $nkeys = count($keys);
  dbgprint(true,"length of normkeys = $nkeys\n");
  for ($i=0;$i<count($keys);$i++) {
   $key = $keys[$i];
   dbgprint(true,"normkeys[$i] = $key\n");
  }
 }
 public function setTransitionTable($input) {
  if ($input == 'default') {
   $this->transitionTable = $this->transitionTable_default;
  }else {
   $this->transitionTable = $this->transitionTable_slp1;   
  }
 }
 public function add_grammar_variants() {
  $galtsall = [];
  foreach($this->searchdict as $k=>$v){
   $galts = $this->grammar_variants($k);
   foreach($galts as $keyina) {
    $galtsall[] = $keyina;
   }
  }

   foreach($galtsall as $keyina) {
    $this->searchdict_add($keyina);
   }
  }
 public function normalize_keyin($keyin0) {
  // keyin0 has been transcoded to slp1, based on input_simple 
  $this->user_keyin = $keyin0;
  $this->user_keyin_norm = $this->dalnorm->normalize($this->user_keyin);
  return;
  
 }
 public function generate_normkeys() {
  // normkeys is the array that the caller sees. It contains
  // normalized spellings that solve the search
  $this->normkeys = [];
  $dbg=false; #true
  
  // $foundkeysin is associative array, to make the 
  // search for duplicate normalized keys simpler.
  $foundkeysin = array();
  foreach($this->keysin as $key) {
   $normkey = $this->dalnorm->normalize($key);
   if (!isset($foundkeysin[$normkey])) {
    $foundkeysin[$normkey] = true;
    $this->normkeys[] = $normkey;
   }
  }
 }

 public function searchdict_add_basic($k0) {
  // add $k0 to searchdict PROVIDED it is in hwnorm1c for SOME dictionary
  $k = $this->dalnorm->normalize($k0);
  $matches = $this->dalnorm->get1($k);
  $nmatches = count($matches);
  //dbgprint($this->dbg,"searchdict_add_basic: $k0, $k, $nmatches\n");
  if ($nmatches > 0) {
   if(!isset($this->searchdict[$k])) {
    $this->searchdict[$k]=true;
   }
  }
 }
 public function searchdict_add_norm($k0) {
  $this->searchdict_add_basic($k0);
 }
 public function searchdict_add($k) {
  $alts1 = $this->generate_alternate_endings(array($k));
  $alts2 = [];
  foreach($alts1 as $a1) {
   $alts3 = $this->grammar_variants($a1);
   foreach($alts3 as $a3) {
    $alts2[] = $a3;
   }
  }
  # normalize and add to searchdict
  foreach($alts2 as $a2) {
   $this->searchdict_add_norm($a2);
  }
 }
 public function getVariants_from_db($word) {
  $dbg = $this->dbg;

  $json = getword_simpleslp1($word);
  dbgprint($dbg,"back from getword_simpleslp1\n");
  $obj = json_decode($json);
  if ($obj->status != 200) {
   dbgprint($dbg,"getVariants_from_db: word=$word, json = $json\n");
   return;
  }
  $matches = $obj->result; // array of slp1 spellings
  if ($dbg) {
   $temp = join(' ',$matches);
   dbgprint($dbg,"getVariants_from_db: $word -> $temp\n");
  }
  foreach($matches as $match) {
   $this->searchdict_add($match);
  }
 }
 public function doVariant($pref,$word) {
  /* Use transitionTable to generate variants to '$word', using the
     given prefix '$pref'.
     Variants are entered into the $this->searchdict associative array.
     It is a recursive routine, called externally with $pref as the
     empty string;  in this case $word goes into searchdict.
     The function bottoms out when $word is the empty string.
     Otherwise, it uses getChar to retrieve prefixes of $word
     that occurs in transitionTable; this prefix is $varChar.
  */
  $dbg = $this->dbg;
  dbgprint($dbg,"doVariant: pref='$pref', word='$word'\n");
  if (strlen($pref) == 0) {
   // This occurs only when doVariant is called externally.  Recursive
   // Calls have strlen($pref)>0.
   $this->searchdict_add($word);
  }
  if (isset($this->badprefs[$pref])) {
   // We know this prefix is bad
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
  $val = $this->ngramValidate($pref);  // false or a key that matches LIKE
  dbgprint($this->dbg,"doVariant: val=$val\n");
  if ($val == false) {
   // '$pref' is not validated , return with no further analysis
   // add $pref to the bad ones
   $this->badprefs[$pref]=true;   # debug  to disregard badprefs
   dbgprint($dbg,"doVariant bad pref 1: $pref ($word)\n");
   return;
  }
  $key = $val;  // a normalized slp1 key of hwnorm1c
  //$this->partialmatches[$key] = true;
  dbgprint($dbg,"doVariant finding variants of '$pref' + '$word'\n");
  // Find variants for the beinning of $word
  // $varCharsData is an array.
  // WHAT IF THERE ARE NO VARIANTS?? CAN THIS HAPPEN?
  // dbgprint(true,"Calling getChar: word=$word\n");
  $varCharsData = $this->getChar($word);
  //dbgprint(true,"Back from getChar: word=$word\n");
  if ($this->dbg) {
   $n = count($varCharsData);
   dbgprint(true," varCharsData has $n items\n");
    $tempstrs = [];
    foreach($varCharsData as $temp) {
     list($itransition,$varChar) = $temp;
     $tempstrs[] = "$itransition,$varChar";
    }
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
   if (strlen($varChar) > 1) {
    $subWord1 = substr($word,1);
    $newChar1 = substr($varChar,0,1) ;
    $pref1 = $pref . $newChar1;
    $this->doVariant($pref1,$subWord1);
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
  //dbgprint(true,"getChar: word=$word\n");
  $newChars = array();
  if (! isset($this->transitionTable))  {
   dbgprint($this->dbg,"getChar: transitiontable not set\n");
  }
  $ntransition = count($this->transitionTable);
  dbgprint($this->dbg,"enter getChar: word=$word, ntransition=$ntransition\n");
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

 public function unused_correcthk($wordin) {
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
 public function unused_generate_hkalternates($wordin) {
  $ans=[];
  $wordin = $this->unused_correcthk($wordin);
  return [$wordin];  // expect a list
 }
 public function generate_alternate_endings($alternates) {
  $ans = [];
  foreach($alternates as $alt) {
   $ans[] = $alt; 
   //1. if alt ends in m,s,H,  generate new alternate by dropping final letter
   //$alt1 = preg_replace('/^(.*)[MmsHhn]$/','\1',$alt);
   $alt1 = preg_replace('/[MmsHhn]$/','',$alt);
   if (! in_array($alt1,$ans)){
    $ans[] = $alt1;
   }
   //2. if alt ends in 'f', try 'A'  : example kartf -> kartA
   //  and vice-versa
   $alt1 = preg_replace('/f$/','A',$alt);
   if (! in_array($alt1,$ans)){
    $ans[] = $alt1;
   }
   $alt1 = preg_replace('/A$/','f',$alt);
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
   // 1. Jul 19, 2017. If $word ends in a consonant,
   // add an 'a' (schwa) to the end, and generate variants of that.
   $ans[] = $word . 'a';
   # do skd nom. sing. variants 
   $ans1 = $this->grammar_variants_skd($word);
   foreach ($ans1 as $a1) {
    $ans[] = $a1;
   }
   #return $ans;
  } 
  // Word now ends in a vowel.
  if (preg_match('|[iu]$|',$word)) {
   // 2. word ends in i,u:  add alternate m and H  (nom. sing.)
   $ans[] = $word . 'H';
   $ans[] = $word . 'm';
  }
  if (preg_match('|[iI]$|',$word)) {
   // 2. word ends in 
   $ans[] = substr($word,0,-1) . 'in';
  }
  if (preg_match('|i[Rn]I$|',$word)) {
   // 2. word ends in 
   $word1 = substr($word,0,-3) . 'in';
   #dbgprint(true,"dbg: $word -> $word1\n");
   $ans[] = substr($word,0,-3) . 'in';
  }
  
  // Word now ends in a vowel other than i,u
  if (preg_match('|a$|',$word)) {
   // 2. word ending in 'a'.  Think 'karma' -> 'karman'
   $ans[] = $word . 'n';
  }
  
  # do skd nom. sing. variants 
  $ans1 = $this->grammar_variants_skd($word);
  foreach ($ans1 as $a1) {
   $ans[] = $a1;
  }
  return $ans;
 }
 public function grammar_variants_skd($word) {
  // spelling of $word is slp1
  // skd tends to use nominative singular for headwords
  $ans=[];
  return $ans;
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
  }else if (preg_match('|in$|',$word)) {
   // example guRin -> guRi
   $word1 = substr($word,0,-2) . 'I';
   $ans[] = $word1;
  }
  return $ans;
 }

 public function ngramValidate($word) {
 
  // see if anything in hwnorm1c starts with $word for this dictionary
  // normalize
  $norm = $this->dalnorm->normalize($word);
  // no problem found
  try {
   $sql1 = "PRAGMA case_sensitive_like = 1;";
   $file_db = $this->dalnorm->file_db;
   $file_db->exec($sql1);
  } catch (PDOException $e) {
   return true;
  }
  try {
   $sql = "SELECT * from hwnorm1c where key LIKE '$word%' LIMIT 1;";
   $matches = $this->dalnorm->get($sql);
   $nmatches = count($matches);
   if ($nmatches == 0) {
    return false;
   } else {
    // extract the matching key
    $rec = $matches[0];  // ($key,$data from hwnorm1c)
    $key = $rec[0];
    return $key;
    //return true;
   }
  }  catch (Exception $e) {
   return true;
  }

 }
 public function clean_slp1($x) {
  $y =  preg_replace('/[^a-zA-Z|~]/','',$x);
  return $y;
 }
 public function simple_slp1_lower_callback($matches) {
  $a = $matches[0];
  $b = strtolower($a);
  return $b;
 }
 public function tokenizer_slp1($word) {
  // Assume $word is in slp1
  // convert to simpleslp1lo 
  //$word1 = mb_strtolower($word, 'UTF-8');
  $word1 = preg_replace_callback('|[AIUFXEOMHKGNCJWQTDPBLVSZ]|','Simple_Search::simple_slp1_lower_callback',$word);
  // replace double letters
  $word1a = preg_replace_callback('|(.)\1|','Simple_Search::double_letter_callback',$word1);
  $save_dir = transcoder_get_dir();
  transcoder_set_dir($this->simple_transcoder_dir());
  $word2 = transcoder_processString($word1a,'slp1','simpleslp1lo');
  transcoder_set_dir($save_dir);
  dbgprint($this->dbg,"tokenizer_slp1: $word, $word1, $word1a, $word2\n");
  return $word2;
 }
 public function simple_transcoder_dir() {
  return ($this->dirpfx . "simple-search/simpleslp/transcoder");
 }
 public function convert_nonascii($wordin) {
  // This function can also change $this->transitionTable
  $input_simple=$this->input_simple;
  // Step1: deal with specific transcodings from $input_simple
  if ($input_simple == 'slp1') {
   $wordin2 = $this->clean_slp1($wordin);
   $wordin2 = $this->tokenizer_slp1($wordin2);
   return $wordin2;
  }

  if ($input_simple == 'hk') {
   $wordin1 = transcoder_processString($wordin,'hk','slp1');
   $wordin2 = $this->clean_slp1($wordin1);
   $wordin2 = $this->tokenizer_slp1($wordin2);
   return $wordin2;
  }
  if ($input_simple == 'itrans') {
   $wordin1 = transcoder_processString($wordin,'roman','slp1');
   $wordin2 = $this->clean_slp1($wordin1);
   $wordin2 = $this->tokenizer_slp1($wordin2);
   return $wordin2;
  }
  if ($input_simple == 'iast') {
   // transcoder files use 'roman' instead of 'iast' 
   $wordin1 = transcoder_processString($wordin,'roman','slp1');
   $wordin2 = $this->clean_slp1($wordin1);
   $wordin2 = $this->tokenizer_slp1($wordin2);
   return $wordin2;
  }
  if ($input_simple == 'deva') {
   $wordin1 = transcoder_processString($wordin,'deva','slp1');
   $wordin2 = $this->clean_slp1($wordin1);
   $wordin2 = $this->tokenizer_slp1($wordin2);
   return $wordin2;
  }
  // step 2.  Assume $input_simple == default
  
  // detect devanagari by converting to slp1
  $wordin1 = transcoder_processString($wordin,'deva','slp1');
  if ($wordin1 != $wordin) {
   // Assume $wordin is spelled in devanagari
   $wordin2 = $this->clean_slp1($wordin1);
   $wordin2 = $this->tokenizer_slp1($wordin2);
   // Also, change transitionTable (Not used as of 2-13-2021)
   $this->transitionTable = $this->transitionTable_slp1;
   return $wordin2;
  }
  // $wordin might have letters with diacritics.
  // We will lower-case the string first, a
  dbgprint($this->dbg,"convert_nonascii calling tokenizer_default: $wordin1\n");
  $wordin1 = $this->tokenizer_default($wordin);
  $wordin2 = $this->clean_slp1($wordin1);
  //dbgprint($this->dbg,"wordin=$wordin, wordin1=$wordin1, wordin2=$wordin2\n");
  return $wordin2;
 }
 public function tokenizer_default($word) {
  // Assume $word consists of ascii letters in lower case, and
  // extended ascii letters in lower case occurring in IAST.
  // Return an slp1 representation based on the simple_simpleslp1 transcoder
  // applied to the lower-cased $word
  // change to lower case
  $word1 = mb_strtolower($word, 'UTF-8');
  //dbgprint(true,"td: word=$word, word1=$word1\n");
  // do other preparation, similar to simpleslp1.py function simpleslp1
  dbgprint($this->dbg,"tokenizer_default calling transcoder_get_dir\n");
  $savedir = transcoder_get_dir();
  $newdir = $this->simple_transcoder_dir();
  //dbgprint($this->dbg,"savedir=$savedir\newdir=$newdir");
  transcoder_set_dir($newdir);
  //dbgprint($this->dbg,"tokenizer_default calling transcoder\n");
  $word1a = transcoder_processString($word1,'simple','simpleslp1');
  transcoder_set_dir($savedir);
  //dbgprint($this->dbg,"tokenizer_default back from transcoder\n");
  // replace double letters
  $word2 = preg_replace_callback('|(.)\1|','Simple_Search::double_letter_callback',$word1a);

  dbgprint($this->dbg,"tokenizer_default: $word, $word1, $word1a, $word2\n");
  return $word2;
 }
 public function double_letter_callback($matches) {
  $a = $matches[0]; // string of length 2  xx
  $b = $a[0];  // x
  return $b;
 }
 public function unused_clean_default($word) {
  // superceded by tokenizer_default
  // may need further adjustment.
  // $word is in default spelling,  but in lower case without diacritics
  // prepare for conversion to slp1
  $word1 = preg_replace('|w|','v',$word);
  $word1 = preg_replace('|f|','p',$word1);
  $word1 = preg_replace('|x|','z',$word1);  # e.g. xenophobe
  // tamilish
  $word1 = preg_replace('|oo|','u',$word1);
  $word1 = preg_replace('|ou|','o',$word1);
  //$word1 = preg_replace('|f|','ph',$word1);
  return $word1;
 }
}
?>
