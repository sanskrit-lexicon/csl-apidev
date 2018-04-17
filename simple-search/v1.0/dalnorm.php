<?php
/* dalnorm.php  Jul 17, 2017  access to hwnorm1c.sqlite
  Oct 12, 2017 Revised in agreement with changes to hwnorm1c.py
  Oct 23, 2017 Revised in agreement with changes to hwnorm1c.py: 'C' words
*/
$dirpfx = "../../";
require_once($dirpfx . "dbgprint.php");

class Dalnorm {
 public $sqlitefile; // the sqlite file path
 public $file_db;  // the sqlite connection object
 public $dbg=false;
 public $status;
 public function __construct($dict,$path) {
  $this->dict=strtolower($dict);
  $this->path = $path;
  $this->sqlitefile = "$path/{$this->dict}.sqlite";
  // connection to sqlitefile
  try {
   $this->file_db = new PDO('sqlite:' .$this->sqlitefile);
   $this->file_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
   #echo "Dal: opened " . $this->sqlitefile . "\n";
   $this->status=true;
  } catch (PDOException $e) {
   $this->file_db = null;
   #echo "PDO exception=".$e."<br/>\n";
   #echo "<p>Dal ERROR. Cannot open sqlitefile for dictionary $dict </p>\n";
   $this->status=false;
  }

 }
 public function close() {
  if ($this->file_db) {
   $this->file_db = null;  //ref: http://php.net/manual/en/pdo.connections.php
  }
  if ($this->file_db_xml) {
   $this->file_db_xml = null;  //ref: http://php.net/manual/en/pdo.connections.php
  }
 }
 public function get($sql) {
  $ansarr = array();
  if (!$this->file_db) {
   //if (True) {echo "file_db is null\n"; echo $this->sqlitefile."\n";}
   return $ansarr;
  }
  $result = $this->file_db->query($sql);
  foreach($result as $m) {
   // hwnorm1c has only key and data, no lnum. We could return '0' for lnum?
   //$rec = array($m['key'],$m['lnum'],$m['data']);
   $rec = array($m['key'],$m['data']);
   $ansarr[]=$rec;
  }
  //if (True) {echo "get: ansarr len =" . count($ansarr) . "\n";}
  return $ansarr; 
 }
 public function get1($key) {
  // Returns associative array for the records in dictionary with this key
  $sql = "select * from {$this->dict} where key='$key'" ;
  return $this->get($sql);
 }
 public function get1norm($key) {
  // normalize the key, then return data for the normalized key
  $normkey = $this->normalize($key);
  return $this->get1($normkey);
 }
 
 public function normalize($key) {
  $a = $key;
  #1. Use  homorganic nasal rather than anusvara
  $dbg=false;
  //$dbg=true;
  $a = preg_replace_callback('/(M)([kKgGNcCjJYwWqQRtTdDnpPbBm])/',
    function ($matches) {
     $slp1_cmp1_helper_data = array(
      'k'=>'N','K'=>'N','g'=>'N','G'=>'N','N'=>'N',
      'c'=>'Y','C'=>'Y','j'=>'Y','J'=>'Y','Y'=>'Y',
      'w'=>'R','W'=>'R','q'=>'R','Q'=>'R','R'=>'R',
      't'=>'n','T'=>'n','d'=>'n','D'=>'n','n'=>'n',
      'p'=>'m','P'=>'m','b'=>'m','B'=>'m','m'=>'m'
     );
     $c = $matches[2];  // following constant
     $nasal = $slp1_cmp1_helper_data[$c];
     //$dbg1=true;
     //dbgprint($dbg1,"Dalnorm.normalize_nasal: chk1: c=$c, nasal=$nasal\n");
     return ($nasal . $c);
    },
    $a);
  if ($dbg) { // dbg
   if ($a != $key) {
    dbgprint($dbg,"Dalnorm.normalize anusvara chk: $key -> $a\n");
   }
  }
 #2. normalize so that 'rxx' is 'rx' (similarly, fxx is fx)
 #   discard fxx is fx rule
 #$a = preg_replace('|([rf])(.)\\2|','\\1\\2',$a);
 $a = preg_replace('|([r])(.)\\2|','\\1\\2',$a);
 #2-asp. normalize so that 'rxX' -> 'rX', where X is aspirated form of x
 #a = re.sub(r'r(.)(.)',rxX_helper,a)
 $a = preg_replace_callback('/r(.)(.)/',
    function ($matches) {
     $rxX_helper_data = array(
      'k'=>'K','g'=>'G',
      'c'=>'C','j'=>'J',
      'w'=>'W','q'=>'Q',
      't'=>'T','d'=>'D',
      'p'=>'P','b'=>'B'
     );
     $x = $matches[1];
     $X = $matches[2];
     if (isset($rxX_helper_data[$x]) && ($X == $rxX_helper_data[$x])) {
      return ('r' . $X);
     }else {
      return ('r' . $x . $X);
     }
    },
    $a);
  
 #3. ending 'aM' is 'a' (Apte)
 //$a = preg_replace('|aM$|','a',$a);
 #4. ending 'aH' is 'a' (Apte)
 $a = preg_replace('|aH$|','a',$a);
 #4a. ending 'uH' is 'u' (Apte)
 $a = preg_replace('|uH$|','u',$a);
 #4b. ending 'iH' is 'i' (Apte)
 $a = preg_replace('|iH$|','i',$a);
 #5. 'tt' is 't' (pattra v. patra)
 $a = preg_replace('|ttr|','tr',$a);
 #6. ending 'ant' is 'at'
 $a = preg_replace('|ant$|','at',$a);
 #7. 'cC' is 'C'
 #$a = preg_replace('|cC|','C',$a);
 # Revised 10-23-2017
 # X + C -> XcC  (X a vowel)
 $a = preg_replace('|([aAiIuUfFxXeEoO])C|','\\1cC',$a);
 # X + cC -> XC  (X a consonant)
 $a = preg_replace('|([kKgGNcCjJYwWqQRtTdDnpPbBmyrlvhzSsHM])cC|','\\1C',$a);
 dbgprint($dbg,"Dalnorm.normalize chk: $key -> $a\n");
 return $a;
 }
}
?>
