""" word_frequency_norm.py
    08-17-2017  Normalize the spellings
"""
import sys,re,codecs
sys.path.append('../../../sanhw1/')
from hwnorm1c import normalize_key
class WFreq(object):
 d = {}
 n = 0
 def __init__(self,line):
  # WFreq is not actually needed here, but handles duplicate words
  line = line.rstrip('\r\n')
  (self.word,wf) = line.split(' ')
  self.wf = int(wf)
  WFreq.n = WFreq.n + 1
  self.n = WFreq.n
  d = WFreq.d
  if self.word not in d:
   d[self.word] = []
  d[self.word].append(self)

def init_wf(filein):
 with codecs.open(filein,"r","utf-8") as f:
  recs = [WFreq(x) for x in f]
 return recs

def write_dups(recs,fileout):
 with codecs.open(fileout,"w","utf-8") as fout:
  d = WFreq.d;
  nout = 0
  for rec in recs:
   word = rec.word
   dups = d[word]
   if len(dups) == 1:
    continue
   if rec != dups[0]:
    continue
   # first occurence of dup
   wfreqdups = ["%s"%r.wf for r in dups]
   wfreqdupstr = ','.join(wfreqdups)
   out = "%05d: %s %s"%(rec.n,word,wfreqdupstr)
   fout.write(out + '\n')
   nout = nout + 1
 print nout,"records written to",fileout

def rewrite(recs,fileout):
 """ write all non-duplicates, using the maximum of wfrequencies when
     there are duplicates.  Write duplicates only once
 """
 with codecs.open(fileout,"w","utf-8") as fout:
  d = WFreq.d;
  nout = 0
  for rec in recs:
   word = rec.word
   dups = d[word]
   if len(dups) == 1:
    wf = rec.wf
    out = '%s %s' %(word,wf)
    fout.write(out + '\n')
    nout = nout + 1
    continue
   # we now know that we are dealing with a dup.
   if rec != dups[0]:
    # skip all but first dup
    continue
   # first occurence of dup
   wfreqdups = [r.wf for r in dups]
   wf = max(wfreqdups)
   out = '%s %s' %(word,wf)
   fout.write(out + '\n')
   nout = nout + 1
 print nout,"records written to",fileout

if __name__ == "__main__":
 filein = sys.argv[1]
 fileout = sys.argv[2]
 filediff = sys.argv[3]  # differences in spelling
 recs = init_wf(filein)
 print len(recs),"read from",filein
 # count of number of words whose spelling differs from normalized spelling
 ndiff = 0  
 fdiff = codecs.open(filediff,"w","utf-8")
 with codecs.open(fileout,"w","utf-8") as f:
  for rec in recs:
   norm = normalize_key(rec.word)
   out = '%s %s'%(norm,rec.wf)
   f.write(out+'\n')
   if norm != rec.word:
    ndiff = ndiff+1
    fdiff.write('%s %s -> %s\n' %(rec.wf,rec.word,norm))
 print len(recs),"normalized records written to",fileout
 print ndiff,"records whose spelling is changed by normalization"
 fdiff.close()
 print ndiff,"records written to",filediff


 
