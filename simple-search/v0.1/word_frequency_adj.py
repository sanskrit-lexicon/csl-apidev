""" word_frequency_adj.py
    07-20-2017
"""
import sys,re,codecs
class WFreq(object):
 d = {}
 n = 0
 def __init__(self,line):
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
 filedup = sys.argv[3]
 recs = init_wf(filein)
 print len(recs),"read from",filein
 write_dups(recs,filedup)
 rewrite(recs,fileout)
 exit(1)
 with codecs.open(filein,"r","utf-8") as f:
  with codecs.open(fileout,"w","utf-8") as fout:
   for line in f:
    line = line.rstrip('\r\n')
    m = re.search(r'localStorage.setItem\("(.*?)", *"(.*?)"\);',line)
    if not m:
     print "COULD NOT PARSE:",line.encode('utf-8')
     continue
    keyas = m.group(1)
    freq = m.group(2)
    keyslp1 = transcoder.transcoder_processString(keyas,"roman","slp1")
    fout.write("%s %s\n" %(keyslp1,freq))
