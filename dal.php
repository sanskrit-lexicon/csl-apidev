<?php
/* dal.php  Apr 28, 2015 Multidictionary access to sqlite Databases
 June 4, 2015 - use pywork/html/Xhtml.sqlite
 May 10, 2015 - also allow use of web/sqlite/X.sqlite
*/

require_once('dictinfo.php');
require_once('dbgprint.php');
class Dal {
 public $dict;
 public $dictinfo;
 public $sqlitefile;
 public $file_db;
 public $dbg=false;
 public function __construct($dict) {
  $this->dict=strtolower($dict);
  $this->dictinfo = new DictInfo($dict);
  $year = $this->dictinfo->get_year();
  // 2017-06-02: Change webpath to use get_serverPath
  $webpath = $this->dictinfo->get_webPath();
  #$webpath = $this->dictinfo->get_serverPath();
  $this->sqlitefile_xml = "$webpath/sqlite/{$this->dict}.sqlite";
  $htmlpath = $this->dictinfo->get_htmlPath();
  $dbg=false;
  dbgprint($dbg,"dal.construct htmlpath = $htmlpath\n");
  $this->sqlitefile = "$htmlpath/{$this->dict}html.sqlite";
  dbgprint($dbg,"dal.construct sqlitefile = {$this->sqlitefile}\n");
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
  // connection to sqlitefile_xml: WHAT IS USAGE OF THIS? (07-17-2017)
  try {
   $this->file_db_xml = new PDO('sqlite:' .$this->sqlitefile_xml);
   $this->file_db_xml->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
   #echo "Dal: opened " . $this->sqlitefile_xml . "\n";
   $this->status=true;
  } catch (PDOException $e) {
   $this->file_db_xml = null;
   #echo "PDO exception=".$e."<br/>\n";
   #echo "<p>Dal ERROR. Cannot open sqlitefile_xml for dictionary $dict </p>\n";
   $this->status=false;
  }
 }
 public function close() {
  if ($this->file_db) {
   $this->file_db = null;  //ref: //php.net/manual/en/pdo.connections.php
  }
  if ($this->file_db_xml) {
   $this->file_db_xml = null;  //ref: //php.net/manual/en/pdo.connections.php
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
 public function get_xml($sql) {
  $ansarr = array();
  if (!$this->file_db_xml) {
   dbgprint($this->dbg, "file_db_xml is null. sqlitefile={$this->sqlitefile}\n");
   return $ansarr;
  }
  $result = $this->file_db_xml->query($sql);
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
 public function get1_xml($key) {
  // Returns associative array for the records in dictionary with this key
  $sql = "select * from {$this->dict} where key='$key' order by lnum";
  dbgprint($this->dbg, "get1_xml, sql=$sql\n");
  return $this->get_xml($sql);
 }
/* Alternate test version for mw
   Jul 19, 2015
*/
 public function get1_mwalt($key) {
 require_once("dal_get1_mwalt.php");
 #$dbg=true;
 #dbgprint($dbg,"Call dal.get1_mwalt($key)\n");
 $recs = dal_get1_mwalt($this,$key);
 #dbgprint($dbg,"Return dal.get1_mwalt($key)\n");
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
  // in mw, with L=99930.1, $lnum0 appears as if L=99930.1000000001
  // To guard against this, we round lnum0 to 3 decimal places.
  //  [This is consistent with the schema definition]
  $lnum0 = round($lnum0,3);
  $sql = "select * from {$this->dict} where (lnum < '$lnum0') order by lnum DESC LIMIT $max";
  return $this->get($sql);
 }
 public function get4b($lnum0,$max) {
  //  Used in listhier
  //  Used in listhier
  // in mw, with L=99930.1, $lnum0 appears as if L=99930.1000000001
  // To guard against this, we round lnum0 to 3 decimal places.
  //  [This is consistent with the schema definition]
  $lnum0 = round($lnum0,3);
  $sql = "select * from {$this->dict} where ('$lnum0' < lnum) order by lnum LIMIT $max";
  return $this->get($sql);
 }
/* get key by lnum 09-14-2018 */
 public function get5($lnum0) {
  // in mw, with L=99930.1, $lnum0 appears as if L=99930.1000000001
  // To guard against this, we round lnum0 to 3 decimal places.
  //  [This is consistent with the schema definition]
  $lnum0 = round($lnum0,3);
  $lnum1 = $lnum0 + 0.001;
  $sql = "select * from {$this->dict} where ('$lnum0' < lnum)and(lnum<'$lnum1') order by lnum LIMIT $max";
  return $this->get($sql);
 }

}

?>
