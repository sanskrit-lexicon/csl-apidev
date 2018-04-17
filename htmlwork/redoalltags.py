"""redoalltags.py
   June 7, 2015
   Does tags for all dictionaries. Output files goes to tagfiles directory
   Usage:
   python redoalltags.py 
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
  os.system('echo "BEGIN redoalltags %s"' % code)
  os.system('python tags.py %s > tagfiles/%s_tags.txt' % (code,code.lower()))
  os.system('echo "END redoalltags %s"' % code)
  os.system('echo ""')
if __name__=="__main__":
 main()
