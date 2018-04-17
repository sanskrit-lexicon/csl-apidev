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
  } catch (PDOException $e) {
   $this->file_db = null;
   #echo "PDO exception=".$e."<br/>\n";
   echo "<p>Dal ERROR. Cannot open sqlitefile for dictionary $dict </p>\n";
  }
 }
 public function close() {
  if ($this->file_db) {
   $this->file_db = null;  //ref: http://php.net/manual/en/pdo.connections.php
  }
 }
 public function get1($key) {
  // Returns associative array for the records in dictionary with this key
  $ansarr = array();
  if (!$this->file_db) {
   //if (True) {echo "file_db is null\n"; echo $this->sqlitefile."\n";}
   return $ansarr;
  }
  $sql = "select * from {$this->dict} where key='$key' order by lnum";
  $result = $this->file_db->query($sql);
  foreach($result as $m) {
   $rec = array($m['key'],$m['lnum'],$m['data']);
   $ansarr[]=$rec;
  }
  return $ansarr;
 }
}

?>
