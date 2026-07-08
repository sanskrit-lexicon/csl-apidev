/* home.js -- catalogue homepage for app/home.php (slice 2, Proposal A
   "Research Workbench" / doc/ux-redesign/proposal-brief.md "Homepage =
   Search + dictionary catalogue").

   Renders one card per dictionary from the static ../lookup/dictmeta.js
   table (window.LOOKUP_DICTMETA, 45 rows of [code, title, year, yearOlder]);
   no server call is needed, so this page works fully offline. Language is
   derived mechanically from the title with the SAME classifyLang() rule as
   lookup/lookup.js (English/Français/Wörterbuch/Glossarium/other) -- kept in
   sync deliberately, not a second scholarly claim. Cards deep-link into the
   detail route dict.php?dict=CODE. */
(function () {
 'use strict';

 // classifier kept byte-identical to lookup/lookup.js classifyLang()
 function classifyLang(title) {
  if (/english/i.test(title)) { return 'en'; }
  if (/fran[çc]ais|french/i.test(title)) { return 'fr'; }
  if (/wörterbuch|woerterbuch/i.test(title)) { return 'de'; }
  if (/glossarium/i.test(title)) { return 'la'; }
  return 'other';
 }
 var LANG_LABEL = { en: 'English', fr: 'Français', de: 'Deutsch', la: 'Latin', other: 'Other' };

 var DICTS = (window.LOOKUP_DICTMETA || []).map(function (row) {
  return { code: row[0], title: row[1], year: row[2], lang: classifyLang(row[1]) };
 });

 var state = { q: '', lang: '' };
 var els = {};

 function esc(s) {
  return String(s).replace(/[&<>"]/g, function (c) {
   return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
  });
 }

 function matches(d) {
  if (state.lang && d.lang !== state.lang) { return false; }
  if (!state.q) { return true; }
  var q = state.q.toLowerCase();
  return d.code.toLowerCase().indexOf(q) !== -1 ||
         d.title.toLowerCase().indexOf(q) !== -1;
 }

 function render() {
  var shown = DICTS.filter(matches);
  els.grid.innerHTML = shown.map(function (d) {
   return '<a class="hm-card" href="dict.php?dict=' + encodeURIComponent(d.code.toLowerCase()) + '">' +
    '<div class="hm-card-top">' +
     '<span class="hm-card-code">' + esc(d.code) + '</span>' +
     '<span class="hm-card-lang">' + esc(LANG_LABEL[d.lang]) + '</span>' +
    '</div>' +
    '<div class="hm-card-title">' + esc(d.title) + '</div>' +
    (d.year ? '<div class="hm-card-year">Edition ' + esc(d.year) + '</div>' : '') +
   '</a>';
  }).join('');
  els.empty.hidden = shown.length > 0;
  els.count.textContent = shown.length === DICTS.length
   ? DICTS.length + ' dictionaries'
   : shown.length + ' of ' + DICTS.length;
 }

 function renderLangChips() {
  var counts = {};
  DICTS.forEach(function (d) { counts[d.lang] = (counts[d.lang] || 0) + 1; });
  // stable, readable order; only show buckets that exist
  var order = ['en', 'de', 'fr', 'la', 'other'].filter(function (l) { return counts[l]; });
  var html = '<button type="button" class="hm-langchip" data-lang="" aria-pressed="true">All</button>';
  html += order.map(function (l) {
   return '<button type="button" class="hm-langchip" data-lang="' + l + '" aria-pressed="false">' +
    esc(LANG_LABEL[l]) + ' ' + counts[l] + '</button>';
  }).join('');
  els.langs.innerHTML = html;
  Array.prototype.forEach.call(els.langs.querySelectorAll('.hm-langchip'), function (chip) {
   chip.addEventListener('click', function () {
    state.lang = chip.getAttribute('data-lang');
    Array.prototype.forEach.call(els.langs.querySelectorAll('.hm-langchip'), function (c) {
     c.setAttribute('aria-pressed', c === chip ? 'true' : 'false');
    });
    render();
   });
  });
 }

 function init() {
  els.grid = document.getElementById('hm-grid');
  els.empty = document.getElementById('hm-empty');
  els.count = document.getElementById('hm-count');
  els.search = document.getElementById('hm-search');
  els.langs = document.getElementById('hm-langs');
  if (window.THEME) { window.THEME.wire(document.getElementById('ap-theme')); }

  els.search.addEventListener('input', function () {
   state.q = els.search.value.trim();
   render();
  });
  renderLangChips();
  render();
 }

 if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
 } else {
  init();
 }
}());
