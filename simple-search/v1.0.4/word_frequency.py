""" word_frequency.py
    06-01-2017
"""
import sys,re,codecs
import transcoder
transcoder.transcoder_set_dir("../../utilities/transcoder/")
if __name__ == "__main__":
 filein = sys.argv[1]
 fileout = sys.argv[2]
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
