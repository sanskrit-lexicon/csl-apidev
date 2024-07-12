
Much of this code is identical to code in csl-websanlexicon.
This compatibility should be maintained.

This compatibility can be verified by this script:

the script /c/xampp/htdocs/cologne/csl-websanlexicon/v02/apidev_copy.sh
  copies basicadjust.php, basicdisplay.php and getword_data.php from
  local installation of csl-websanlexicon to csl-apidev.
  The idea is that modifications will be first made to csl-websanlexicon
  and then 'transferred' to csl-apidev.
  
----------------------------------------------
 getword_data.php and getwordClass.php
 
  Does the $basicOption variable in the constructor of
     Getword_data class in csl-websanlexicon  have any importance in csl-apidev?
  Answer: yes -- csl-apidev/parm.php initializes this to false.
  NOTE: The only call to this construction in the simple-search display
    is via getwordClass.php, which uses the parm.php value of 'false'.

/c/xampp/htdocs/cologne/csl-apidev (master)
$ grep 'Getword_data(' *.php
api0_hws0Class.php:  $temp = new Getword_data();
getwordClass.php:  $temp = new Getword_data($basicOption);
servepdfClass.php:  $temp = new Getword_data(); // Uses key

***********************************************************************
Some csl-apidev vs. csl-websanlexicon  diffs as of 07-11-2024
-----------------------------------------------
getwordClass.php

$  diff getwordClass.php /c/xampp/htdocs/cologne/csl-websanlexicon/v02/makotemplates/web/webtc/getwordClass.php
17,18c17,18
<   $this->basicOption = $this->getParms->basicOption;
<   // $this->basicOption = $basicOption; // 06-19-2024 Refer webtc1/
---
>   // $this->basicOption = $this->getParms->basicOption;
>   $this->basicOption = $basicOption; // 06-19-2024 Refer webtc1/
103,104d102
<  } else {
<   $linkcss = "<link rel='stylesheet' type='text/css' href='css/basic.css' />";
105a104
>  $linkcss = ""; // 06-19-2024

Note: linkcss is used ONLY in csl-apidev.
---------------------------------------------------
getword_data.php

diff getword_data.php /c/xampp/htdocs/cologne/csl-websanlexicon/v02/makotemplates/web/webtc/getword_data.php > tempdiff_getword_data_work.txt

<EMPTY -- no differences>

NOTE:
 getword_data
   xmlmatches is public in csl-apidev.  This probably has some app,
   although not in the simple-search display.
   It does no harm in csl-websanlexicon to make xmlmatches public.

---------------------------------------------------
diff dispitem.php /c/xampp/htdocs/cologne/csl-websanlexicon/v02/makotemplates/web/webtc/dispitem.php > tempdiff_dispitem_work.txt

326c326
<   $serve = "//localhost/cologne/csl-apidev/$serve";
---
>   $serve = "../webtc/$serve";

---------------------------------------------------
diff dispitem.php /c/xampp/htdocs/cologne/csl-websanlexicon/v02/makotemplates/web/webtc/dispitem.php > tempdiff_dispitem_work.txt
----------------------------------------------

How related:?  
1. list-pane in csl-apidev (as part of simple-search display)
   listhier.php  listhierClass.php  listview.php
2. webtc1 in csl-websanlexicon)
   listhier.php  listhiermodel.php  listhierview.php  listparm.php

In present work,
   listhierview.php (126 lines, in csl-websanlexicon)
 was coordinated with a subset of
   listhierClass.php (433 lines, in csl-apidev)
 for the purpose of the rev/sup markup in the list pane.
 
But the exact relation between
