<?php
/* dictinfo.php
 June 4, 2015 get_htmlPath - sqlite file for html
 This code depends on the organization of files on the server,
 and specifically depends on the orgranization at Cologne sanskrit-lexicon.
 // VCP changed from 2013 to 2019 03-12-2021
 // FRI  02-01-202r
*/
require_once('dbgprint.php');
class DictInfo {  
 static public $dictyear=
   array("ACC"=>"2020" , "AE"=>"2020" , "AP"=>"2020" , "AP90"=>"2020",
       "BEN"=>"2020" , "BHS"=>"2020" , "BOP"=>"2020" , "BOR"=>"2020",
       "BUR"=>"2020" , "CAE"=>"2020" , "CCS"=>"2020" , "GRA"=>"2020",
       "GST"=>"2020" , "IEG"=>"2020" , "INM"=>"2020" , "KRM"=>"2020",
       "MCI"=>"2020" , "MD"=>"2020" , "MW"=>"2020" , "MW72"=>"2020",
       "MWE"=>"2020" , "PD"=>"2020" , "PE"=>"2020" , "PGN"=>"2020",
       "PUI"=>"2020" , "PWG"=>"2020" , "PW"=>"2020" , "SCH"=>"2020",
       "SHS"=>"2020" , "SKD"=>"2020" , "SNP"=>"2020" , "STC"=>"2020",
       "VCP"=>"2020" , "VEI"=>"2020" , "WIL"=>"2020" , "YAT"=>"2020",
       "LAN"=>"2020","ARMH"=>"2020","PWKVN"=>"2020", "LRV"=>"2022",
       "ABCH"=>"2023", "ACPH"=>"2023", "ACSJ"=>"2023","FRI"=>"2025",
       );
 static public $dictyear_older=
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
   $this->year = self::$dictyear_older[$this->dictupper];
  }else {
   // $this->year = '2020';  
   $this->year = self::$dictyear[$this->dictupper]; // 
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
  "ACC"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/ACCScan/ACCScanpdf" ,
  "AE"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/AEScan/AEScanpdf" ,
  "AP"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/APScan/APScanpdf" ,
  "AP90"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/AP90Scan/AP90Scanpdf" ,
  "BEN"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/BENScan/BENScanpdf" ,
  "BHS"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/BHSScan/BHSScanpdf" ,  
  "BOP"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/BOPScan/BOPScanpdf" ,
  "BOR"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/BORScan/BORScanpdf" ,
  "BUR"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/BURScan/BURScanpdf" ,
  "CAE"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/CAEScan/CAEScanpdf" ,
  "CCS"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/CCSScan/CCSScanpng" ,  
  "GRA"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/GRAScan/GRAScanpdf" ,
  "GST"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/GSTScan/GSTScanpdf" ,
  "IEG"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/IEGScan/IEGScanpdf" ,
  "INM"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/INMScan/INMScanpdf" ,
  "KRM"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/KRMScan/KRMScanpdf" ,
  "MCI"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/MCIScan/MCIScanpdf" , 
  "MD"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/MDScan/MDScanjpg" ,
  "MW"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/MWScan/MWScanpdf" ,
  "MW72"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/MW72Scan/MW72Scanpdf" ,
  "MWE"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/MWEScan/MWEScanpdf" ,
  "PD"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/PDScan/PDScanpdf" ,
  "PE"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/PEScan/PEScanpdf" ,
  "PGN"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/PGNScan/PGNScanpdf" ,
  "PUI"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/PUIScan/PUIScanpdf" ,
  "PWG"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/PWGScan/PWGScanpdf" ,
  "PW"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/PWScan/PWScanpng" ,
  "SCH"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/SCHScan/SCHScanpdf" ,
   "SHS"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/SHSScan/SHSScanpdf" ,
  "SKD"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/SKDScan/SKDScanpdf" ,
  "SNP"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/SNPScan/SNPScanpdf" ,
  
  "STC"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/STCScan/STCScanpdf" ,
  "VCP"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/VCPScan/VCPScanpdf" ,
  "VEI"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/VEIScan/VEIScanpdf" ,
  "WIL"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/WILScan/WILScanjpg" ,
  "YAT"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/YATScan/YATScanpdf" ,
  "LAN"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/LANScan/LANScanpdf" ,
  "ARMH"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/ARMHScan/ARMHScanpdf" ,
  "PWKVN"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/PWScan/PWScanpng" ,
  "LRV"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/LRVScan/LRVScanpdf" ,
  "ABCH"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/ABCHScan/pdfpages" ,
  "ACPH"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/ACPHScan/pdfpages" ,
  "ACSJ"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/ACSJScan/pdfpages" ,
  "FRI"=>"//www.sanskrit-lexicon.uni-koeln.de/scans/FRIScan/pdfpages",
 );
 $url = $cologne_pdfpages_urls[$this->dictupper];
 return $url;
 }

}
?>
