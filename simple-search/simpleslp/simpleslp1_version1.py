""" simpleslp1.py
"""

import codecs,re,sys
import transcoder
transcoder_dir = transcoder.transcoder_set_dir('../../utilities/transcoder')

def simpleslp1(word):
 """ Apply slp1_simpleslp1 transcoder. 
  lower case all letters in word, EXCEPT Y (palatal nasal) and
  R (cerebral nasal) -- Y and R are changed to 'n' in transcoder.
  Also, replace a doubled letter by the single letter.
 """
 def sub1(m):
  a = m.group(1)
  return a.lower()
 
 regex1 = '([AIUFXEOMHKGNCJWQTDPBLVSZ])'
 word1 = re.sub(regex1,sub1,word)
 regex2 = r'(.)\1'
 def sub2(m):
  a = m.group(0)  # xx
  return a[0]     # x
 
 word2 = re.sub(regex2,sub2,word1)
 var = transcoder.transcoder_processString(word2,'slp1','simpleslp1lo')
 #if word != word2:
 # if word.startswith('kar'):
 #  print('dbg:',word,word1,word2,var)
 ans = [var]
 #if not re.search(r'(ar|ri|ru)
 # sometimes an 'ar' slp1 might also be slp1 vowel 'f'.
 # probably when NOT followed by a vowel
 #   (i.e. at end or followed by consonant)
 regex3 = r'(ar)([^aiufeo]|$)'
 def sub3(m):
  return 'r' + m.group(2)
 word3 = re.sub(regex3,sub3,var)
 #if True and (word3 != var):
 # print('dbg:',word,word1,word2,word3,var)
 if word3 != var:
  ans.append(word3)
 # sometimes, ri should be interpreted as 'f'
 # when (a) at beginning or not preceded by a vowel or followed by vowel
 regex4 = r'(^|[^aiufeo])ri([^aiufeo]|$)'
 def sub4(m):
  return m.group(1) + 'r' + m.group(2)  # drop r in ri
 word4 = re.sub(regex4,sub4,word3)
 if word4 != word3:
  ans.append(word4)
  if True:
   print('dbg:',word,word1,word2,var,word3,word4)
 return ans

def print_varstat(varstat,fileout):
 f = codecs.open(fileout,"w","utf-8")
 keys = sorted(varstat.keys())
 for k in keys:
  f.write('%05d keys with %05d variants\n' %(varstat[k],k))
 f.close()
 print(len(keys),"stats written to",fileout)

if __name__ == "__main__":
 filein = sys.argv[1]
 fileout = sys.argv[2]
 if len(sys.argv) > 3:
  filestat = sys.argv[3]
 else:
  filestat = None
 colnames = ['slp1','simpleslp1']   
 separator = ':'
 f = codecs.open(filein,"r","utf-8")
 fout = codecs.open(fileout,"w","utf-8")
 outline = ('%s'%separator).join(colnames)
 print('colnames line=',outline)
 fout.write(outline+'\n')
 
 varstat = {}
 mline = 1000000
 testwords = []
 #mline = 1000
 for iline,line in enumerate(f):
  if (iline % 10000) == 0:
   print('line',iline)
  if (testwords != []) and (iline < len(testwords)):
   word = testwords[iline]
  else:
   m = re.search(r'^(.*?):',line)
   word = m.group(1)
  variants = simpleslp1(word)
  varstr = ' '.join(variants)
  outline = '%s:%s' %(word,varstr)
  fout.write(outline + '\n')
  nvar = len(variants)
  if nvar not in varstat:
   varstat[nvar] = 0
  varstat[nvar] = varstat[nvar] + 1
  if iline == mline:
   break
 f.close()
 fout.close()
 if True and (filestat != None):
  print_varstat(varstat,filestat)

