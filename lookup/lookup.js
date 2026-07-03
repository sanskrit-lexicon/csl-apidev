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
 var PREFERRED_KEY = 'lookupPreferredDicts';
 var LANG_LABELS = { en: 'English', de: 'German', fr: 'French', la: 'Latin', other: 'Other' };

 var els = {};
 var state = { dicts: [], tabs: [], preferred: loadPreferred(), langFilter: {}, eraFilter: {} };

 // ---- dictionary metadata (doc/roadmap_lookup.md Wave 2 item 1) --------

 // window.LOOKUP_DICTMETA: [dict, title, year, yearOlder][] from dictmeta.js
 var DICTMETA_BY_CODE = {};
 (window.LOOKUP_DICTMETA || []).forEach(function (row) {
  var dict = row[0].toLowerCase();
  var title = row[1];
  DICTMETA_BY_CODE[dict] = { title: title, year: row[2], yearOlder: row[3], lang: classifyLang(title) };
 });

 // Mechanical, auditable classification from the title string only -- see
 // the comment in dictmeta.js for why this isn't a hand-curated per-dict
 // scholarly claim. Titles matching none of these fall into 'other'.
 function classifyLang(title) {
  if (/english/i.test(title)) { return 'en'; }
  if (/fran[çc]ais|french/i.test(title)) { return 'fr'; }
  if (/wörterbuch|woerterbuch/i.test(title)) { return 'de'; }
  if (/glossarium/i.test(title)) { return 'la'; }
  return 'other';
 }

 function metaFor(dict) {
  return DICTMETA_BY_CODE[dict] || { title: dict.toUpperCase(), year: '', yearOlder: null, lang: 'other' };
 }

 function loadPreferred() {
  try { return JSON.parse(window.localStorage.getItem(PREFERRED_KEY)) || []; }
  catch (e) { return []; }
 }

 function savePreferred() {
  try { window.localStorage.setItem(PREFERRED_KEY, JSON.stringify(state.preferred)); }
  catch (e) { /* localStorage unavailable (private mode, quota) -- non-fatal */ }
 }

 function togglePreferred(dict) {
  var i = state.preferred.indexOf(dict);
  if (i === -1) { state.preferred.push(dict); } else { state.preferred.splice(i, 1); }
  savePreferred();
  renderDictCards(state.dicts, state.output);
 }

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

 function uniqSorted(arr) {
  var seen = {}, out = [];
  arr.forEach(function (v) { if (v && !seen[v]) { seen[v] = true; out.push(v); } });
  return out.sort();
 }

 function renderFilterBar(items) {
  var langs = uniqSorted(items.map(function (i) { return i.lang; }));
  var eras = uniqSorted(items.map(function (i) { return i.year; }));
  els.filterbar.innerHTML = '';
  els.filterbar.hidden = langs.length < 2 && eras.length < 2;

  function chipGroup(label, values, activeSet, labelFor) {
   var wrap = document.createElement('div');
   wrap.className = 'lk-filtergroup';
   var span = document.createElement('span');
   span.className = 'lk-filtergroup-label';
   span.textContent = label;
   wrap.appendChild(span);
   values.forEach(function (v) {
    var chip = document.createElement('button');
    chip.type = 'button';
    chip.className = 'lk-filterchip';
    chip.textContent = labelFor ? labelFor(v) : v;
    chip.setAttribute('aria-pressed', activeSet[v] ? 'true' : 'false');
    chip.addEventListener('click', function () {
     if (activeSet[v]) { delete activeSet[v]; } else { activeSet[v] = true; }
     renderDictCards(state.dicts, state.output);
    });
    wrap.appendChild(chip);
   });
   return wrap;
  }

  if (langs.length > 1) {
   els.filterbar.appendChild(chipGroup('language', langs, state.langFilter, function (v) { return LANG_LABELS[v] || v; }));
  }
  if (eras.length > 1) {
   els.filterbar.appendChild(chipGroup('edition year', eras, state.eraFilter));
  }
 }

 function renderDictCards(dicts, output) {
  state.output = output;
  var items = dicts.map(function (dictRec) {
   var meta = metaFor(dictRec.dict);
   return {
    dictRec: dictRec, dict: dictRec.dict,
    title: meta.title, year: meta.year, yearOlder: meta.yearOlder, lang: meta.lang
   };
  });

  renderFilterBar(items);

  var langActive = Object.keys(state.langFilter).length > 0;
  var eraActive = Object.keys(state.eraFilter).length > 0;
  var filtered = items.filter(function (i) {
   return (!langActive || state.langFilter[i.lang]) && (!eraActive || state.eraFilter[i.year]);
  });

  // Preferred dicts (per doc/roadmap_lookup.md Wave 2 item 1) sort first,
  // in the user's stored order; everything else keeps dalglob.php's order.
  filtered.sort(function (a, b) {
   var pa = state.preferred.indexOf(a.dict), pb = state.preferred.indexOf(b.dict);
   if (pa === -1 && pb === -1) { return 0; }
   if (pa === -1) { return 1; }
   if (pb === -1) { return -1; }
   return pa - pb;
  });

  els.dictlist.innerHTML = '';
  if (!filtered.length) {
   var empty = document.createElement('p');
   empty.className = 'lk-dictlist-empty';
   empty.textContent = 'No dictionaries match the current filters.';
   els.dictlist.appendChild(empty);
   return;
  }

  filtered.forEach(function (item, i) {
   var card = document.createElement('div');
   card.className = 'lk-dictcard';
   card.setAttribute('role', 'button');
   card.tabIndex = 0;
   card.setAttribute('aria-pressed', i === 0 ? 'true' : 'false');

   var pin = document.createElement('button');
   pin.type = 'button';
   pin.className = 'lk-pin';
   var isPreferred = state.preferred.indexOf(item.dict) !== -1;
   pin.setAttribute('aria-pressed', isPreferred ? 'true' : 'false');
   pin.title = isPreferred ? 'Remove from preferred dictionaries' : 'Mark as preferred dictionary';
   pin.textContent = isPreferred ? '★' : '☆'; // filled/empty star
   pin.addEventListener('click', function (evt) {
    evt.stopPropagation();
    togglePreferred(item.dict);
   });
   card.appendChild(pin);

   var head = document.createElement('div');
   head.className = 'lk-dictcard-head';
   var code = document.createElement('span');
   code.className = 'lk-dictcard-code';
   code.textContent = item.dict.toUpperCase() + ' (' + item.dictRec.dockeys.length + ')';
   head.appendChild(code);
   if (item.lang !== 'other') {
    var langBadge = document.createElement('span');
    langBadge.className = 'lk-dictcard-lang';
    langBadge.textContent = LANG_LABELS[item.lang];
    head.appendChild(langBadge);
   }
   card.appendChild(head);

   var title = document.createElement('div');
   title.className = 'lk-dictcard-title';
   title.textContent = item.title;
   card.appendChild(title);

   if (item.year) {
    var year = document.createElement('div');
    year.className = 'lk-dictcard-year';
    year.textContent = item.yearOlder && item.yearOlder !== item.year
     ? item.year + ' (earlier ed. ' + item.yearOlder + ')'
     : item.year;
    card.appendChild(year);
   }

   function select() {
    Array.prototype.forEach.call(els.dictlist.children, function (c) {
     if (c.setAttribute) { c.setAttribute('aria-pressed', 'false'); }
    });
    card.setAttribute('aria-pressed', 'true');
    loadDictEntries(item.dictRec, output);
   }
   card.addEventListener('click', select);
   card.addEventListener('keydown', function (evt) {
    if (evt.key === 'Enter' || evt.key === ' ') { evt.preventDefault(); select(); }
   });

   els.dictlist.appendChild(card);
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

 // ---- permalinks (doc/roadmap_lookup.md Wave 2 item 2; doc/cleanurl.md
 // reserves the path-segment /{DICT}/{ref} family for per-dictionary
 // entries -- lookup/ searches across all dictionaries at once, so it
 // stays in the query-string family already used for the Wave 1 GET
 // prefill, extended with pushState instead of a full reload, mirroring
 // simple-search's listDisplay() (simple-search/v1.1/list-0.2s_rw.php). ---

 function updatePermalink(key, input, output) {
  var params = new URLSearchParams();
  params.set('key', key);
  if (input) { params.set('input', input); }
  if (output) { params.set('output', output); }
  var url = window.location.pathname + '?' + params.toString();
  if (url !== window.location.pathname + window.location.search) {
   window.history.pushState({ key: key, input: input, output: output }, '', url);
  }
  els.copylink.hidden = false;
 }

 // ---- top-level search --------------------------------------------------

 function runSearch(pushUrl) {
  var key = els.key.value.trim();
  clearResults();
  setStatus('', null);
  if (!key) { return; }
  var scheme = resolveScheme();
  var output = els.output.value;

  if (pushUrl !== false) { updatePermalink(key, scheme, output); }

  setStatus('Searching...', 'loading');
  fetchDalglob(key, scheme, output).then(function (data) {
   if (data.status !== 200 || !data.dicts.length) {
    setStatus('“' + key + '” was not found in any dictionary.', 'notfound');
    return;
   }
   setStatus('', null);
   state.dicts = data.dicts;
   renderDictCards(data.dicts, output);
   loadDictEntries(data.dicts[0], output);
  }).catch(function (err) {
   setStatus('Network error: ' + err.message, 'error');
  });
 }

 function restoreFromLocation() {
  var params = new URLSearchParams(window.location.search);
  var key = params.get('key') || '';
  var input = params.get('input');
  var output = params.get('output');
  els.key.value = key;
  if (ASCII_SCHEMES.indexOf(input) !== -1) { els.scheme.value = input; }
  if (output) { els.output.value = output; }
  updatePreview();
  if (key) { runSearch(false); } else { clearResults(); setStatus('', null); els.copylink.hidden = true; }
 }

 // ---- copy-link -----------------------------------------------------

 function copyLink() {
  var url = window.location.href;
  var done = function () { setStatus('Link copied to clipboard.', 'copied'); };
  var fail = function () {
   // Fallback for browsers without the async Clipboard API (still zero
   // external dependencies -- a hidden textarea + the legacy command).
   var ta = document.createElement('textarea');
   ta.value = url;
   ta.style.position = 'fixed';
   ta.style.opacity = '0';
   document.body.appendChild(ta);
   ta.select();
   try { document.execCommand('copy'); done(); }
   catch (e) { setStatus('Could not copy automatically -- copy from the address bar.', 'error'); }
   document.body.removeChild(ta);
  };
  if (navigator.clipboard && navigator.clipboard.writeText) {
   navigator.clipboard.writeText(url).then(done, fail);
  } else {
   fail();
  }
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
  els.copylink = qs('#lk-copylink');
  els.preview = qs('#lk-preview');
  els.status = qs('#lk-status');
  els.filterbar = qs('#lk-filterbar');
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
  els.copylink.addEventListener('click', copyLink);
  window.addEventListener('popstate', restoreFromLocation);

  var prefill = window.LOOKUP_PREFILL || {};
  if (ASCII_SCHEMES.indexOf(prefill.input) !== -1) { els.scheme.value = prefill.input; }
  if (prefill.output) { els.output.value = prefill.output; }
  updatePreview();
  if (prefill.key) { runSearch(false); }
 }

 if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
 } else {
  init();
 }
})();
