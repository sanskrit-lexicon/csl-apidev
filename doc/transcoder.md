# transcoder

The utilities folder of apidev contains transcoder.php and the transcoder subdirectory.
There are different ways to code Sanskrit for computer usage.  The most 'natural' way is probably with 
[Devanagari Unicode](https://en.wikipedia.org/wiki/Devanagari_(Unicode_block)).  But before Unicode became well-supported, other schemes were common; and there is still some convenience in using one or another of these other schemes.   The transcoder system was developed (in Java) by Ralph Bunker as a way of converting from one to another scheme.  transcoder.php is a functional PHP implementation of an early version of Bunker's Java code. 


To transcode a PHP string A from transcoding scheme X to transcoding scheme Y, one must have in hand an XML file 
of simple structure named X_Y.xml.  The transcoder subdirectory has a collection of these XML transcoder files.
The first time a particular program does a particular X to Y transcoding, the X_Y.xml file is parsed into a finite state machine data structure. This finite state machine then is applied to the string A, changing it into PHP string B. 

There are also some additional convenience functions in transcoder.php.

transcoder.php has also been translated into a Python module transcoder.py.  See https://github.com/funderburkjim/sanskrit-transcoding.

As mentioned, transcoder.php is written as a collection of PHP functions and global variables.  

Among the functions, some are intended for external use by other applications (such as the dictionary displays); other internal functions are helpers to the external functions.  The global variables are also intended as internally useful.

----

## transcoder subdirectory
**slp1** is  *lingua franca* of the sanskrit-lexicon website.  This means that SLP1 is used to represent Devanagari Sanskrit within digitizations.  Displays allow users to view or enter Devanagari Sanskrit in slp1 or several other codings.  For each coding X,  there is 
* a transcoder xml file named 'slp1_X.xml' for  transcoding from slp1 to X and 
* a transcoding file named X_slp1.xml for transcoding from X to slp1.

Currently, the standardized spellings for the codings X are:  hk, itrans, roman, deva, and wx.
<code>
**TODO**   The 'input' and 'output' menus of displays currently do not allow the user to choose the *wx* coding.
     This coding is used at Hyderabad University.
</code>

Other files, not of general interest (in particular, not used in the current displays):
  * as_roman.xml   'as' (Anglicized Sanskrit) is Thomas Malten's transcoding which uses letter-number combinations to represent text printed with the Latin alphabet, possibly with diacritics.
  * as_romanorig.xml  An earlier version of as_roman.xml
  * slp1_romanpms.xml A version of slp1_roman.xml prepared by Peter Scharf
  * pms directory : contains a zip file of some transcoders as of 2013, from Peter Scharf.


## External functions


### transcoder_processString($line,$from,$to)
A primary user function to transocode the string $line from the coding $from to the coding $to.  E.g.,
transcoder_processString('rAma','slp1','deva') returns राम .

###  transcoder_processElements($line,$from,$to,$tagname) 
This function transcodes *parts* of $line.  The assumption
is that $line contains parts which should be transcoded, and that these parts are delimited in the XML style by the tag $tagname.   For instance  transcoder_processElements('The hero named <SA>rAma</SA>','slp1','deva','<SA>') returns the string 'The hero named राम'. 

### transcoder_standardize_filter
Function transcoder_standardize_filter($filter) returns a 'standardized' name for the value of $filter.
Historically, code used by Cologne has used alternate names for Sanskrit coding schemes; e.g., "SKTROMANUNICODE", "roman" and "iast".  It is assumed that applications will provide a standardized spellings for the $from and $to parameters of the transcoder_processString and  transcoder_processElements functions.  

###  transcoder_set_dir($dir)

Allows user to change the $transcoder_dir global variable; thus, applications can provide their own from_to.xml files, rather than using those in the transcoder subdirectory.

###  transcoder_get_dir()
Returns current value of the $transcoder_dir global variable

----

## Internal global variables
### $transcoder_dir
The directory containing the from_to.xml files. Default is the *utilities/transcoder* directory.  Can be
changed by function *transcoder_set_dir($dir)*.

### $transcoder_fsmarr
Associative array containing the finite state machines.  When a from_to.xml file is parsed, the result is a data structure contained in a PHP (local) variable, $fsm. We save this for later use:  $transcoder_fsmarr[<from_to>]=$fsm.
E.g., $transcoder_fsmarr['slp1_deva'] = $fsm.

### $transcoder_htmlentities
Contains a boolean value, initially 'false'.  Appears to have no substantive use, so could be removed.

----

## Internal functions

### transcoder_processString_main($line,$fsm)
Function transforms the string in variable $line, according to
the finite state machine in variable $fsm, and returns the resulting string.

### transcoder_processString_match
Used by function transcoder_processString_main.  Details not clear.

### transcoder_processElements_callback
Function used by transcoder_processElements with PHP regular expression matching.


### transcoder_fsm($from,$to)
Function parses a transcoder file (from_to.xml) and stores the resulting finite state machine in $transcoder_fsmarr['from_to'] (see above).

### unichr($dec)
Converts the integer value in $dec to a unicode string in utf-8 format.

### unichr_alt($u)
Unused function, so could be removed.

### transcoder_unicode_parse_alt($val)
Function only used when $transcoder_htmlentities is 'true'. So probably could be removed.

### transcoder_unicode_parse($val)
Function converts $val to a unicode string.  $val is assumed to be a string like '\uXXXX'  where each X is a hex digit.  

### transcoder_unicode_parse_old ($val)
Another implementation of transcoder_unicode_parse function.  Function could be removed.

### transcoder_dbg
Function transcoder_dbg($line,$from,$to,$ans) writes a debug message to the 'tempout' file in the directory containing this transcoder.php file.

