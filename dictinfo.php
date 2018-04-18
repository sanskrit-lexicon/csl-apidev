<?php
/* dictinfo.php
 June 4, 2015 get_htmlPath - sqlite file for html
 This code depends on the organization of files on the server,
 and specifically depends on the orgranization at Cologne sanskrit-lexicon.
*/
class DictInfo {
 static public $dictyear=
   array("ACC"=>"2014" , "AE"=>"2014" , "AP"=>"2014" , "AP90"=>"2014",
       "BEN"=>"2014" , "BHS"=>"2014" , "BOP"=>"2014" , "BOR"=>"2014",
       "BUR"=>"2013" , "CAE"=>"2014" , "CCS"=>"2014" , "GRA"=>"2014",
       "GST"=>"2014" , "IEG"=>"2014" , "INM"=>"2013" , "KRM"=>"2014",
       "MCI"=>"2014" , "MD"=>"2014" , "MW"=>"2014" , "MW72"=>"2014",
       "MWE"=>"2013" , "PD"=>"2014" , "PE"=>"2014" , "PGN"=>"2014",
       "PUI"=>"2014" , "PWG"=>"2013" , "PW"=>"2014" , "SCH"=>"2014",
       "SHS"=>"2014" , "SKD"=>"2013" , "SNP"=>"2014" , "STC"=>"2013",
       "VCP"=>"2013" , "VEI"=>"2014" , "WIL"=>"2014" , "YAT"=>"2014");
 //static public $scanpath = "../..";  // scan dir.depends on loc of this file!
 //static public $scanpath = preg_replace('|/awork/apidev|','',__DIR__);
 public $scanpath;
 public $scanpath_server;
 public $dict;
 public $dictupper;
 public $year;  
 public $english;
 public function __construct($dict) {
  $this->scanpath_server = realpath(preg_replace('|/awork/apidev|','',__DIR__));
  $this->scanpath = "../..";
  /* 04-17-2018 restate for XAMPP configuration */
  $this->scanpath_server = dirname(__DIR__);
  #echo "scanpath_server={$this->scanpath_server}<br/>";
  $this->scanpath = $this->scanpath_server; //"../..";
  
  /*
  $this->scanpath = preg_replace('|[.]rrz|','rrz',$this->scanpath); // test?
  //$this->scanpath = preg_replace('|[.]rrz|','rrz',$this->scanpath);
  require_once('dbgprint.php');
  dbgprint(true,"__DIR__= {__DIR__}\n");
  dbgprint(true,"scanpath now = {$this->scanpath}\n");
  $temp = dirname(__FILE__);
  dbgprint(true,"temp=$temp\n");
  $temp1 = realpath("../..");
  dbgprint(true,"temp1=$temp1\n");
  //$this->scanpath = "../..";
  */
  $this->dict=strtolower($dict);
  $this->dictupper=strtoupper($dict);
  $this->year = self::$dictyear[$this->dictupper];
  $this->english = in_array($this->dictupper,array("AE","MWE","BOR")); // boolean flag
 }
 
 public function get_year() {
  return $this->year;
 }
 public function get_cologne_webPath() {
  // 04-17-2018
  // used by servepdf.php
  // Cologne scan directory 
  $cologne_scandir = "http://www.sanskrit-lexicon.uni-koeln.de/scans";
  $path = $cologne_scandir . "/{$this->dictupper}Scan/{$this->year}/web";
  return $path;
 }
 public function get_webPath() {
  // $path = self::$scanpath . "/{$this->dictupper}Scan/{$this->year}/web";
  #$path = $this->scanpath . "/{$this->dictupper}Scan/{$this->year}/web";
  /* 04-17-2018  reconstruct for XAMPP */
  $path = $this->scanpath . "/{$this->dict}/web";
  $path = realpath($path); 
  #echo "webPath=$path<br/>";
  //dbgprint(true,"get_webPath: $path\n");
  return $path;
 }
 public function get_serverPath() {
  /* For other php functions to access file system */
  // $path = self::$scanpath . "/{$this->dictupper}Scan/{$this->year}/web";
  #$path = $this->scanpath_server . "/{$this->dictupper}Scan/{$this->year}/web";
  // 04-17-2018  for XAMPP
  $path = $this->get_webPath();
  //dbgprint(true,"get_webPath: $path\n");
  return $path;
 }
 public function get_htmlPath() {
  //$path = self::$scanpath . "/{$this->dictupper}Scan/{$this->year}/pywork/html";
    #$path = $this->scanpath . "/{$this->dictupper}Scan/{$this->year}/pywork/html";
  // 04-17-2018  for XAMPP
  $path = $this->scanpath . "/{$this->dict}/pywork/html";
  $path = realpath($path);
  #echo "htmlPath=$path</br>";
  return $path;
 }
  
}
?>


