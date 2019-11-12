# listview
listview.php can function as a restful endpoint, either as a web page or as a component in a web page.

## listview as a web page

listview.php can be used directly to generate a web display. As an experiment, click on the following link, opening in a new browser window.

https://www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/listview.php?dict=mw&key=harika&input=slp1&output=deva&accent=no

We'll provide more details on the parameters below.  But explore a bit first.

* click on a word in the list to the left, say *hari*.   The definition of the clicked word is now in right pane
* click on the up and down triangles in the list pane.  The list now shows words before or after.
* reload the page to show definition of *harika*.  In right pane, click on the second (yellow) arrow.  Note that the list recenters to this second homonym, and the arrows change colors (in right pane).
* edit the url to show 'dict=ap90' (Apte dictonary of 1890).  Notice that the right pane shows 'not found', but the left pane shows words with 'harikaH' in the middle.  This is because Apte's headword shows the (masculine) nominative singular form, ending in visarga.  Click on 'harikaH' in left pane and you'll see Apte's definition in right pane.
* right click in the display, and 'view page source'.  You'll see that the page loads in css and javascript. These provide visual details and functionality. 

## listview as a component

Note: The term *component* is used in a loose sense here.  As our understanding of the *web component* technology improves,  we may at sometime be able to make this listview into a genuine [Web Component](https://en.wikipedia.org/wiki/Web_Components) .

listview can be embedded in an **iframe** as part of a larger web application.  

For example, the [simple search](https://www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/simple-search/v1.0/list-0.2s.html) display at Cologne.


This display has various user-friendly controls to set parameters for an ajax call to listview.php like above.  But the result is put into an iframe.   If you 'view source', you'll see 
* toward the bottom, an iframe specification:
  ```
   <iframe id="dataframe">  
   <p>Your browser does not support iframes.</p>
   </iframe>
  ```
* In the *listDisplay* javascript function, you'll see a jQuery ajax call to listview.php which puts the results into the iframe with `id="dataframe"`.   
  * **TODO** Must the listview code be put into an *iframe* ? Can it be put into a 'div'? 
* If you then (a) look up a word (say harika in mw), (b) right click in the iframe and 'View frame source',  then you will see the same code as in the 'view source' example of the 'listview as a web page' example above.


## input parameters
The most important restful parameters for listview are: *dict, key, input, output, and accent* (see first example above).   

Since listview 'includes' listhier, parameters used by listhier calls are also used indirectly. These  parameters are *lnum and direction*.

See [Restful Parameters](restfulparm.md) Parameters for details.
