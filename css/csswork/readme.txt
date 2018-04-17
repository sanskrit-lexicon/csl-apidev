
csswork/readme.org

* cssfiles/<dict
 python css.py webtc
 python css.py webtc1
 python css.py webtc2
 python css.py mobile
gathers all the (relevant) css files for the different displays and 
  dictionaries into cssfiles subdirectory

* compare_webtc.txt
python compare.py webtc MW72 > compare_webtc.txt
all the 'X_webtc_main.css' files are compared to MW72_webtc_main.css,
using the system 'diff' utility.

Results:
Here are the ones identical to MW72_webtc_main.css
MW72 == ACC,AE,BEN,BHS,BOP,
        BOR,GRA,GST,IEG,INM,
	KRM,MCI,MD,PE,PGN,
        PUI,SCH,SHS,SNP,VEI,
	WIL,YAT
MW72 != AP90,AP,BUR,CAE,CCS
	MWE,MW,PD,PWG,PW,
	SKD,STC,VCP
	
