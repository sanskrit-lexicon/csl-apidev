"""genhtml.py
   June 6, 2015
   For a given dictionary code X,
   (a) create the directory scans/XScan/yyyy/pywork/html (if it doesn't exist)
   (b) Populate it with redo.sh, def.sql, make_html.php

"""
import sys,re
import codecs
import os.path,time
import os
from shutil import copyfile 
from string import Template

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

def genhtml(code):
 codelow = code.lower()  # lower case
 if not (code in dictyear):
  print "genhtml ERROR: Invalid dictionary code:",code
  exit(1)
 year = dictyear[code]
 dirmain = "%sScan/%s" %(code,year)
 # Take into account relative location of this program file
 dirbase = dirin = "../../../" + dirmain
 pywork = "%s/pywork" % dirbase
 if not os.path.exists(pywork):
  print "genhtml ERROR: Bad pywork directory:",pywork
  exit(1)
 print pywork,"directory exists"
 htmldir = "%s/html" % pywork
 if os.path.exists(htmldir):
  print "html directory exists",htmldir
 else:
  os.mkdir(htmldir)
  if os.path.exists(htmldir):
   print "html directory created",htmldir
  else:
   print "genhtml ERROR: Could not create html directory",htmldir
   exit(1)
 # now we have htmldir available.
 # 1. copy makeassets/make_html.php to htmldir
 # June 7, 2015.  Use a variant for YAT, MD
 if code in ['YAT','MD']:
  inpath = "makeassets/make_html_YAT_MD.php"
 else:
  inpath = "makeassets/make_html.php"
 outpath = "%s/make_html.php" % htmldir
 print "Copying %s to %s" %(inpath,outpath)
 copyfile(inpath,outpath)
 # 2. Using string template to create redo.sh for this dict
 # ref: //stackoverflow.com/questions/6385686/python-technique-or-simple-templating-system-for-plain-text-output
 inpath = "makeassets/template_redo.sh"
 outpath = "%s/redo.sh" % htmldir
 with open(inpath,"r") as f:
  src = Template(f.read())
 d = {"dcode":codelow}
 result = src.substitute(d)
 with open(outpath,"w") as f:
  f.write("%s" % result)
 print "created", outpath
 #os.system('cat %s' %outpath) # dbg
 # 3. Using string template to create def.sql for this dict
 inpath = "makeassets/template_def.sql"
 outpath = "%s/def.sql" % htmldir
 with open(inpath,"r") as f:
  src = Template(f.read())
 d = {"dcode":codelow}
 result = src.substitute(d)
 with open(outpath,"w") as f:
  f.write("%s" % result)
 print "created", outpath
 #os.system('cat %s' %outpath) # dbg

if __name__=="__main__":
 code = sys.argv[1]  # dictionary code, upper case
 genhtml(code)
