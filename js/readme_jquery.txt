readme_jquery.txt
_Created: 05-03-2023 · Last updated: 24-07-2026_

## History
- 05-03-2023: Dependabot alert for XSS in jQuery < 1.6.3; bumped
  js/jquery.min.js from 1.4.3 → 1.6.3 (saved as jquery.min.prev.js).
- Later: js/jquery.min.js advanced to 3.5.0.
- 23-07-2026 (H1523 / csl-apidev#33): simple-search and sample pages that
  still loaded //code.jquery.com/jquery-2.1.4.min.js (XSS CVEs fixed in 3.4+)
  now use jquery-3.7.1.min.js. Cookie stack is js-cookie (see PR #94).
- 23–24-07-2026 (H1523): local js/jquery.min.js bumped 3.5.0 → 3.7.1
  (listview.php and any other local loaders).

## Current layout
- js/jquery.min.js — jQuery 3.7.1 (local; listview.php)
- CDN simple-search / sample: jquery-3.7.1.min.js
- jquery-ui 1.11.4 remains on cdnjs (separate package)

Check live version in browser console: `$.fn.jquery`
