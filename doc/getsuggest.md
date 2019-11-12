# getsuggest

getsuggest.php returns a list of words from a given dictionary with a given prefix.

10 or fewer words are returned.

The words are returned as a JSON array of strings.

For a dictionary with Sanskrit headwords, the spelling can be in one of the Sanskrit encodings known by [transcoder](transcoder.md) such as slp1, hk, iast, deva, itrans.   

getsuggest also works for a dictionary with English headwords.

To see getsuggest in action, use the [simple search display](https:www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/simple-search/v1.0/list-0.2s.html), and choose as 'input' method any of the methods except *simple*.  In the citation box, type the first 2 or more letters of a word.  You will see the list of matches returned by getsuggest as a list below the citation field. The mouse ('hand pointer') may be moved in the list to a desired word, then a mouse click shows the entry for the selected word.  This user interface is accomplished by the
JQuery UI autocomplete widget, with *getsuggest* providing the list of words.


## Parameters
The list of parameters is similar to those for listview:  *dict, term, input*.  One difference is that *term* is used (for conformity with JQuery UI autocomplete) instead of *key*;  also 'output' is NOT used -- the returned list is in the encoding specified by 'input'.

As usual with restful APIs,  getsuggest can be used directly as part of a browser url:

https://www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/getsuggest.php?dict=mw&input=slp1&term=sev

