""" w.py
    08-17-2017  Filter word_frequency_hwnorm1c.txt for last field != 0.
    write fields 1,2 (key, wf acc. to word_frequency_adj.txt)
"""
import sys,re,codecs

class WFreq(object):
 def __init__(self,line):
  # WFreq is not actually needed here, but handles duplicate words
  line = line.rstrip('\r\n')
  (self.word,wf,ndict) = line.split(' ')
  self.wf = int(wf)
  self.ndict = int(ndict)

def init_wf(filein):
 with codecs.open(filein,"r","utf-8") as f:
  recs = [WFreq(x) for x in f]
 return recs


if __name__ == "__main__":
 filein = sys.argv[1]
 fileout = sys.argv[2]
 recs = init_wf(filein)
 nout = 0
 with codecs.open(fileout,"w","utf-8") as f:
  for rec in recs: 
   (key,wf,ndict) = (rec.word,rec.wf,rec.ndict)
   if ndict == 0:
    continue  # skip if word not in any Cologne dictionaries
   out = '%s %s' %(key,wf)
   f.write(out+'\n')
   nout = nout + 1
 print len(recs),"records read from",filein
 print nout,"records written to",fileout



 
