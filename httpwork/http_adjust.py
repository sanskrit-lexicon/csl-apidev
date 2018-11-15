"""http_adjust.py
  11-15-2018
  change 'http://' to '//' in all relevant inventory files.
  This is in preparation for conversion of Cologne server
  to 'https'.
  python http_adjust.py http_files.txt http_adjust_log.txt
  Ref1: https://yoast.com/moving-your-website-to-https-ssl-tips-tricks/
  This reference says protocol relative links not good:
  Ref2: https://www.paulirish.com/2010/the-protocol-relative-url/
"""
import codecs,sys

def adjust_file(filename):
 ## change http:// to protocol-relative '//'
 old = 'http://'
 new = '//'
 changes = [] # list of changed line (old,new)
 with codecs.open(filename,"r","utf-8") as f:
  lines = [x.rstrip('\r\n') for x in f]
 # overwrite filein
 with codecs.open(filename,"w","utf-8") as f:
  for line in lines:
   line1 = line.replace(old,new)
   if line1 != line:
    change = (line,line1)
    changes.append(change)
   f.write(line1 + '\n')
 return changes

if __name__ == "__main__":
 filein = sys.argv[1]
 fileout = sys.argv[2]
 with codecs.open(filein,"r","utf-8") as f:
  # read uncommented lines 
  lines = [x.rstrip('\r\n') for x in f if not x.startswith(';')]
 outarr=[]
 flog = codecs.open(fileout,"w","utf-8")
 for filename in lines:
  changes = adjust_file(filename) 
  print(len(changes),filename)
  outarr=[]
  outarr.append("%s changes in %s\n" %(len(changes),filename))
  for ichange,change in enumerate(changes):
   (old,new) = change
   nchange = ichange+1  
   outarr.append('change#%d:'%nchange)
   outarr.append('old: %s' % old)
   outarr.append('new: %s' % new)
   outarr.append('----------------------------------------')
  for out in outarr:
   flog.write(out + '\n')
 flog.close()

 

