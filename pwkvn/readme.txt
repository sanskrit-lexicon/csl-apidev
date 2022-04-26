Based on github repository https://github.com/funderburkjim/webcompLearn/
This is a variation of getword05b.
The citation suggestion search is over all headwords which are in
 either schmidt or pwkvn.
 The variation is in csl-citation.
 

cp -r temp_lit-getword05b temp_lit-getword05c

-----------------------------------------------------------------
preparation of headword list.
cp /c/xampp/htdocs/cologne/sch/pywork/schhw.txt temp_schhw.txt
cp /c/xampp/htdocs/cologne/pwkvn/pywork/pwkvnhw.txt temp_pwkvnhw.txt

cp /c/xampp/htdocs/cologne/pw/pywork/pwhw.txt temp_pwhw.txt
 wc -l *hw.txt
  135787 temp_pwhw.txt
  25064 temp_pwkvnhw.txt
  29123 temp_schhw.txt

python mergehw.py temp_schhw.txt temp_pwkvnhw.txt temp_pwhw.txt mergehw.txt
28454 distinct headwords out of 29123 headwords read from temp_schhw.txt
14998 distinct headwords out of 25064 headwords read from temp_pwkvnhw.txt
131917 distinct headwords out of 135787 headwords read from temp_pwhw.txt
29553 hws merged from sch and vn
13899 headwords in both sch and vn
8919 headwords in (sch or vn) and pw
29553 records written to mergehw.txt

unique headwords, sorted in Sanskrit alphabetical order
mark each of these according to presence in sch, pwkvn, pw

