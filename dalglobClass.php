<?php
/* dalglobClass.php  Feb 2, 2020.  sqlite files that are 'global', i.e. not
   tied to a dictionary
*/
require_once('dbgprint.php');
require_once('parm.php');
class Dalglob {
 #public $dict;
 #public $dictinfo;
 public $sqlitefile;
 public $file_db;
 public $dbg=false;
 public $ans= array('status'=>404, 'dicts'=>array());
 public $dbname;
 
 public $tabname;  # name of table in sqlitefile. 
 public $tabid;    # name of 'id' key used by getgeneral
 public $dbinfo;   # provides map from dbname to dbinfo
 public $errans = array('status'=>404, 'dicts'=>array());

 public function __construct() {
  include("dictinfowhich.php");
  $this->dbg=false;
  // $this->key = $parms->key;
  if ($dictinfowhich == "cologne") {
   $parent = "../..";
  }else {
   $parent = "..";
  }
  if (isset($_REQUEST['dbglob'])) {
   $dbname = $_REQUEST['dbglob'];
  }else {
   $dbname = "dbglob not set";
  }
  $this->dbinfo = array(
   "keydoc_glob"=>array(
          "dbname"=>"keydoc_glob",
          "sqlitefile"=>"$parent/hwnorm2/keydoc/keydoc_glob.sqlite",
          "tabname"=>"keydoc_glob",
          "tabid"=>"key"
   ),
   "keydoc_glob1"=>array(
          "dbname"=>"keydoc_glob1",
          "sqlitefile"=>"$parent/hwnorm2/keydoc/keydoc_glob1.sqlite",
          "tabname"=>"keydoc_glob1",
          "tabid"=>"key"
   )

  );
  $this->file_db = null;
  $this->status = false;
  $this->ans = $this->errans;
  if (! isset($this->dbinfo[$dbname])) {
   dbgprint($this->dbg,"Dalglob: unknown database: $dbname\n");
   return;
  }
  $dbinfo = $this->dbinfo[$dbname];
  $this->dbname = $dbname;
  $this->sqlitefile = $dbinfo["sqlitefile"];
  $this->tabname = $dbinfo["tabname"];
  $this->tabid = $dbinfo["tabid"];
  dbgprint($this->dbg,"Dalglob construct. sqlitefile={$this->sqlitefile}, tabname={$this->tabname}\n");
  $dbg=false;
  if (file_exists($this->sqlitefile)) {
   try {
    $this->file_db = new PDO('sqlite:' .$this->sqlitefile);
    $this->file_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    dbgprint($dbg,"dalglob.php: opened " . $this->sqlitefile . "\n");
    $this->status=true;
   } catch (PDOException $e) {
    $this->file_db = null;
    dbgprint($dbg,"Dalglob : Cannot open " . $this->sqlitefile . "\n");
    $this->status=false;
    return;
   }
  } else {
   $this->file_db = null;
   #dbgprint($dbg,"dal.php: Cannot open " . $this->sqlitefile . "\n");
   $this->status=false;
   return ;
  } 
  $parms = new Parm(); 
  $key = $parms->key;
  $keyin = $parms->keyin;
  #dbgprint(true,"keyin=$keyin, key=$key\n");
  $this->ans = $this->get1($key);
  return;
 }
 public function close() {
  if ($this->file_db) {
   $this->file_db = null;  //ref: //php.net/manual/en/pdo.connections.php
  }
 }
 public function get($sql) {
  $ansarr = array();
  if (!$this->file_db) {
   //"file_db is null for $this->sqlitefile.
   return $ansarr;
  }
  dbgprint($this->dbg,"Dalglob.get: sql=$sql\n");
  $result = $this->file_db->query($sql);
  if ($result == false) {
   return $ansarr;
  }
  
  foreach($result as $m) {
   $rec = array($m['key'],$m['data']);
   $ansarr[]=$rec;
  }
  return $ansarr; 

 }

