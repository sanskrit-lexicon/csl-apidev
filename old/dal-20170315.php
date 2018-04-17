<?php
/* dal.php  Apr 28, 2015 Multidictionary access to sqlite Databases
 June 4, 2015 - use pywork/html/Xhtml.sqlite
*/

require_once('dictinfo.php');

class Dal {
 public $dict;
 public $dictinfo;
 public $sqlitefile;
 public $file_db;
 public function __construct($dict) {
  $this->dict=strtolower($dict);
  $this->dictinfo = new DictInfo($dict);
  $year = $this->dictinfo->get_year();
  $webpath = $this->dictinfo->get_webPath();
  $htmlpath = $this->dictinfo->get_htmlPath();
  #$this->sqlitefile = "$webpath/sqlite/{$this->dict}.sqlite";
  $this->sqlitefile = "$htmlpath/{$this->dict}html.sqlite";
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
 }
 public function get($sql) {
  $ansarr = array();
  if (!$this->file_db) {
   //if (True) {echo "file_db is null\n"; echo $this->sqlitefile."\n";}
   return $ansarr;
  }
  $result = $this->file_db->query($sql);
  foreach($result as $m) {
   $rec = array($m['key'],$m['lnum'],$m['data']);
   $ansarr[]=$rec;
  }
  return $ansarr; 
 }
 public function get1($key) {
  // Returns associative array for the records in dictionary with this key
  $sql = "select * from {$this->dict} where key='$key' order by lnum";
  return $this->get($sql);
 }
/* Alternate test version for mw
   Jul 19, 2015
*/
 public function get1_mwalt($key) {
 require_once("dal_get1_mwalt.php");
 $recs = dal_get1_mwalt($this,$key);
  return $recs;
 }

 public function get2($L1,$L2) {
  //  Used in listhier
  // returns an array of records, one for each L-value in the range
  // $L1 <= $L <= $L2
  // each record is an array with three elements: key,lnum,data
  $sql="select * from {$this->dict} where  $L1 <= lnum and lnum <= $L2  order by lnum"; 
  return $this->get($sql);
 }
 public function get3($key) {
  // returns an array of records, which start like $key
  $sql = "select * from {$this->dict} where key LIKE '$key%' order by lnum";
  return $this->get($sql);
 }
 public function get3a($key,$max) {
  // returns an array of records, which start like $key
  // Setting a pragma must for case_sensitive
  $pragma="PRAGMA case_sensitive_like=true;";
  $this->file_db->query($pragma);
  $sql = " select * from {$this->dict} where key LIKE '$key%' order by lnum LIMIT $max";
  return $this->get($sql);
 }
 public function get4a($lnum0,$max) {
  //  Used in listhier
  $sql = "select * from {$this->dict} where (lnum < '$lnum0') order by lnum DESC LIMIT $max";
  return $this->get($sql);
 }
 public function get4b($lnum0,$max) {
  //  Used in listhier
  $sql = "select * from {$this->dict} where ('$lnum0' < lnum) order by lnum LIMIT $max";
  return $this->get($sql);
 }

}

?>
