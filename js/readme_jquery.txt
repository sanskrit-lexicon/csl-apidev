readme_jquery.txt
03-05-2023
Reason for this note:  Github generated a message pertaining to a xss vulnerability:
  https://github.com/sanskrit-lexicon/csl-apidev/security/dependabot/1
  This says that the vulnerability pertains to jquery with versions < 1.6.3.
We have been using js/jquery.min.js  which is  version 1.4.3.

# save prior version, which was 1.4.3
mv jquery.min.js jquery.min.prev.js
# Download jquery-1.6.3.min.js from https://code.jquery.com/jquery-1.6.3.min.js
# Note:  the jquery version being used can be found by this statement in
#  developer tools console:  $.fn.jquery
#

jquery.min.js is used only in listview.php. However listview.php is also called by
  simple-search/v1.1/list-0.2s_rw.php

Note listview-0.2s_rw.php also makes reference to a cloud resource of jquery, at version 2.1.4.
list-0.2s_rw.php:<script type="text/javascript" src="//code.jquery.com/jquery-2.1.4.min.js"></script>
This 2.1.4.min.js reference is NOT changed.

Other jquery components are used in simple-search/v1.1/list-0.2s_rw.php

list-0.2s_rw.php:<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.css">
list-0.2s_rw.php:<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
list-0.2s_rw.php:<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>

Note these have NOT been modified.
