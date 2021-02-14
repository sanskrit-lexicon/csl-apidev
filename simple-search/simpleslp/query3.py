import sqlite3
import json
import sys,codecs,re
def stems_m(word,dbg):
 ans = [word]
 if re.search(r'[aiu][m]$',word):
  stem = word[0:-1]
  ans.append(stem)
 # m.a. kupAByAm kupabyam -> kupa
 # n.a. vanAByAm vanabyam -> vana
 # similarly muniByAm, SiSuByAm
 stem = re.sub(r'^(.*[aiu])(byam)$',r'\1',word)
 if stem not in ans: ans.append(stem)
 # kupAnAm kupa,  munInAm muni, SiSUnAm SiSu
 stem = re.sub(r'^(.*[aiu])nam$',r'\1',word)
 if stem not in ans: ans.append(stem)
 return ans

def stems_h(word,dbg):
 ans = [word]
 if re.search(r'[aiu][h]$',word):
  stem = word[0:-1]
  ans.append(stem)
 if word.endswith('eh'):
  # case 3p of nouns ending in a:  kUpEH.
  stem = word[0:-2] + 'a'  # kUpa
  if stem not in ans: ans.append(stem)
 # kupeByaH kupa, vaneByaH vana
 stem = re.sub(r'^(.*[e])(byah)$',r'\1',word)
 if stem not in ans: ans.append(stem)
 # kupayoH kupa
 stem = re.sub(r'^(.*a)yoh$',r'\1',word)
 if stem not in ans: ans.append(stem)
 # muniBiH muni, SiSuBiH SiSu
 stem = re.sub(r'^(.*[iu])(bih)$',r'\1',word)
 if stem not in ans: ans.append(stem)
 # munyoH muni
 stem = re.sub(r'^(.*)(yoh)$',r'\1i',word)
 if stem not in ans: ans.append(stem)
 # SiSvoH SiSu
 stem = re.sub(r'^(.*)(voh)$',r'\1u',word)
 if stem not in ans: ans.append(stem)
 # munayaH muni
 stem = re.sub(r'^(.*)(ayah)$',r'\1i',word)
 if stem not in ans: ans.append(stem)
 # SiSavaH SiSu
 stem = re.sub(r'^(.*)(avah)$',r'\1u',word)
 if stem not in ans: ans.append(stem)
 return ans

def stems_o(word,dbg):
 ans = [word]
 # vanO vana, kUpO kupa
 stem = word[0:-1] + 'a' # drop the O,o  
 ans.append(stem)
 return ans

def stems_e(word,dbg):
 ans = [word]
 # vane vana
 stem = word[0:-1] + 'a'
 ans.append(stem)
 return ans

def stems_i(word,dbg):
 ans = [word]
 # vanAni vana
 stem = re.sub(r'^(.*a)ni$',r'\1',word)
 if stem not in ans: ans.append(stem)
 ans.append(stem)
 return ans

def stems_a(word,dbg):
 ans = [word]
 # kupena kupa, 
 stem = re.sub(r'^(.*)ena$',r'\1a',word)
 if stem not in ans: ans.append(stem)
 return ans

def stems_u(word,dbg):
 ans = [word]
 # kupezu kupa
 stem = re.sub(r'^(.*)esu$',r'\1a',word)
 if stem not in ans: ans.append(stem)
 # SiSuzu SiSu
 stem = re.sub(r'^(.*u)su$',r'\1',word)
 if stem not in ans: ans.append(stem)
 return ans

def stems(word,dbg):
 """
 assume word is a simpleslp1lo-spelled word
 return a list, including word and some stems of word
 That is, we may assume word represents an inflected form of some stem.
 And we recover the stem
 """
 if word.endswith('m'): return stems_m(word,dbg)
 if word.endswith('h'): return stems_h(word,dbg)
 if word.endswith('o'): return stems_o(word,dbg)
 if word.endswith('a'): return stems_a(word,dbg)
 if word.endswith('u'): return stems_u(word,dbg)
 if word.endswith('i'): return stems_i(word,dbg)
 if word.endswith('e'): return stems_i(word,dbg)
 return [word]

def query3(word,dbg):
 """ the 'filepath' variable needs to be set in a more portable manner
  Currently, we assume the
 """
 tabname = 'hwnorm1c_simpleslp1'
 fileout = '%s.sqlite' % tabname
 #word = 'asva' # in simpleslp1
 words = stems(word,dbg)
 query = ' OR '.join(words)
 sql = 'select slp1 from %s where simpleslp1 MATCH "%s" ;' %(tabname,query)
 #dbg=True
 if dbg:
  import os
  cwd = os.getcwd()
  # when called from php in ../v1.1a,  cwd is ../v1.1a
  dbgprint(dbg,"query3.py: cwd=%s\n" %cwd)
 filepath = '../simpleslp/%s' % fileout  
 conn = sqlite3.connect(filepath)
 c = conn.cursor()  # prepare cursor for further use
 ans0 = c.execute(sql).fetchall()
 #ans = remove_u(ans0)
 ans = ans0
 ansobj = []
 #colnames = get_columnames(c,tabname)
 #print('colnames=',colnames)
 for a in ans:
  ansobj.append(a[0])  # a is a list with one element -- a headword in slp1
  continue
  obj = {}
  #print('a=',a,type(a))
  for i,colname in enumerate(colnames):
   val = a[i]
   obj[colname] = val
  ansobj.append(obj)
 result = {}
 result['status'] = 200
 result['result'] = ansobj
 ans_json  = json.dumps(result)
 print(ans_json)

def dbgprint(flag,text):
 if not flag:
  return
 with codecs.open("dbg_query3.txt","a","utf-8") as f:
  f.write(text)

if __name__ == "__main__":
 word = sys.argv[1]  # word in simpleslp1
 dbg = False
 dbgprint(dbg,"query3: word=%s\n" % word)
 query3(word,dbg)
 
