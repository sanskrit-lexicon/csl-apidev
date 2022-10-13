<?php
/* dictinfo.php
 June 4, 2015 get_htmlPath - sqlite file for html
 This code depends on the organization of files on the server,
 and specifically depends on the orgranization at Cologne sanskrit-lexicon.
 // VCP changed from 2013 to 2019 03-12-2021
*/
require_once('dbgprint.php');
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
       "VCP"=>"2019" , "VEI"=>"2014" , "WIL"=>"2014" , "YAT"=>"2014",
       "LAN"=>"2019","ARMH"=>"2020","PWKVN"=>"2020", "LRV"=>"2022");
 //static public $scanpath = "../..";  // scan dir.depends on loc of this file!
 //static public $scanpath = preg_replace('|/awork/apidev|','',__DIR__);
 #public $scanpath;
 #public $scanpath_server;
 public $dict,$dictstatus,$dicterr;
 public $dictupper;
 public $year;  
 public $english;
 public $sqlitedir;  // 07-10-2018 for Dalraw
 public $webpath, $webparent;
 public function __construct($dict) {
  #$this->scanpath_server = realpath(preg_replace('|/awork/apidev|','',__DIR__));
  #$this->scanpath = "../..";
  /* 04-17-2018 restate for XAMPP configuration */
  #$this->scanpath_server = dirname(__DIR__);
  #$this->scanpath = $this->scanpath_server; //"../..";
  $this->dict=strtolower($dict);
  $this->dictupper=strtoupper($dict);
  if (! isset(self::$dictyear[$this->dictupper])) {
   $this->dictstatus = 404;
   $this->dicterr = "Unknown dictionary code: $dict";
  }else {
   $this->dictstatus = 200;
   $this->dicterr = "";
  }
  if ((isset($_REQUEST['version'])) &&($_REQUEST['version'] == '1')) {
   // older version -- 2014 or 2013
   $this->year = self::$dictyear[$this->dictupper];
  }else {
   $this->year = '2020';
  }
  $this->english = in_array($this->dictupper,array("AE","MWE","BOR")); // boolean flag
  $this->webpath = $this->get_webPath();
  $this->webparent = realpath("{$this->webpath}/../");
  #dbgprint(true,"dictinfo: webpath = {$this->webpath}, webparent={$this->webparent}\n");
  $this->sqlitedir = "{$this->webpath}/sqlite";
  }
 
 public function get_year() {
  return $this->year;
 }
 public function get_cologne_weburl() {
  // 04-17-2018
  // used by servepdf.php
  // Cologne scan directory 
  $cologne_scandir = "//www.sanskrit-lexicon.uni-koeln.de/scans";
  $path = $cologne_scandir . "/{$this->dictupper}Scan/{$this->year}/web";
  return $path;
 }
 public function get_webPath() {
  include("dictinfowhich.php");
  $dbg=false;
  dbgprint($dbg,"dictinfo.get_webPath: dictinfowhich=$dictinfowhich\n");
  if ($dictinfowhich == "xampp") {
   /* 01-05-2021  reconstruct for XAMPP 
    This makes an assumption regarding location of the directory of this
    file, namely that it is a sibling of the dictionary directories.
    Example. Dict = mw.
    We assume csl-apidev directory is a sibling of mw.
    and that path to web directory is mw/web
   */
   $dirpfx = $this->get_parent_dirpfx("csl-apidev"); # ends in /
   $path = "$dirpfx" . "csl-apidev/../{$this->dict}/web";
   dbgprint($dbg,"dictinfo.get_webPath. 1 path = $path\n");
  }else {
   /* assume ($dictinfowhich == "cologne")
   Example.  csl-apidev at Cologne is a sibling of MWScan
   Currently (01-05-2021) csl-apidev is a symlink.
   The 'real' name of this is awork/apidev
   */
   #$dirpfx = $this->get_parent_dirpfx("csl-apidev"); # ends in /
   #$dirpfx = $this->get_parent_dirpfx("awork"); # ends in /.  doesn't work
   $dirpfx = $this->get_parent_dirpfx("apidev"); # ends in /.  symlink doesn't work
   # dirpfx now is path to awork  (it is a path string that ends in `awork/`)
   $path = $dirpfx . "../". "{$this->dictupper}Scan/{$this->year}/web";
   dbgprint($dbg,"dictinfo. dirpfx = $dirpfx\n");
   dbgprint($dbg,"dictinfo.get_webPath Cologne. 1 path = $path\n");
  }
  return $path;
 }
 public function get_parent_dirpfx($base) {
  $dirpfx = "../../"; // apidev  Not portable
  #$ds = DIRECTORY_SEPARATOR;
  for($i=1;$i<10;$i++) {
   $d = dirname(__FILE__,$i);
   $b = basename($d);
   if ($b == $base) {
    $d = dirname(__FILE__,$i+1);
    $dirpfx = "$d/";
    break;
   }
  }
  return $dirpfx;
 }

 public function get_pdfpages_url() {
  /* Assume this method called only from servepdf, which is in web/webtc folder
  */
  $dbg=false;
  include("dictinfowhich.php");
  $cologne_url = $this->get_cologne_pdfpages_url();
  if ($dictinfowhich == 'cologne') {
   return $cologne_url;
  }
  // otherwise, $dictinfowhich == 'xampp'
  // Try relative url, either in web directory, or parent of web directory
  // Use relative url if it is a non-empty directory.
  $testpaths = array ( "../scans/{$this->dict}/pdfpages","../{$this->dict}/pdfpages", "../{$this->dict}/web/pdfpages"   );
  foreach($testpaths as $testpath) {
   if (!$this->dir_is_empty($testpath)) {
    return $testpath;
   }
  }
  // Use Cologne url as a fallback
  return $cologne_url;
 } 
 public function dir_is_empty($dir) {
  /* ref: https://stackoverflow.com/questions/7497733/how-can-i-use-php-to-check-if-a-directory-is-empty
   Note this is just a function. Put into this class for convenience of this
   application.  Currently used only by get_pdfpages_dir()
  */
  if (! is_dir($dir)) { 
   return TRUE; 
  }
  $handle = opendir($dir);
  while (false !== ($entry = readdir($handle))) {
    if ($entry != "." && $entry != "..") {
      closedir($handle);
      return FALSE;
    }
  }
  closedir($handle);
  return TRUE;
 }
