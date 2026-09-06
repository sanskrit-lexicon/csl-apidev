_Created: 11-11-2019 · Last updated: 05-09-2026_

# getword_xml

getword_xml.php is in an 'alpha' (highly experimental) state, and is used by no displays or sample displays.

The idea was to have an api call that would return the xml form of the data from a given dictionary;  it would be
assumed that Javascript code provided by the calling application would provide the display of this data.

The parameters are the 5 primary ones used by the [getword](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/doc/getword.md) display: *dict, key, input, output, accent*.

Two additional, mutually exclusive selector parameters exist (H4212 documentation
of previously undocumented behavior — the code paths are in
`getwordXmlClass.php`):

- `lnum` — return the record(s) at this Cologne `lnum` (uses `Dal::get2`).
  When present, `key` is ignored.
- `regex` — wildcard key search: `*` is translated to the SQL `%` wildcard and
  `?` to `_`, then matched with `Dal::get3b`, capped at 100 records. A leading
  `*` therefore scans the whole key index. Documented for completeness; the
  parameter set remains experimental ('alpha') as above.

Precedence: `lnum` > `regex` > `key`.

Data is returned in a JSON object with (a) the given 5 inputs and (b) an 'xml' attribute' whose value is an array of strings.  The array has as many elements as there are records the the dictionary xml file with 'key1 == key'.
In the following example, there are 2 elements in the returned xml array.

Here is an example from the browser:

https://www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/getword_xml.php?dict=mw&key=harika&input=slp1&output=slp1&accent=no.  

It is actually better to view this result using 'show source' of browser, since the xml markup of the array elements does not display nicely in a main browser windown.

Compare this to the getword display:
https://www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/getword.php?dict=mw&key=harika&input=slp1&output=slp1&accent=no.  

As mentioned, this is in a very preliminary form.   One could imagine having a more robust form which would return all resources needed to construct a dictionary display; the display itself would be constructed by Javascript code.

_Dr. Mārcis Gasūns_
