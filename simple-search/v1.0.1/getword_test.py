""" getword_test.py
"""
#from subprocess import call
import subprocess
import json
import sys,codecs,re
# for python 2.6.
# ref = https://stackoverflow.com/questions/4760215/running-shell-command-from-python-and-capturing-the-output
#getword_list_1.0.cli.php vishnu mw
#x = subprocess.check_output(["php","getword_list_1.0.cli.php",'vishnu','mw'])
#print x.encode('utf-8')
def genoutput(out,dictcode):
 # out is a string, in JSON format
 #print out
 obj = json.loads(out)
 keys = [r['dicthw'] for r in obj['result']]
 return ','.join(keys)
def test():
 key = sys.argv[1]
 dictcode = sys.argv[2]
 #cmd = "php getword_list_1.0.cli.php %s %s" %(key,dictcode)
 cmd=["php","getword_list_1.0.cli.php",key,dictcode]
 p = subprocess.Popen(cmd,stdout=subprocess.PIPE,stderr=subprocess.PIPE)
 outall,err = p.communicate()
 outlines = outall.split('\n')
 for out in outlines:
  if out.strip() != '':
   txt =  genoutput(out,dictcode)
   print key,dictcode,txt

def parse_input(filein):
 recs = []
 with codecs.open(filein,"r","utf-8") as f:
  for line in f:
   if line.startswith(';'):
    continue # comment
   line = line.rstrip('\r\n')
   key,dictcode = re.split(r' +',line)
   recs.append((key,dictcode))
 print len(recs),"records from",filein
 return recs

def do_getword(key,dictcode):
 cmd=["php","getword_list_1.0.cli.php",key,dictcode]
 p = subprocess.Popen(cmd,stdout=subprocess.PIPE,stderr=subprocess.PIPE)
 outall,err = p.communicate()
 outlines = outall.split('\n')
 for out in outlines:
  if out.strip() != '':
   txt =  genoutput(out,dictcode)
   return "%s %s %s" %(key,dictcode,txt)

def test_suite(filein,fileout):
 recs = parse_input(filein)
 with codecs.open(fileout,"w","utf-8") as f:
  for (key,dictcode) in recs:
   out = do_getword(key,dictcode)
   f.write(out+'\n')
 print len(recs),"results written to",fileout
if __name__ == "__main__":
 filein = sys.argv[1]
 fileout = sys.argv[2]
 test_suite(filein,fileout)
 

