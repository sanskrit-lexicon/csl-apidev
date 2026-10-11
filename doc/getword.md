_Created: 11-11-2019 · Last updated: 11-10-2026_


# getword

The getword endpoint was written to work as a subcomponent of the listview endpoint.

But it does generate html, and can be used in other contexts.  It knows about the same six restful parameters as does listview.  The basic four parameters are *dict, key, input, output*.  The *accent* parameter has default value 'no' (Sanskrit accents not shown).  The *dispcss* parameter has default value 'yes' (stylesheet css/basic.css is loaded).

## with listview parameters
Let's start with an example of the listview display:
https://www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/listview.php?dict=mw&key=harika&input=slp1&output=iast

We can replace 'listview.php' by 'getword.php' and will get only the entry display of the right hand pane of listview display.
https://www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/getword.php?dict=mw&key=harika&input=slp1&output=iast

Note that this is almost identical to the listview display.  The only difference I notice is that the homonym arrows are not colored in the getword display.  This is because the css styling classes are defined in css/listview.css, which is not included as part of getword.   Notice that css/basic.css *is* included by default.

## with parameter dispcss=no
To illustrate the impact of NOT loading css/basic.css,  consider this example, where also the output is changed to Devanagari.
https://www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/getword.php?dict=mw&key=harika&input=slp1&output=deva&dispcss=no

Inspecting the devanagari text हरिक , we see that it is rendered with the Devanagari Unicode block using the browser default font (which is Nirmala for a windows 10 pc).
By contrast, with basic.css loaded (as previous example), the css and getword markup cause the Devanagari text to be rendered with the Siddhanta font.

## A 'Basic' display with getword 
getword.php has utility in addition to its use as a listview component.  Several examples of using getword.php as part of a VueJS application are https://funderburkjim.github.io/sanlex-vue/index.html.  For example there are several examples which are functionally quite similar to the Basic Displays (such as https://www.sanskrit-lexicon.uni-koeln.de/scans/MWScan/2014/web/webtc/indexcaller.php).

## Open Graph (og:) meta (H6478)

Full-page mode (`dispopt` 1/2) emits the canonical og: + twitter:card meta set
in the `<head>`, built by `Getword_data::ogmeta_html()` from the first matched
record: `og:title` = headword Devanagari + IAST, `og:description` = the first
sense line (tags stripped, 160-char cap), `og:url` = the Cologne-host page,
`og:image` = `https://samskrtam.ru/og/<token>.png` (the kosha og card service;
`<token>` is the filesystem-safe SLP1 encoding). Fragment mode (`dispopt=3`),
JSONP replies without a page wrapper, and not-found replies carry no og: block.
The data half lives in the fork-synced `getword_data.php` (twin-first, see
[WEB_BACKEND_MANUAL](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/docs/WEB_BACKEND_MANUAL.md) §4.1/§7); the host bases are passed by
`getwordClass.php` (deliberately divergent, §7.3).

_Dr. Mārcis Gasūns_
