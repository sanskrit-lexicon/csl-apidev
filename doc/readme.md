# apidev

This is documentation regarding the 
[csl-apidev repository](https://github.com/sanskrit-lexicon/csl-apidev).

```
The path to this directory at Cologne is currently scans/awork/apidev.  
There is also a Unix softlink from *scans/csl-apidev* to scans/awork/apidev/
```


*csl-apidev* is a development version of the Cologne sanskrit-lexicon Application Programming Interface. The basic purpose is to provide software components that may be used to develop web pages making use of the Cologne sanskrit-lexicon dictionaries.

In the mid-2000s, Malcolm Hyman and Peter Scharf envisioned providing accessibility to the data of the dictionary 
digitizations via an API.   The present 2019 form of *apidev* was implemented by Funderburk (starting around 2015) as materialization of this dictionary API idea.

The API uses the PHP programming language, as well as some (currently small) amount of Javascript, and some special purpose CSS.  The current reliability status of the API should be considered well-tested beta.  

The API style of apidev entry points is a RESTful api.  The base url of an API call currently starts with:

 **https://www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/X.php**.  Restful parameters can be passed either as part of the base url  (by a so-called "GET" request) or via a "POST" request.  Here **X** specifies the action,
and is currently one of:

|X|description|
|---|----------|
|[listview](listview.md) | generate display like simple-search|
|[listhier](listhier.md) | generate the list pane of the listview display|
|[getword](getword.md) | generate the entry display pane of the listview display|
|[getsuggest](getsuggest.md) | return short list of words with a certain prefix|
|[servepdf](servepdf.md) | generates link to scanned images for a particular page of a particular dictionary|
|[getword_xml](getword_xml.md) | for a given headword, return matching records from <dict>.xml.  Currently not used.|

[restfulparm](restfulparm.md) shows all the restful parameters used by any of the endpoints.

[transcoder](transcoder.md) provides details of the transcoding from one to another computer representation of Sanskrit.

The current convention of restful interface APIs is to return data in JSON format.
This is the case with *getsuggest*, but the other formats are returned as strings of HTML.  

The links above will provide more details, in particular the expected input parameters.

