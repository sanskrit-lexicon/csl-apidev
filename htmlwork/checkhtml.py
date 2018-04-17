"""checkhtml.py
   June 7 2015
   check that the html files have been created properly for all dictionaries.
   Do this by comparing the file size of X.sqlite and Xhtml.sqlite.
   Usage: python checkhtml.py > checkhtml.txt
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

mb = float(2**20) # number of bytes in a megabyte
def checkhtml(code):
 if not (code in dictyear):
  print "redohtml ERROR: Invalid dictionary code:",code
  exit(1)
 year = dictyear[code]
 dirmain = "%sScan/%s" %(code,year)
 # Take into account relative location of this program file
 dirbase = dirin = "../../../" + dirmain
 pywork = "%s/pywork" % dirbase
 htmldir = "%s/html" % pywork
 htmldb = "%s/%shtml.sqlite" % (htmldir,code.lower())
 htmldb_size = os.path.getsize(htmldb) # in bytes
 # also get xml sqlite file, in web/sqlite
 dirweb = "%s/web" % dirbase
 xmldb = "%s/sqlite/%s.sqlite" %(dirweb,code.lower())
 xmldb_size = os.path.getsize(xmldb) # in bytes

 # convert from byte to mb
 
 htmldb_msize = float(htmldb_size)/mb
 xmldb_msize = float(xmldb_size)/mb
 # compute % diff of html rel. to xml
 d = 100.0*(htmldb_msize-xmldb_msize)/xmldb_msize
 
 out = "%4s %5.1f %5.1f %4.0f %%" %(code,htmldb_msize,xmldb_msize,d)
 print out
 return (htmldb_msize,xmldb_msize,d)
 #os.path.getsize
def main():
 codes = dictyear.keys()
 codes = sorted(codes) # alphabetical order
 tots = [0.0,0.0,0.0]
 for code in codes:
  (h,x,d) = checkhtml(code)
  tots[0] = tots[0] + h
  tots[1] = tots[1] + x
  tots[2] = tots[2] + d
 # print totals
 print "Totals"
 ncodes = len(codes)
 print "%6d dictionaries" % ncodes
 print "%6.1f MB in html sqlite files" % tots[0]
 print "%6.1f MB in  xml sqlite files" % tots[1]
 
if __name__=="__main__":
 main()
