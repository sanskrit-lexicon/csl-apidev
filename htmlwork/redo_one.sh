C=$1
#echo "C = $C"
python genhtml.py $C
python redohtml.py $C
