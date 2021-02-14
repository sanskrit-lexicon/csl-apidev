""" simpleslp1.py
"""

import codecs,re,sys
import transcoder

#transcoder_dir = transcoder.transcoder_set_dir('../../utilities/transcoder')
import os
dir_path = os.path.dirname(os.path.realpath(__file__))
transcoder_dir_path = os.path.join(dir_path,'transcoder')
transcoder_dir = transcoder.transcoder_set_dir(transcoder_dir_path)
#print('transcoder_dir_path=',transcoder_dir_path)
#exit(1)

def simple_lower(word):
 """ lower case all letters in word, EXCEPT Y (palatal nasal) and
  R (cerebral nasal) -- Y and R are changed to 'n' in transcoder.
 """
 def sub(m):
  a = m.group(1)
  return a.lower()
 
 regex = '([AIUFXEOMHKGNCJWQTDPBLVSZ])'
 word1 = re.sub(regex,sub,word)
 return word1

def remove_double(word):
 """ replace xx with x in word """
 regex = r'(.)\1'
 def sub (m):
  a = m.group(0)  # xx
  return a[0]     # x
 word1 = re.sub(regex,sub,word)
 return word1

def mn_consonants(alist,old,new):
 # assume 'vowels' are aiueo
 regex = r'%s([^aiueo])' % old
 repl =  r'%s\1' %new
 ans = []
 for a in alist:
  x = re.sub(regex,repl,a)
  if x not in  ans:
   ans.append(x)
 return ans

def simpleslp1(word):
 """ Apply slp1_simpleslp1 transcoder. 
  lower case all letters in word, EXCEPT Y (palatal nasal) and
  R (cerebral nasal) -- Y and R are changed to 'n' in transcoder.
  Also, replace a doubled letter by the single letter.
 """
 word1 = simple_lower(word)
 word2 = remove_double(word1)
 ans1 = transcoder.transcoder_processString(word2,'slp1','simpleslp1lo')
 ans = [ans1]
 if 'f' in word2:
  # Handle other forms of 'f':  ri,ru,ar
  for altf in ['ri','ru','ar']:
   word3 = re.sub('f',altf,word2)
   ansf = transcoder.transcoder_processString(word3,'slp1','simpleslp1lo')
   ans.append(ansf)
 # allow either 'm' or 'n' before consonant
 a1 = mn_consonants(ans,'m','n')  # change mC to nC (C = consonant)
 a2 = mn_consonants(ans,'n','m')
 ans = ans + a1 + a2
 if 'kxp' in word2:
  # Handle other forms of 'x':  l and also lr, lri,
  for altf in ['klrp','klrip','klrup','kalp']:
   word3 = re.sub('kxp',altf,word2)
   ansx = transcoder.transcoder_processString(word3,'slp1','simpleslp1lo')
   ans.append(ansx)
 if re.search(r'ar$',ans1):
  # cases like pw: kar <-> kf.
  # This is aimed at verbs only, but the code will catch words
  # ending in punar
  for altf in ['ri','ru','r']:
   x = re.sub(r'ar$',altf,ans1)
   if x not in ans:
    ans.append(x)
 # special case of 'kalp' verb (in pw, etc) == kxp
 if ans1 == 'kalp':
  for alt in ['klp','klrp','klrip']:
   x = re.sub('kalp$',alt,ans1)
   if x not in ans:
    ans.append(x)
 # Choose to add grammar variants
 # in the query
 return ans
 """
 # Add Grammar variants
 ans1 = []
 for a in ans:
  for a1 in grammar_variants(a):
   if a1 not in ans1:
    ans1.append(a1)
 
 return ans1
 """


def grammar_variants(word):
 ans = [word]
 if word.endswith(('a','i','u')):
  ans.append(word + 'h')
  ans.append(word + 'm')
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
  if (iline % 50000) == 0:
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

