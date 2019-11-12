# servepdf
servepdf.php can function as a restful endpoint, either as a web page or as a component in a web page.

## servepdf as a web page

servepdf.php can be used directly to generate a web display. As an experiment, click on the following link, opening in a new browser window.
Here is an example which returns a web page which displays the scanned image of page number 1234 of the MW dictionary:

https://www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/servepdf.php?dict=mw&page=1234

It is also possible to display the scanned image via a headword; here we spell the headword in hk transliteration:

https://www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/servepdf.php?dict=mw&input=hk&key=gaN


And here's another example where the key is in Devanagari.

https://www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/servepdf.php?dict=mw&input=deva&key=राम


## As web page component
Most of the Cologne web page displays contain a link to the scan page of the entry.   The displays construct a URL like
those shown above, using the 'page' parameter;  that URL is then the href value in a clickable link .

For example, search for word rAma (slp1) in [simple-search](https:www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/simple-search/v1.0/list-0.2s.html).   Then (with Chrome browser) place cursor over link *p=877*, right click and inspect.  You'll see 
```
<a href="//www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/servepdf.php?dict=MW&page=877" target="_MW">877</a>
```

## input parameters
The most important restful parameters for servepdf are: *dict and page* (see first example above) or *dict, key, input*  (see other examples above).

See also [Restful Parameters](restfulparm.md).


## Programming analysis - Cologne server
There is a rather complicated  process by which *servepdf* is used in the context of a web display. Here is a brief guide to the process.

As shown above,  the 'page' parameter is normally used to construct a link to a particular image of a scanned page of a particular dictionary.  
* The 'dict' parameter is used to get an instance of the Dictinfo class
* The get_cologne_weburl method of the Dictinfo instance provides a URL $weburl to the *web* directory for the dictionary.  Similarly, the get_webPath method provides a filesystem relative path $webpath to the *web* directory.
    * For instance, for 'mw' dictionary, 
      * $weburl == https://www.sanskrit-lexicon.uni-koeln.de/scans/MWScan/2020/web
      * $webpath == ../../MWScan/2020/web
* There is a file  ($webpath/webtc/pdffiles.txt) which contains a line for each scan image, of the form
* *page*:image-file-name
* pdffiles.txt is scanned to find the 'image-file-name' corresponding to the *page* parameter
* This image-file-name is assumed to be in the *web/pdfpages* directory, namely at url "$weburl/pdfpages/image-file-name".  For instance with page=1234 and dict=mw, the scanned image is at url
* https://www.sanskrit-lexicon.uni-koeln.de/scans/MWScan/2014/web/pdfpages/mw1234-suvihvala.pdf
* Finally, servepdf.php constructs a web page showing the image at this url, along with next-previous controls.
* And then servepdf.php sends this html code back to the caller.


## Program analysis --  non-Cologne servers
There is some provision for using scanned images from applications run on other servers, such as XAMPP on Windows PCS, or Ubuntu.   If these are set up in a certain simple way, then
* There are several places in a local installation where scanned images may reside. The best location for the images for dictionary xxx is 'cologne/scans/xxx/pdfpages/' directory.  Other locations could be:
  * cologne/xxx/web/pdfpages 
  * cologne/xxx/pdfpages
* If local images are found, then those (local) images will be shown instead of the Cologne images
* If the non-Cologne installation does not have such images, then the Cologne images will be shown.

## Enhancement suggestions
* **TODO** the logic uses a test (in dictinfowhich.php) to determine whether the server being run is the Cologne Sanskrit Lexicon or some other server.  This test may need improvement.
* **TODO** There are copies of the images saved in an Amazon Web Services bucket.  These images could also be used (as still a third source of images), but currently the code does not make this easy to do.
* **TODO** There are also copies of the images in repositories of the [sanskrit-lexicon-scans](https:/github.com/sanskrit-lexicon-scans) Github organization.
* **TODO** servepdf.php is currently is written in a functional style; it should be converted to Class style
  

