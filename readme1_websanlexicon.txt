
Much of this code is identical to code in csl-websanlexicon.
This compatibility should be maintained.

This compatibility can be verified by this script:

dirweb=../csl-websanlexicon/v02/makotemplates/web/webtc
for file in dispitem.php getword.php getword_data.php \
            getwordClass.php basicadjust.php basicdisplay.php \
            dbgprint.php
do

echo "diff -w $file $dirweb/$file"
diff -w $file $dirweb/$file
done
