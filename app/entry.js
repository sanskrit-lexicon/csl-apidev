/* entry.js -- progressive enhancement ONLY for app/entry.php (H227).
   The page is complete without this file: entries, attribution, scan
   links, citation are all server-rendered. This script merely adds a
   copy-to-clipboard button to the Cite block. Vanilla classic script,
   zero external resources (same constraints as app.js). */
(function () {
 'use strict';

 function addCopy(afterEl, getText, label) {
  if (!afterEl) { return; }
  var btn = document.createElement('button');
  btn.type = 'button';
  btn.className = 'ep-copycite';
  btn.textContent = label;
  btn.addEventListener('click', function () {
   var text = getText();
   var done = function () {
    btn.textContent = 'Copied';
    setTimeout(function () { btn.textContent = label; }, 1500);
   };
   var fail = function () {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); done(); } catch (e) { /* leave label */ }
    document.body.removeChild(ta);
   };
   if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(text).then(done, fail);
   } else {
    fail();
   }
  });
  afterEl.insertAdjacentElement('afterend', btn);
 }

 function init() {
  var cite = document.getElementById('ep-citetext');
  if (cite) {
   addCopy(cite, function () { return cite.textContent.trim(); }, 'Copy citation');
  }
  var bib = document.getElementById('ep-bibtex');
  if (bib) {
   addCopy(bib, function () { return bib.textContent; }, 'Copy BibTeX');
  }
 }

 if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
 } else {
  init();
 }
})();
