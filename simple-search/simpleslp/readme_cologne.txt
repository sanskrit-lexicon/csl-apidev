
This to recreate a sqlite file: hwnorm1c_simpleslp1.sqlite
 which is used by simple-search/v1.1a
 

cp  ../../../../hwnorm1/sanhw1/hwnorm1c.txt .

# use python3
python3 simpleslp1.py hwnorm1c.txt hwnorm1c_simpleslp1.txt
 colnames = slp1:simpleslp1
python3 make_sqlite_fts.py hwnorm1c_simpleslp1.txt hwnorm1c_simpleslp1.sqlite
 # python3.6 at cologne does not have fts5 module for sqlite, so we use fts4
 
385190 lines read from hwnorm1c_simpleslp1.txt
22.45 seconds for batch size 10000

Note: On a local (new) computer, about 3 seconds
