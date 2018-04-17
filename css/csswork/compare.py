"""compare.py
   June 6, 2015
   We have 3 categories of css files:  webtc, webtc1, and webtc2
   Within a category, we have a separate file for each dictionary.
   However, probably most of the files within a category are 'almost' identical
   from one dictionary to another.
   We want to tabulate the intra-category differences.
   This uses 'diff'.  Output to stdout
   python compare.py <category> <basedict> 
   python compare.py webtc MW72 > compare_webtc.txt
"""
import sys
import codecs
import os.path
import os 

def compare(category,basedict): #,fileout):
 dirbase = "cssfiles"  # where the css files reside
 basefile = "%s_%s_main.css" % (basedict,category)
 basepath = "%s/%s" %(dirbase,basefile)
 cssfiles = []
 for root, dirs, files in os.walk(dirbase):
  cssfiles = [f for f in files if f.endswith('%s_main.css'%category)]
  break
 for name in cssfiles:
  if name ==basefile:
   continue
  namepath = "%s/%s" %(dirbase,name)
  parts = []
  x = 'diff %s %s' % (basefile,name)
  parts.append('echo "%s"' % x)
  cmd = 'diff %s %s' % (basepath,namepath)
  parts.append(cmd)
  blank= 'echo ""'
  parts.append(blank)
  dashes = "-"*72
  parts.append('echo "%s"' % dashes)
  parts.append(blank)
  cmd = ';'.join(parts)
  os.system(cmd)
  
 return
 #for name in cssfiles:
 
if __name__=="__main__":
 category = sys.argv[1]
 basedict = sys.argv[2]
 #fileout = sys.argv[3]
 compare(category,basedict) #,fileout)
