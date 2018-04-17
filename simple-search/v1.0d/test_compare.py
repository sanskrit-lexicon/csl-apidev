"""test_compare.py
   08-10-2017  Compare test-suite results
   between v0.1d/X_out.txt and v0.1/X_out.txt
python test_compare.py test1 test1_compare.txt

"""
import sys,codecs
import re
class Rec(object):
 def __init__(self,line):
  line = line.rstrip('\r\n')
  m = re.search(r'^([^ ]*) ([^ ]*) ([^ ]*)$',line)
  self.key = m.group(1)
  self.dict = m.group(2)
  self.results = m.group(3)

def init_recs(filein):
 with codecs.open(filein,"r","utf-8") as f:
  recs = [Rec(line) for line in f]
 return recs

if __name__ == "__main__":
 filepfx = sys.argv[1]
 fileout = sys.argv[2]
 # assume in either v0.1 or v0.1d
 dir1 = 'v1.0 '
 dir2 = 'v1.0d'
 dir1a = dir1.rstrip()
 filein1='../%s/test_suite/%s_out.txt' %(dir1a,filepfx)
 filein2='../%s/test_suite/%s_out.txt' %(dir2,filepfx)
 recs1 = init_recs(filein1)
 recs2 = init_recs(filein2)
 #ompare_suite(filein,fileout)
 if len(recs1)  != len(recs2):
  print "ERROR: %s has %s lines" %(filein1,len(recs1))
  print "ERROR: %s has %s lines" %(filein2,len(recs2))
  exit(1)
 outlines = []
 out = "; Compare %s for %s and %s" %(filepfx,dir1,dir2)
 outlines.append(out)
 n = len(recs1)
 outlines.append('; %s Cases' %n)
 nok = 0
 nprob = 0
 
 for idx1,rec1 in enumerate(recs1):
  rec2 = recs2[idx1]
  icase = idx1+1
  if rec1.results == rec2.results:
   status = 'EQ'
   nok = nok + 1
  else:
   status = 'NEQ'
   nprob = nprob + 1
  outlines.append('; Case %s:  %s %s (%s)' %(icase,rec1.key,rec1.dict,status))
  outlines.append('%s = %s' %(dir1,rec1.results))
  outlines.append('%s = %s' %(dir2,rec2.results))

  #utlines.append(';')
 # modify outlines[1] 
 outlines[1] = "%s . %s cases are same for %s and %s" %(outlines[1],nok,dir1,dir2)
 with codecs.open(fileout,"w","utf-8") as f:
  for out in outlines:
   f.write(out+'\n')
 print nok,"Cases are the same for %s and %s" %(dir1,dir2)
 print nprob,"Cases are different"

