
# listhier

The listhier endpoint was written to work as a subcomponent of the [listview](listview.md) endpoint.

But listhier does generate html, and viewing urls constructed with it gives insight into its different uses.

  
## with listview parameters

Let's start with an example of the listview display:
https://www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/listview.php?dict=mw&key=harika&input=slp1&output=iast

To display *just the list* part of this display, we replace 'listview.php' with 'listhier.php', and the same parameters: 
https://www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/listhier.php?dict=mw&key=harika&input=slp1&output=iast
If we look at the source of this listhier display, we see that each word in the list is associated with a clickable link to a Javascript function. These functions are defined in js/listview.js; since listview.php includes this javascript library, clicking on the links changes the display.  However, listhier.php does not, by itself, include these JS functions, so clicking on the words in the linkhier display has no effect.

## with the 'direction' parameter
In the previous example, the keyword 'harika' is in the middle of the list; this is because the default value of the *direction* parameter is CENTER.  
But we can explictly include the direction parameter. For example, with the value 'UP',  the keyword 'harika' will be at the bottom of the list:
https://www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/listhier.php?dict=mw&key=harika&input=slp1&output=iast&direction=UP

Similarly, with direction parameter value of DOWN, the key 'harika' appears at the top of the list:
https://www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/listhier.php?dict=mw&key=harika&input=slp1&output=iast&direction=DOWN

## with the 'lnum' parameter
From the above *listview* example of 'harika', we note that 'harika' has two homonyms.  When we construct the listhier display with *key=harika*, the list is constructed with respect to the *first* homonym.
However, the 'lnum' parameter allows the list to be constructed with respect to another homonym.  Note that the Cologne ID=261324 for the second homonym of harika. If the 'lnum=261324' restful parameter is passed to listhier,
then the list is constructed with respect to the record with this Cologne ID, i.e., with respect to the second homonym of 'harika':
https://www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/listhier.php?dict=mw&lnum=261324&input=slp1&output=iast

Incidentally, if both the *key* and *lnum* parameters appear in the url, then the *lnum* parameter takes precedence.
Also note that the *direction* parameter could be used with the *lnum* parameter.

