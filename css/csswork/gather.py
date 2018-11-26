"""css.py for css  June 5, 2015
 python css.py cssdir
 Examples:
 python css.py webtc
 python css.py webtc1
 python css.py webtc2
 python css.py mobile


"""
import sys,re
import codecs
import os.path,time
import os
from shutil import copyfile 
# dictyear has all dictionary codes, with the 'year'.
# This 'year' is required to locate the files
# This is a Python dictionary data structure, quite like a PHP associative array
dictyear={"ACC":"2014" , "AE":"2014" , "AP":"2014" , "AP90":"2014",
       "BEN":"2014" , "BHS":"2014" , "BOP":"2014" , "BOR":"2014",
       "BUR":"2013" , "CAE":"2014" , "CCS":"2014" , "GRA":"2014",
       "GST":"2014" , "IEG":"2014" , "INM":"2013" , "KRM":"2014",
       "MCI":"2014" , "MD":"2014" , "MW":"2014" , "MW72":"2014",
       "MWE":"2013" , "PD":"2014" , "PE":"2014" , "PGN":"2014",
       "PUI":"2014" , "PWG":"2013" , "PW":"2014" , "SCH":"2014",
       "SHS":"2014" , "SKD":"2013" , "SNP":"2014" , "STC":"2013",
       "VCP":"2013" , "VEI":"2014" , "WIL":"2014" , "YAT":"2014"}
def css(cssdir):
 #fout = codecs.open(cssdir,"w","utf-8")
 n=0
 #dictyear = {"ACC":"2014" , "AE":"2014" , "AP":"2014"} # debug
 for code in dictyear:
  year = dictyear[code]
  n = n + 1
  dirmain = "%sScan/%s" %(code,year)
  # Take into account relative location of this program file
  dirbase = dirin = "../../../../" + dirmain + "/web/" + cssdir
  #print dirbase
  # ref //stackoverflow.com/questions/3207219/how-to-list-all-files-of-a-directory-in-python
  cssfiles = []
  for root, dirs, files in os.walk(dirbase):
   cssfiles = [f for f in files if f.endswith('.css')]
   break
  for name in cssfiles:
   if name in ['serveimg.css','keyboard.css']:
    continue
   inpath = os.path.join(dirbase, name)
   #put output file into cssfiles subdirectory
   outpath = "cssfiles/%s_%s_%s" %(code,cssdir,name)
   print("cp %s %s" %(inpath,outpath))
   copyfile(inpath,outpath)
  continue
 return

if __name__=="__main__":
 cssdir = sys.argv[1] # output path
 css(cssdir)

