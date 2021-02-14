
hwnorm1c.txt copied from cologne/hwnorm1/sanhw1/hwnorm1c.txt


simpleslp1.py construct variant spellings of normalized headwords
from hwnorm1c.txt.  We call the resulting spellings 'simpleslp1'.
These are in general lower-case slp1, but with a few variations.
The variations of an slp1 normalized 'word' are done in
function simpleslp1.
1. word -> word1:
   a. lower-case, except for 2 letters: Y and R.
   b. replace doubled letters with single letters (xx -> x)
2. word1 -> word2:  transcode according to file
   slp1_simpleslp1lo.xml in csl-apidev/utilities/transcoder/ directory.
   a. Y and R changed to 'n'  (palatal, cerebral nasals)
   b. f -> r  (vocalic r)
   c. x -> l  (vocalic l)
   d. consecutive vowels (hiatus) coalesed to single vowels (arbitrary)
   e. w -> t, q -> d
   f. z -> s
   g. | -> l   (DEVANAGARI LETTER LLA)
3. 
Alter it slightly to interface with make_sqlite_fts.py

python simpleslp1.py hwnorm1c.txt hwnorm1c_simpleslp1.txt
 colnames = slp1:simpleslp1
python make_sqlite_fts.py hwnorm1c_simpleslp1.txt hwnorm1c_simpleslp1.sqlite