 public function get1($key) {
  // Returns associative array for the records in dictionary with this key
  // 7/14/2020  Normalize the key before searching for it.
  //
  $keynorm = $this->normalize_key($key);
  $sql = "select * from {$this->tabname} where key='$keynorm'";
  $ansarr =  $this->get($sql);
  if (count($ansarr) == 0) {
   return $this->errans;
  }
  $rec = $ansarr[0];  // expect count($ansarr) == 1
  $data = $rec[1];
  if ($this->dbname == 'keydoc_glob') {
   $dicts = preg_split('|,|',$data);
  }else if ($this->dbname == 'keydoc_glob1') {
   $dicts = $this->parse_glob1($data,$keynorm);
  }
   return array('status'=>200, 'dicts'=>$dicts);
 }
 public function parse_glob1($data,$key) {
  #dbgprint(true,"parse_glob1: $data\n");
  $a = preg_split('|:|',$data);
  $ans = array();
  foreach($a as $a1) {
   $b = preg_split('|=|',$a1);
   $dict = $b[0];
   if (count($b) == 1) {
    $dockeys = array($key);
   }else {
    $c = preg_split('|,|',$b[1]);
    $dockeys = array();
    foreach($c as $c1) {
     if ($c1 == '*') {
      $dockeys[] = $key;
     }else {
      $dockeys[] = $c1;
     }
    }
   }
   $ans1 = array("dict"=>$dict, "dockeys"=>$dockeys);
   $ans[] = $ans1;
  }
  return $ans;
 }
 public function normalize_key($a){
 /* This function is consistent with the hwnorm1c.py version in
  sanskrit-lexicon/hwnorm2/keydoc/
 */
 $aM = function($b) {return preg_replace('/aM$/','a',$b);};
 $aH = function($b) {return preg_replace('/aH$/','a',$b);};
 $uH = function($b) {return preg_replace('/uH$/','u',$b);};
 $iH = function($b) {return preg_replace('/iH$/','i',$b);};
 $ttr_tr = function($b) {return preg_replace('/ttr/','tr',$b);};
 $ant_at = function($b) {return preg_replace('/ant$/','at',$b);};
 $normalize_key_C = function($b) {
  if (strpos($b,'C') == False) {return $b;}
  # X + C -> XcC  (X a vowel)
  $b1 = preg_replace('/([aAiIuUfFxXeEoO])C/','\1cC',$b);
  # X + cC -> XC  (X a consonant)
  $b2 = preg_replace('/([kKgGNcCjJYwWqQRtTdDnpPbBmyrlvhzSsHM])cC/','\1C',$b1);
  return $b2;
 };
 $rxx_rx = function($b) {return preg_replace('/([r])(.)\2/','\1\2',$b);};

$homorganic_nasal = function($b) {
  return preg_replace_callback('/(M)([kKgGNcCjJYwWqQRtTdDnpPbBm])/',
   function($matches) {
    #n = $matches[1] is  always M
    $c = $matches[2];
    $slp1_cmp1_helper_data = array(
    'k'=>'N','K'=>'N','g'=>'N','G'=>'N','N'=>'N',
    'c'=>'Y','C'=>'Y','j'=>'Y','J'=>'Y','Y'=>'Y',
    'w'=>'R','W'=>'R','q'=>'R','Q'=>'R','R'=>'R',
    't'=>'n','T'=>'n','d'=>'n','D'=>'n','n'=>'n',
    'p'=>'m','P'=>'m','b'=>'m','B'=>'m','m'=>'m'
    );
    $nasal = $slp1_cmp1_helper_data[$c];
    return ($nasal . $c);

   },
   $b);
};
/*
 $rxX_helper_data = array(
 'k'=>'K','g'=>'G',
 'c'=>'C','j'=>'J',
 'w'=>'W','q'=>'Q',
 't'=>'T','d'=>'D',
 'p'=>'P','b'=>'B'
 );

 $rxX_helper = function($matches) {
  $x = $matches[1];
  $X = $matches[2];
  if (array_key_exists($x,$rxX_helper_data) &&
      ($X == $$rxX_helper_data[$x])) {
   return 'r' . $X; 
  } else {
   # no change
   return 'r' . $x . $X;
  }
 };
*/

 $rxX_rx = function($b) {

  return preg_replace_callback('/r(.)(.)/',
 
  function ($matches) { // anonymous function
  $x = $matches[1];
  $X = $matches[2];
 $rxX_helper_data = array(
 'k'=>'K','g'=>'G',
 'c'=>'C','j'=>'J',
 'w'=>'W','q'=>'Q',
 't'=>'T','d'=>'D',
 'p'=>'P','b'=>'B'
 );

  if (array_key_exists($x,$rxX_helper_data)) {
   if($X == $rxX_helper_data[$x]) {
    return 'r' . $X;
   }
  }
  # no change
  return 'r' . $x . $X;
 }
 ,
   $b);
 };
 $rules = array(
  array('Mm',$homorganic_nasal),
  array('aM',$aM),
  array('aH',$aH),
  array('uH',$uH),
  array('iH',$iH),
  array('ttr',$ttr_tr),
  array('ant',$ant_at),
  array('cC',$normalize_key_C),
  array('rxx',$rxx_rx),
  array('rxX',$rxX_rx)
 );
 foreach($rules as $rule) {
  list($code,$f) = $rule;
  $b = $f($a);
  $a = $b;
 }
 return $a;
}
 public function unused_get3b($key,$max) {
 /*  This is not yet ready!!!
 returns an array of records, where 'key' is like $key
 The wildcards for sqlite are: 
   (ref=https://www.sqlitetutorial.net/sqlite-like/)
 The percent sign % wildcard matches any sequence of zero or more characters.
 The underscore _ wildcard matches any single character.
 Setting a pragma for case_sensitive
*/
  $pragma="PRAGMA case_sensitive_like=true;";
  $this->file_db->query($pragma);
  $sql = " select * from {$this->tabname} where key LIKE '$key' LIMIT $max";
  return $this->get($sql);
 }

}
?>