public function get_cologne_pdfpages_url() {
 /* These urls are current as of 08-31-2019
 */
 $cologne_pdfpages_urls = array(
  "ACC"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/ACCScan/2014/web/pdfpages" ,
  "AE"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/AEScan/2014/web/pdfpages" ,
  "AP"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/APScan/2014/web/pdfpages" ,
  "AP90"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/AP90Scan/2014/web/pdfpages" ,
  "BEN"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/BENScan/2014/web/pdfpages" ,
  "BHS"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/BHSScan/2014/web/pdfpages" ,
  "BOP"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/BOPScan/2014/web/pdfpages" ,
  "BOR"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/BORScan/2014/web/pdfpages" ,
  "BUR"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/BURScan/2013/web/pdfpages" ,
  "CAE"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/CAEScan/2014/web/pdfpages" ,
  "CCS"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/CCSScan/2014/web/pdfpages" ,
  "GRA"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/GRAScan/2014/web/pdfpages" ,
  "GST"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/GSTScan/2014/web/pdfpages" ,
  "IEG"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/IEGScan/2014/web/pdfpages" ,
  "INM"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/INMScan/2013/web/pdfpages" ,
  "KRM"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/KRMScan/2014/web/pdfpages" ,
  "MCI"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/MCIScan/2014/web/pdfpages" ,
  "MD"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/MDScan/2014/web/pdfpages" ,
  #"MW"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/MWScan/2014/web/pdfpages" ,
  "MW"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/MWScan/MWScanpdf" ,
  "MW72"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/MW72Scan/2014/web/pdfpages" ,
  "MWE"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/MWEScan/2013/web/pdfpages" ,
  "PD"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/PDScan/2014/web/pdfpages" ,
  "PE"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/PEScan/2014/web/pdfpages" ,
  "PGN"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/PGNScan/2014/web/pdfpages" ,
  "PUI"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/PUIScan/2014/web/pdfpages" ,
  "PWG"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/PWGScan/2013/web/pdfpages" ,
  "PW"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/PWScan/2014/web/pdfpages" ,
  "SCH"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/SCHScan/2014/web/pdfpages" ,
  "SHS"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/SHSScan/2014/web/pdfpages" ,
  "SKD"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/SKDScan/2013/web/pdfpages" ,
  "SNP"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/SNPScan/2014/web/pdfpages" ,
  "STC"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/STCScan/2013/web/pdfpages" ,
  "VCP"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/VCPScan/2019/web/pdfpages" ,
  "VEI"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/VEIScan/2014/web/pdfpages" ,
  "WIL"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/WILScan/2014/web/pdfpages" ,
  "YAT"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/YATScan/2014/web/pdfpages" ,
  "LAN"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/LANScan/2019/web/pdfpages" ,
  "ARMH"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/ARMHScan/2020/web/pdfpages" ,
  "PWKVN"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/PWScan/2014/web/pdfpages" ,
  "LRV"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/LRVScan/2022/pdfpages" ,
 );
 $url = $cologne_pdfpages_urls[$this->dictupper];
 return $url;
 }

}
?>
