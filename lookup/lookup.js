/* lookup.js -- client logic for lookup/index.php (doc/roadmap_lookup.md
   Wave 1). Vanilla ES6, zero external resources, no build step, plain
   classic script (not type="module" -- avoids the .mjs MIME-type trap
   documented in Uprava/FINDINGS.md #3).

   Flow: one dalglob.php call resolves which dictionaries/homonym keys
   contain the citation, then one getword_batch.php round-trip per
   dictionary fetches all of that dictionary's entries in a single
   request. If getword_batch.php isn't deployed yet (HTTP 404 -- the
   server checkout hasn't pulled it), falls back to sequential
   getword.php fetches spaced ~250ms apart with exponential backoff on
   429, so the page keeps working against today's endpoints regardless
   (doc/roadmap_lookup.md D2 / Wave 1 non-goal: no endpoint-contract
   changes). Never a parallel per-homonym burst -- that burst is the
   429/TLS flakiness driver this rewrite exists to remove. */
(function () {
 'use strict';

 var DEVA_RE = /[ऀ-ॿ]/;
 var IAST_RE = /[āīūṛṝḷḹṃṅṇṣṭṱḍḹśṣĀĪŪṚṜḶḸṂṄṆṢṬṰḌḸŚṢ]/;
 // The global lookup has no per-dictionary context (unlike simple-search),
 // so autocomplete suggestions are drawn from MW's headword ngram index --
 // the broadest general-purpose dictionary available via getsuggest.php.
 var SUGGEST_DICT = 'mw';
 var ASCII_SCHEMES = ['hk', 'slp1', 'itrans'];
 var SPACING_MS = 250;
 var MAX_RETRIES = 4;

 var els = {};
 var state = { dicts: [], tabs: [] };

 function qs(sel) { return document.querySelector(sel); }

 function debounce(fn, wait) {
  var t;
  return function () {
   var args = arguments, ctx = this;
   clearTimeout(t);
   t = setTimeout(function () { fn.apply(ctx, args); }, wait);
  };
 }

 function sleep(ms) { return new Promise(function (resolve) { setTimeout(resolve, ms); }); }

 // getword.php / getword_batch.php entry HTML links 'css/basic.css'
 // relative to the repo root; lookup/ sits one level down (a sibling of
 // sample/), so the reference needs the same correction sample/dalglob1.php
 // already applies for the identical reason. (getwordClass.php's dispcss=no
 // parameter looks like the "right" fix but is currently dead code --
 // see the comment in getword_batch.php.)
 function fixCssPath(html) { return html.replace('css/basic.css', '../css/basic.css'); }

 // ---- input-scheme detection -----------------------------------------

 function detectScript(text) {
  if (DEVA_RE.test(text)) { return 'deva'; }
  if (IAST_RE.test(text)) { return 'roman'; }
  return null; // plain ASCII: HK/SLP1/ITRANS can't be auto-distinguished
 }

 function resolveScheme() {
  var auto = detectScript(els.key.value);
  if (auto) {
   els.schemeField.hidden = true;
   els.schemeAuto.hidden = false;
   els.schemeAuto.textContent = 'auto-detected: ' + (auto === 'deva' ? 'Devanagari' : 'IAST');
   return auto;
  }
  els.schemeField.hidden = false;
  els.schemeAuto.hidden = true;
  return els.scheme.value;
 }

 function updatePreview() {
  var key = els.key.value.trim();
  if (!key) { els.preview.textContent = ''; return; }
  var scheme = resolveScheme();
  els.preview.textContent = 'Will search “' + key + '” as ' + scheme.toUpperCase() + '.';
 }

 function setStatus(text, kind) {
  els.status.textContent = text || '';
  els.status.className = 'lk-status' + (kind ? ' lk-status-' + kind : '');
 }

 function clearResults() {
  els.dictlist.innerHTML = '';
  els.tabs.hidden = true;
  els.tabs.innerHTML = '';
  els.panels.innerHTML = '';
  state.dicts = [];
  state.tabs = [];
 }

 // ---- network ----------------------------------------------------------

 function fetchText(url) {
  return fetch(url).then(function (res) {
   return res.text().then(function (text) {
    return { ok: res.ok, status: res.status, text: text };
   });
  });
 }

 function fetchDalglob(key, input, output) {
  var url = '../dalglob.php' +
   '?key=' + encodeURIComponent(key) +
   '&input=' + encodeURIComponent(input) +
   '&output=' + encodeURIComponent(output) +
   '&accent=no' +
   '&dbglob=keydoc_glob1';
  return fetchText(url).then(function (res) {
   if (!res.ok) { throw new Error('HTTP ' + res.status); }
   return JSON.parse(res.text);
  });
 }

 function fetchBatch(dict, dockeys, output) {
  var url = '../getword_batch.php' +
   '?dict=' + encodeURIComponent(dict) +
   '&keys=' + encodeURIComponent(dockeys.join(',')) +
   '&input=slp1' + // dockeys from dalglob.php are already SLP1-normalized
   '&output=' + encodeURIComponent(output) +
   '&accent=no';
  return fetchText(url).then(function (res) {
   if (res.status === 404) { return null; } // not deployed yet -> fall back
   if (!res.ok) { throw new Error('HTTP ' + res.status); }
   var items = JSON.parse(res.text);
   items.forEach(function (item) { item.html = fixCssPath(item.html); });
   return items;
  });
 }

 function fetchOneWithBackoff(dict, key, output, attempt) {
  attempt = attempt || 0;
  var url = '../getword.php' +
   '?key=' + encodeURIComponent(key) +
   '&output=' + encodeURIComponent(output) +
   '&dict=' + encodeURIComponent(dict) +
   '&accent=no' +
   '&input=slp1' +
   '&dispcss=no';
  return fetchText(url).then(function (res) {
   if (res.status === 429 && attempt < MAX_RETRIES) {
    var backoff = SPACING_MS * Math.pow(2, attempt + 1);
    setStatus('Server is throttling requests — retrying in ' + Math.round(backoff / 1000) + 's...', 'retry');
    return sleep(backoff).then(function () {
     return fetchOneWithBackoff(dict, key, output, attempt + 1);
    });
   }
   if (!res.ok) {
    return { key: key, status: res.status, html: '<p>Network error (' + res.status + ') for ' + key + '</p>' };
   }
   return { key: key, status: 200, html: fixCssPath(res.text) };
  });
 }

 function fetchSequential(dict, dockeys, output) {
  var results = [];
  var chain = Promise.resolve();
  dockeys.forEach(function (key, i) {
   chain = chain
    .then(function () { return i > 0 ? sleep(SPACING_MS) : null; })
    .then(function () { return fetchOneWithBackoff(dict, key, output); })
    .then(function (r) { results.push(r); });
  });
  return chain.then(function () { return results; });
 }

 function loadDictEntries(dictRec, output) {
  setStatus('Loading ' + dictRec.dict.toUpperCase() + '...', 'loading');
  return fetchBatch(dictRec.dict, dictRec.dockeys, output)
   .then(function (batchResults) {
    return batchResults || fetchSequential(dictRec.dict, dictRec.dockeys, output);
   })
   .then(function (results) {
    setStatus('', null);
    renderTabs(dictRec.dict, results);
   })
   .catch(function (err) {
    setStatus('Network error loading ' + dictRec.dict.toUpperCase() + ': ' + err.message, 'error');
   });
 }

 // ---- rendering ----------------------------------------------------------

 function renderDictButtons(dicts, output) {
  els.dictlist.innerHTML = '';
  dicts.forEach(function (dictRec, i) {
   var btn = document.createElement('button');
   btn.type = 'button';
   btn.className = 'lk-dictbtn';
   btn.textContent = dictRec.dict.toUpperCase() + ' (' + dictRec.dockeys.length + ')';
   btn.setAttribute('aria-pressed', i === 0 ? 'true' : 'false');
   btn.addEventListener('click', function () {
    Array.prototype.forEach.call(els.dictlist.children, function (b) { b.setAttribute('aria-pressed', 'false'); });
    btn.setAttribute('aria-pressed', 'true');
    loadDictEntries(dictRec, output);
   });
   els.dictlist.appendChild(btn);
  });
 }

 function renderTabs(dict, results) {
  state.tabs = results;
  els.tabs.innerHTML = '';
  els.panels.innerHTML = '';
  els.tabs.hidden = results.length < 2; // one homonym needs no tab chrome

  results.forEach(function (r, i) {
   var tabId = 'lk-tab-' + dict + '-' + i;
   var panelId = 'lk-panel-' + dict + '-' + i;

   var tab = document.createElement('button');
   tab.type = 'button';
   tab.id = tabId;
   tab.className = 'lk-tab';
   tab.setAttribute('role', 'tab');
   tab.setAttribute('aria-controls', panelId);
   tab.setAttribute('aria-selected', i === 0 ? 'true' : 'false');
   tab.tabIndex = i === 0 ? 0 : -1;
   tab.textContent = r.key + (r.status === 404 ? ' (not found)' : '');
   tab.addEventListener('click', function () { activateTab(i); });
   els.tabs.appendChild(tab);

   var panel = document.createElement('div');
   panel.id = panelId;
   panel.className = 'lk-panel';
   panel.setAttribute('role', 'tabpanel');
   panel.setAttribute('aria-labelledby', tabId);
   panel.hidden = i !== 0;
   panel.innerHTML = r.html;
   els.panels.appendChild(panel);
  });

  function activateTab(index) {
   var tabs = els.tabs.querySelectorAll('[role="tab"]');
   var panels = els.panels.querySelectorAll('[role="tabpanel"]');
   Array.prototype.forEach.call(tabs, function (t, i) {
    var selected = i === index;
    t.setAttribute('aria-selected', selected ? 'true' : 'false');
    t.tabIndex = selected ? 0 : -1;
    if (selected) { t.focus(); }
   });
   Array.prototype.forEach.call(panels, function (p, i) { p.hidden = i !== index; });
  }

  els.tabs.onkeydown = function (evt) {
   var tabs = Array.prototype.slice.call(els.tabs.querySelectorAll('[role="tab"]'));
   var current = tabs.indexOf(document.activeElement);
   if (current === -1) { return; }
   var next = null;
   if (evt.key === 'ArrowRight') { next = (current + 1) % tabs.length; }
   else if (evt.key === 'ArrowLeft') { next = (current - 1 + tabs.length) % tabs.length; }
   else if (evt.key === 'Home') { next = 0; }
   else if (evt.key === 'End') { next = tabs.length - 1; }
   if (next !== null) {
    evt.preventDefault();
    activateTab(next);
   }
  };
 }

 // ---- autocomplete ----------------------------------------------------

 var fetchSuggestions = debounce(function (term) {
  if (term.length < 2) { els.suggestions.innerHTML = ''; return; }
  var url = '../getsuggest.php' +
   '?term=' + encodeURIComponent(term) +
   '&dict=' + SUGGEST_DICT +
   '&input=' + encodeURIComponent(resolveScheme());
  fetchText(url).then(function (res) {
   if (!res.ok) { return; }
   var matches;
   try { matches = JSON.parse(res.text); } catch (e) { return; }
   els.suggestions.innerHTML = '';
   matches.forEach(function (m) {
    var opt = document.createElement('option');
    opt.value = m;
    els.suggestions.appendChild(opt);
   });
  }).catch(function () { /* suggestions are best-effort */ });
 }, 250);

 // ---- top-level search --------------------------------------------------

 function runSearch() {
  var key = els.key.value.trim();
  clearResults();
  setStatus('', null);
  if (!key) { return; }
  var scheme = resolveScheme();
  var output = els.output.value;

  setStatus('Searching...', 'loading');
  fetchDalglob(key, scheme, output).then(function (data) {
   if (data.status !== 200 || !data.dicts.length) {
    setStatus('“' + key + '” was not found in any dictionary.', 'notfound');
    return;
   }
   setStatus('', null);
   state.dicts = data.dicts;
   renderDictButtons(data.dicts, output);
   loadDictEntries(data.dicts[0], output);
  }).catch(function (err) {
   setStatus('Network error: ' + err.message, 'error');
  });
 }

 // ---- init ----------------------------------------------------------

 function init() {
  els.form = qs('#lk-form');
  els.key = qs('#lk-key');
  els.suggestions = qs('#lk-suggestions');
  els.schemeField = qs('#lk-scheme-field');
  els.scheme = qs('#lk-scheme');
  els.schemeAuto = qs('#lk-scheme-auto');
  els.output = qs('#lk-output');
  els.preview = qs('#lk-preview');
  els.status = qs('#lk-status');
  els.dictlist = qs('#lk-dictlist');
  els.tabs = qs('#lk-tabs');
  els.panels = qs('#lk-panels');

  els.form.addEventListener('submit', function (evt) {
   evt.preventDefault();
   runSearch();
  });
  els.key.addEventListener('input', function () {
   updatePreview();
   fetchSuggestions(els.key.value.trim());
  });
  els.scheme.addEventListener('change', updatePreview);
  els.output.addEventListener('change', updatePreview);

  var prefill = window.LOOKUP_PREFILL || {};
  if (ASCII_SCHEMES.indexOf(prefill.input) !== -1) { els.scheme.value = prefill.input; }
  if (prefill.output) { els.output.value = prefill.output; }
  updatePreview();
  if (prefill.key) { runSearch(); }
 }

 if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
 } else {
  init();
 }
})();
