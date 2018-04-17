"""redoall.py
   June 6, 2015
   Does genhtml and redohtml for all dictionaries
   Usage:
   python redoall.py > redoall_log.txt
   June 26, 2015:
    a. Does NOT do genhtml  
      (These have already been done - Some, notably MW, have been changed,
       so genhtml would generate the wrong files.  Probably only MW in this camp.)
    b. Does a time comparison between X.xml and Xhtml.sqlite, and only
       remakes xhtml.sqlite if x.xml is newer.

"""
import sys,re
import codecs
import os.path,time
import os
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
def getdirs(code):
 if not (code in dictyear):
  print "getdirs ERROR: Invalid dictionary code:",code
  exit(1)
 year = dictyear[code]
 dirmain = "%sScan/%s" %(code,year)
 # Take into account relative location of this program file
 dirbase = dirin = "../../../" + dirmain
 pywork = "%s/pywork" % dirbase
 if not os.path.exists(pywork):
  print "getdirs ERROR: Bad pywork directory:",pywork
  exit(1)
 print pywork,"directory exists"
 htmldir = "%s/html" % pywork
 if not os.path.exists(htmldir):
  print "getdirs ERROR: html directory does not exist",htmldir
  exit(1)
 return (pywork,htmldir)

def xmlNewerP(code):
 """
 """
 codelow = code.lower()  # lower case
 (pywork,htmldir) = getdirs(code)
 xmlpath = "%s/%s.xml" %(pywork,codelow)
 htmlpath = "%s/%shtml.sqlite" %(htmldir,codelow)
 xmltime = os.path.getmtime(xmlpath)
 htmltime = os.path.getmtime(htmlpath)
 flag = (xmltime > htmltime)
 xmltimeshow = time.ctime(xmltime)
 htmltimeshow = time.ctime(htmltime)
 
 out = "%s   xml %s %s" %(code,xmltimeshow,xmlpath)
 os.system('echo %s' % out)
 out = "%s  html %s %s" %(code,htmltimeshow,htmlpath)
 os.system('echo %s' % out)
 return flag

def main():
 codes = dictyear.keys()
 #skipcodes = ["MW72" , "AP", "AP90", "VCP"]
 skipcodes = []
 codes = sorted(codes) # alphabetical order
 #print codes
 #return
 for code in codes:
  if code in skipcodes:
   os.system('echo "skipping %s"' % code)
   continue
  os.system('echo "BEGIN redoall %s"' % code)
  os.system('echo "NOT doing genhtml for %s"' % code)
  #os.system('python genhtml.py %s' % code)
  flag = xmlNewerP(code)
  if flag:
   os.system('echo "DOING redohtml for %s"' % code)
   os.system('python redohtml.py %s' % code)
  else:
   os.system('echo "redohtml not required for %s"' % code)
  os.system('echo "END redoall %s"' % code)
  os.system('echo ""')
if __name__=="__main__":
 #code = sys.argv[1]  # dictionary code, upper case
 #redohtml(code)
 main()
