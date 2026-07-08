/* dict.js -- dictionary-detail route for app/dict.php (slice 2, Proposal A;
   doc/ux-redesign/proposal-brief.md "MW midpage -> Dictionary detail route
   inside unified UI").

   The dictionary code comes from ?dict=CODE. It is validated against the
   static ../lookup/dictmeta.js table before use (unknown codes get a
   graceful fallback), so nothing user-controlled is ever interpolated raw --
   every value written to the DOM is either from the trusted table or passed
   through esc()/encodeURIComponent(). Works fully offline (no server call).

   The detail page is a lightweight hub: metadata + a search-within box that
   deep-links into the unified search pre-filtered to this dictionary
   (index.php?key=...&dict=CODE), plus links out to the whole-corpus search
   and the classic Cologne interface. Per the brief, scan links stay entry
   actions inside the reader (servepdf.php), not a page-level split. */
(function () {
 'use strict';

 function classifyLang(title) {
  if (/english/i.test(title)) { return 'en'; }
  if (/fran[çc]ais|french/i.test(title)) { return 'fr'; }
  if (/wörterbuch|woerterbuch/i.test(title)) { return 'de'; }
  if (/glossarium/i.test(title)) { return 'la'; }
  return 'other';
 }
 var LANG_LABEL = { en: 'English', fr: 'Français', de: 'Deutsch', la: 'Latin', other: 'Sanskrit / other' };

 var BY_CODE = {};
 (window.LOOKUP_DICTMETA || []).forEach(function (row) {
  BY_CODE[row[0].toLowerCase()] = { code: row[0], title: row[1], year: row[2], yearOlder: row[3] };
 });

 function esc(s) {
  return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) {
   return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
  });
 }
 function getParam(name) {
  return new URLSearchParams(window.location.search).get(name) || '';
 }
 function setText(id, text) { document.getElementById(id).textContent = text; }

 function renderUnknown(raw) {
  document.title = 'Cologne Sanskrit Lexicon — Dictionary not found';
  setText('dt-crumb', 'Not found');
  setText('dt-code', '?');
  setText('dt-title', raw ? 'Unknown dictionary “' + raw + '”' : 'No dictionary selected');
  document.getElementById('dt-meta').innerHTML = '';
  document.getElementById('dt-search').hidden = true;
  document.getElementById('dt-about').textContent =
   'That dictionary code is not in the catalogue.';
  document.getElementById('dt-actions').innerHTML =
   '<a class="dt-action" href="home.php">' +
    '<span class="dt-action-ico">&#128218;</span>' +
    '<span class="dt-action-text"><b>Browse all dictionaries</b>' +
    '<span>Return to the catalogue</span></span></a>';
 }

 function action(href, icon, title, sub, external) {
  return '<a class="dt-action" href="' + href + '"' +
   (external ? ' target="_blank" rel="noopener"' : '') + '>' +
   '<span class="dt-action-ico" aria-hidden="true">' + icon + '</span>' +
   '<span class="dt-action-text"><b>' + esc(title) + '</b><span>' + esc(sub) + '</span></span>' +
   '</a>';
 }

 function render(d) {
  var codeUpper = d.code.toUpperCase();
  var codeUrl = encodeURIComponent(d.code.toLowerCase());
  document.title = 'Cologne Sanskrit Lexicon — ' + codeUpper;

  setText('dt-crumb', codeUpper);
  setText('dt-code', codeUpper);
  setText('dt-title', d.title);
  document.getElementById('dt-dict-field').value = d.code.toLowerCase();
  document.getElementById('dt-key').setAttribute('aria-label', 'Look up a word in ' + d.title);

  var meta = [];
  meta.push('<span><b>Language</b> ' + esc(LANG_LABEL[classifyLang(d.title)]) + '</span>');
  if (d.year) { meta.push('<span><b>Digital edition</b> ' + esc(d.year) + '</span>'); }
  if (d.yearOlder && d.yearOlder !== d.year) {
   meta.push('<span><b>Earlier edition</b> ' + esc(d.yearOlder) + '</span>');
  }
  meta.push('<span><b>Code</b> ' + esc(codeUpper) + '</span>');
  document.getElementById('dt-meta').innerHTML = meta.join('');

  document.getElementById('dt-actions').innerHTML =
   action('index.php?dict=' + codeUrl, '&#128269;',
          'Search ' + codeUpper, 'Open the unified search filtered to this dictionary') +
   action('index.php', '&#127760;',
          'Search all dictionaries', 'Look a word up across the whole corpus') +
   action('//www.sanskrit-lexicon.uni-koeln.de/', '&#128196;',
          'Classic interface', 'The original Cologne pages for this dictionary', true);

  var about = d.title + ' (' + codeUpper + ')';
  if (d.year) { about += ', digital edition ' + d.year; }
  about += '. Part of the Cologne Digital Sanskrit Dictionaries. Use the search ' +
   'box above to look up a headword; results open in the reader alongside the ' +
   'same word in every other dictionary that has it.';
  document.getElementById('dt-about').textContent = about;
 }

 function init() {
  if (window.THEME) { window.THEME.wire(document.getElementById('ap-theme')); }
  var raw = getParam('dict');
  var d = BY_CODE[raw.toLowerCase()];
  if (d) { render(d); } else { renderUnknown(raw); }
 }

 if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
 } else {
  init();
 }
}());
