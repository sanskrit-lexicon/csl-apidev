""" word_frequency_hwnorm1c.py
    08-17-2017  Which word_frequency words are found in hwnorm1c, and
                for those, how many dictionaries are represented?
"""
import sys,re,codecs
sys.path.append('../../../sanhw1/')
from hwnorm1c import normalize_key
import sqlite3

class HWnorm(object):
 def __init__(self,resultpair):
  self.key,self.data = resultpair
  spellings = self.data.split(';')
  self.spellings = []
  for spelling in spellings:
   key1,dictstr = spelling.split(':')
   dictlist = dictstr.split(',')
   self.spellings.append((key1,dictlist))

class HWnorm1Sqlite(object):
 def __init__(self,filename):
  self.filename = filename
  self.tablename = 'hwnorm1c'
  try:
   self.conn = sqlite3.connect(filename)
   self.status = True
  except sqlite3.Error as e:
   print "HWnorm1Sqlite init: An error occurred:", e.args[0]
   self.status = False
   self.conn = None
 def close(self):
  if self.status:
   conn.close()
 def get(self,key):
  # results is a list (possibly empty)
  # Each list element is a 2-tuple: (key,data)
  t = (key,)  # for security
  sql = "SELECT * FROM hwnorm1c WHERE key=?"
  c = self.conn.cursor()
  c.execute(sql,t)
  rawresults = c.fetchall()
  results = [HWnorm(r) for r in rawresults]
  return results
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

def test():
 filename = '../../../sanhw1/hwnorm1c.sqlite'
 HWnormgetter = HWnorm1Sqlite(filename)
 results = HWnormgetter.get('deva')
 for result in results:
  print result.key,len(result.spellings),result.data
if __name__ == "__main__":
 #test()
 #exit(1)
 filein = sys.argv[1]
 fileout = sys.argv[2]
 #filediff = sys.argv[3]  # differences in spelling
 recs = init_wf(filein)
 print len(recs),"read from",filein
 filename = '../../../sanhw1/hwnorm1c.sqlite'
 HWnormgetter = HWnorm1Sqlite(filename)
 def get(key):
  results = HWnormgetter.get(key)
  alldictlist = []
  for hwnormrec in results:
   for (key1,dictlist) in hwnormrec.spellings:
    alldictlist = alldictlist + dictlist
  ndicts = len(set(alldictlist))
  return ndicts
 # count of number of words whose spelling NOT in hwnorm1c
 notfound = 0
 with codecs.open(fileout,"w","utf-8") as f:
  for rec in recs:
   (key,wf) = (rec.word,rec.wf)
   ndict = get(key)
   out = '%s %s %s' %(key,wf,ndict)
   f.write(out+'\n')
   if ndict == 0:
    notfound = notfound + 1
 print len(recs),"records written to",fileout
 print notfound,"records not in hwnorm1c"



 
