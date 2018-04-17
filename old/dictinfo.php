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
 static public $scanpath = "../..";  // scan dir.depends on loc of this file!
 public $dict;
 public $dictupper;
 public $year;  
 public $english;
 public function __construct($dict) {
  $this->dict=strtolower($dict);
  $this->dictupper=strtoupper($dict);
  $this->year = self::$dictyear[$this->dictupper];
  $this->english = in_array($this->dictupper,array("AE","MWE","BOR")); // boolean flag
 }
 
 public function get_year() {
  return $this->year;
 }

 public function get_webPath() {
  $path = self::$scanpath . "/{$this->dictupper}Scan/{$this->year}/web";
  return $path;
 }
 public function get_htmlPath() {
  $path = self::$scanpath . "/{$this->dictupper}Scan/{$this->year}/pywork/html";
  return $path;
 }
  
}
?>


