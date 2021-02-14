""" make_sqlite_fts.py
   make an inverted index (fts = full text search) from a csv file.
   Assume no header in csv file and ':' as separator
"""
from __future__ import print_function
import sys,re,codecs;
import sqlite3
import time  # for performance checks
def remove(fileout):
 import os
 if os.path.exists(fileout):
  os.remove(fileout)
  print("removed previous",fileout)

def get_dict_code(fileout):
 # assume fileout is xxx.sqlite
 m = re.search(r'^(.*?)[.]sqlite$',fileout)
 if not m:
  print('sqlite.py ERROR: cannot get dictionary code')
  print('fileout=',fileout)
  exit(1)
 code = m.group(1).lower() # should be lower case?
 print('sqlite.py: dictionary code=',code)
 return code

def create_virtual_table(c,conn,tabname,colnames):
 #tabcols = ['%s TEXT' %colname for colname in colnames]
 #tabcols_string = ','.join(colnames)
 # default tokenizer is
 # unicode61 (normalise all tokens into unicode characters
 # ascii: converts all non-ascii characters into ascii version
 #   e.g., remove diacritics
 # porter:  porter stemming algorithm for english 'stems'
 # for this purpose, we DON'T wwant porter stemming
 tokenizers = '' #'tokenize = "porter"'
 ftsargs = colnames + [tokenizers]
 ftsargs_string = ','.join(ftsargs)
 # can also use fts4 or fts3
 template = 'CREATE VIRTUAL TABLE %s USING fts5(%s);' % (tabname,ftsargs_string)
 if False:  #dbg
  print('DBG: table template=')
  print(template)
 c.execute(template)
 conn.commit()

def insert_batch(c,conn,tabname,rows,colnames):
 # if rows is empty, nothing to do
 if len(rows) == 0:
  return
 # one placehold per colname
 placeholders = ['?' for x in colnames]
 placeholders_string = ','.join(placeholders)
 sql = 'INSERT INTO %s VALUES (%s)' % (tabname,placeholders_string)
 if False: #dbg.
  print('sql = ',sql)
 c.executemany(sql,rows)
 conn.commit()
 
if __name__ == "__main__":
 time0 = time.time() # a real number

 filein = sys.argv[1]   # xxx.txt
 fileout = sys.argv[2]  # xxx.sqlite
 mbatch = 10000
 separator = ':'  # column separator in filein as csv file
 remove(fileout) 
 # infer dictionary name from fileout. And use this as table name
 dictlo = get_dict_code(fileout)
 tabname = dictlo
 print('table name=',tabname)
 # establish connection to xxx.sqlite, 
 # also creates xxx.sqlite if it doesn't exist
 conn = sqlite3.connect(fileout)
 c = conn.cursor()  # prepare cursor for further use
 import csv,codecs
 rows = [] # data rows (exclude first row which has column names
 with codecs.open(filein,"r","utf-8") as f:
  reader = csv.reader(f,delimiter = separator)
  nrow = 0
  for irow,row in enumerate(reader):
   if irow == 0:
    colnames = row
    create_virtual_table(c,conn,tabname,colnames)
   elif nrow < mbatch:
    rows.append(row)
    nrow = nrow + 1
   else:
    # insert records of a batch, and commit
    insert_batch(c,conn,tabname,rows,colnames)
    # reinit batch
    rows = []
    nrow = 0
    # add this row to
    rows.append(row)
    nrow = nrow + 1
 #insert last batch
 insert_batch(c,conn,tabname,rows,colnames)

 
 # create index
 #create_index(c,conn,dictlo)
 conn.close()  # close the connection to xxx.sqlite
 time1 = time.time()  # ending time
 print(irow,'lines read from',filein)
 #print(nrow,'rows written to',fileout)
 timediff = time1 - time0 # seconds
 print('%0.2f seconds for batch size %s' %(timediff,mbatch))
