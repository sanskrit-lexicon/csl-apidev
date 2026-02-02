
readme_newdict.txt in simple-search
02-02-2026
When a new dictionary is added to the cdsl collection,
changes to various repos are required.
This readme file mentions several changes in repos:
  csl-websanlexicon
  csl-apidev
  csl-hwnorm1
1. # changes in csl-websanlexicon
    cd /c/xampp/htdocs/cologne/csl-websanlexicon/v02
1a. # make needed changes (if any) to
    makotemplates/web/webtc/basicadjust.php
    makotemplates/web/webtc/dal.php
    makotemplates/web/webtc/basicdisplay.php
    makotemplates/web/webtc/getword_data.php 
1b. # copy these files to csl-apidev
    cd /c/xampp/htdocs/cologne/csl-websanlexicon/v02
    sh apidev_copy.sh
2.  # additional changes to csl-apidev
    cd /c/xampp/htdocs/cologne/csl-apidev/
2a. # Add new dictionary references to
    dictinfo.php  (e.g. AP90)
    dispitem.php  (e.g. AP90)
    dal.php (e.g. ap90) ?
    sample/dictnames.js
2b. # update hwnorm1/hwnorm1c.sqlite  database
    cd /c/xampp/htdocs/cologne/hwnorm1/sanhw1
    # edit sanhw1.py and add new dictionary code in two spots
    # follow instructions in readme.txt
