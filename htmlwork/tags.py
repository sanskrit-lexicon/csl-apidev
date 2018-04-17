"""tags.py
   June 7, 2015
   For a given dictionary code X, read through the generated html and
   tabulate the tags used and their frequency, printing results to stdout.
   This version reads the X/YYYY/pywork/html/input.txt files.

"""
import sys,re
import codecs
import os.path,time
import os

class Counter(dict):
 def __init__(self):
  self = {}
 def update(self,l):
  for x in l:
   if not (x in self):
    self[x]=0
   self[x] = self[x] + 1


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

def find_tags(data):
 alltags = re.findall(r'<[^/].*?>',data)
 tags = []
 for tag in alltags:
  m = re.search(r'^<([^ >/]*)',tag)
  t = m.group(1)
  if not (t in ['a','info','body']):
   tags.append(tag)
 return tags

def gentags(code):
 codelow = code.lower()  # lower case
 if not (code in dictyear):
  print "gentags ERROR: Invalid dictionary code:",code
  exit(1)
 year = dictyear[code]
 dirmain = "%sScan/%s" %(code,year)
 # Take into account relative location of this program file
 dirbase = dirin = "../../../" + dirmain
 pywork = "%s/pywork" % dirbase
 if not os.path.exists(pywork):
  print "gentags ERROR: Bad pywork directory:",pywork
  exit(1)
 #print pywork,"directory exists"
 htmldir = "%s/html" % pywork
 indexpath = "%s/input.txt" % htmldir
 if not os.path.exists(indexpath):
  print "ERROR. input.txt not found" % indexpath
  exit(1)
 f = codecs.open(indexpath,"r","utf-8")
 c = Counter()
 for line in f:
  line = line.rstrip('\r\n')
  (key,lnum,data) = re.split('\t',line)
  tags = find_tags(data)
  c.update(tags)
 f.close()
 tags = c.keys()
 tags = sorted(tags)
 for tag in tags:
  out =  "%06d %s" %(c[tag],tag)
  print out
 return


if __name__=="__main__":
 code = sys.argv[1]  # dictionary code, upper case
 gentags(code)
