#-*- coding:utf-8 -*-
"""mergehw.py
 
"""
from __future__ import print_function
import sys,re,codecs

class HW:
 def __init__(self,hw,insch,invn,inpw):
  self.hw = hw
  self.insch = insch
  self.invn = invn
  self.inpw = inpw
  
def init_hwdata(filein):
 with codecs.open(filein,"r","utf-8") as f:
  d = {}
  n = 0
  for x in f:
   m = re.search(r'<k1>(.*?)<k2>',x)
   n = n + 1
   hw = m.group(1)
   d[hw] = True
  recs = d.keys()

  print("%s distinct headwords out of %s headwords read from %s" %
        (len(recs),n,filein))
 return d

def merge(sd,vd,pd):
 sset = set(sd.keys())
 vset = set(vd.keys())
 svset = sset.union(vset)  # sch and vn headwords

 recs = []
 nsv  = 0
 npin = 0
 for hw in svset:
  sin = (hw in sd)
  vin = (hw in vd)
  pin = (hw in pd)
  rec = HW(hw,sin,vin,pin)
  recs.append(rec)
  if sin and vin: nsv = nsv + 1
  if pin: npin = npin + 1
 print(len(recs),"hws merged from sch and vn")
 print(nsv,"headwords in both sch and vn")
 print(npin,"headwords in (sch or vn) and pw")
 return recs

def write(fileout,recs):
 with codecs.open(fileout,"w","utf-8") as f:
  for rec in recs:
   a = []
   if rec.insch: a.append('s')
   if rec.invn: a.append('v')
   if rec.inpw: a.append('p')
   astr = ''.join(a)
   out = '%s %s' % (rec.hw,astr)
   f.write(out+'\n')
 print(len(recs),"records written to",fileout)

def sorthws(hws):
 slp_from = "aAiIuUfFxXeEoOMHkKgGNcCjJYwWqQRtTdDnpPbBmyrlvSzsh"
 slp_to =   "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvw"
 slp_from_to = str.maketrans(slp_from,slp_to)
 sorted_keys = sorted(hws,key = lambda x: x.hw.translate(slp_from_to))
 return sorted_keys

if __name__=="__main__":
 filein = sys.argv[1] # schhw
 filein1 = sys.argv[2] # pwkvnhw
 filein2 = sys.argv[3] # pwhw
 fileout = sys.argv[4] # 

 sd = init_hwdata(filein)
 vd = init_hwdata(filein1)
 pd = init_hwdata(filein2)
 hws = merge(sd,vd,pd)
 hws1 = sorthws(hws)
 write(fileout,hws1)

 
