/* app.js -- client logic for app/index.php (doc/ux-redesign/ui-spec-app-v1.md
   slice 1, Proposal A "Research Workbench", MG rulings R1-R7 of 03-07-2026).
   Vanilla ES6, zero external resources, no build step, plain classic script
   (not type="module" -- avoids the .mjs MIME-type trap documented in
   Uprava/FINDINGS.md #3).

   Flow (fuzzy default): ONE getword_list_1.0.php call against MW generates
   candidate headwords; the results list renders immediately; then ONE
   sequential dalglob.php chain (250ms-spaced, cached, cancellable) fills in
   the per-row dictionary badges (R6/R7). Clicking a badge fetches that
   dictionary's entries via getword_batch.php in one request; on HTTP 404
   (server checkout not yet pulled) it falls back to sequential getword.php
   fetches ~250ms apart with exponential backoff on 429 -- same fallback as
   lookup/lookup.js, and for the same reason: never a parallel per-key burst.

   Rate discipline (spec hard requirement): at most one in-flight request
   chain per user action (searchToken cancels stale chains), all typing
   debounced >= 300ms, every response cached per session keyed by SLP1
   headword, no prefetching of entries the user hasn't clicked.

   ?fixtures=1 serves all endpoint traffic from fixtures/fixtures.json for
   offline development (the Cologne server is on SERVER_OUTAGES.md). */
(function () {
 'use strict';

 var DEVA_RE = /[ऀ-ॿ]/;
 var IAST_RE = /[āīūṛṝḷḹṃṅṇṣṭṱḍśĀĪŪṚṜḶḸṂṄṆṢṬṰḌŚ]/;
 // Candidate generation + autocomplete run against MW: the broadest
 // general-purpose dictionary (same choice as lookup.js SUGGEST_DICT).
 var ENGINE_DICT = 'mw';
 var ASCII_SCHEMES = ['hk', 'slp1', 'itrans'];
 var SPACING_MS = 250;
 var MAX_RETRIES = 4;
 var DEBOUNCE_MS = 300; // spec: debounced >= 300ms
 var MAX_ROWS = 20;
 var MOBILE_MQ = '(max-width: 899px)';
 var SERVER_DOWN_MSG = 'The Cologne server is not responding — try again later.';

 var FIXTURES = /[?&]fixtures=1/.test(window.location.search);
 var fixturesPromise = null;

 var els = {};
 var state = {
  mode: 'fuzzy',          // fuzzy | exact | prefix (suffix = slice 2)
  display: 'roman',       // roman (IAST, R5 default) | deva
  rows: [],               // [{slp1, raw, scheme, dicts|null, el, badgesEl}]
  openRow: null,
  openDict: null,
  openDockey: null,
  dictFilter: {},         // dict -> true (empty = all)
  cache: new Map(),       // per-session response cache (spec rate discipline)
  searchToken: 0
 };

 // ---- dictionary metadata (reused from lookup/dictmeta.js) --------------

 var DICTMETA_BY_CODE = {};
 var DICT_ORDER = {}; // canonical badge order (R7) = dictmeta.js array order
 (window.LOOKUP_DICTMETA || []).forEach(function (row, i) {
  var dict = row[0].toLowerCase();
  DICTMETA_BY_CODE[dict] = { title: row[1], year: row[2] };
  DICT_ORDER[dict] = i;
 });

 function metaFor(dict) {
  return DICTMETA_BY_CODE[dict] || { title: dict.toUpperCase(), year: '' };
 }

 // ---- small helpers ------------------------------------------------------

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

 // Entry HTML links 'css/basic.css' relative to the repo root; app/ sits one
 // level down -- same correction lookup.js and sample/dalglob1.php apply.
 function fixCssPath(html) { return html.replace('css/basic.css', '../css/basic.css'); }

 function isMobile() { return window.matchMedia(MOBILE_MQ).matches; }

 // Headwords render IAST by default, Devanagari via the R5 toggle, both
 // derived client-side from the SLP1 key by sanskrit-util (SHARED_CODE.md
 // families 1-2; only the slp1->iast/deva directions are used, which avoids
 // the known devanagari_to_slp1 ळ→x bug). Rows whose SLP1 spelling is
 // unknown (HK/ITRANS prefix suggestions) fall back to the raw suggestion.
 function renderHeadword(row) {
  var SU = window.SanskritUtil;
  if (!row.slp1 || !SU) { return row.raw; }
  return state.display === 'deva' ? SU.slp1_to_devanagari(row.slp1) : SU.from_slp1(row.slp1);
 }

 function setStatus(text, kind) {
  els.status.textContent = text || '';
  els.status.className = 'ap-status' + (kind ? ' ap-status-' + kind : '');
 }

 // ---- fixtures + cached fetch layer --------------------------------------

 function loadFixtures() {
  if (!fixturesPromise) {
   fixturesPromise = fetch('fixtures/fixtures.json')
    .then(function (res) { return res.json(); })
    .catch(function () { return {}; });
  }
  return fixturesPromise;
 }

 /* Single cached-JSON gate for every endpoint. cacheKey doubles as the
    fixtures.json lookup key, so live capture and offline replay stay in
    lockstep. A cache hit issues zero network requests (acceptance
    criterion 8). */
 function requestJson(cacheKey, url) {
  if (state.cache.has(cacheKey)) {
   return Promise.resolve(state.cache.get(cacheKey));
  }
  var p;
  if (FIXTURES) {
   p = loadFixtures().then(function (fx) {
    if (Object.prototype.hasOwnProperty.call(fx, cacheKey)) { return fx[cacheKey]; }
    return null; // fixture miss behaves like a 404
   });
  } else {
   p = fetch(url).then(function (res) {
    if (res.status === 404) { return null; }
    if (!res.ok) { throw new Error('HTTP ' + res.status); }
    return res.json();
   });
  }
  return p.then(function (data) {
   state.cache.set(cacheKey, data);
   return data;
  });
 }

 // ---- endpoint bindings (spec §Endpoint bindings) -------------------------

 function fetchSuggest(term, scheme) {
  // getsuggest.php has no 'default' filterin; plain ASCII is queried as HK
  // (the transcodings coincide for unmarked prefixes).
  var s = scheme === 'default' ? 'hk' : scheme;
  var key = 'suggest|' + ENGINE_DICT + '|' + s + '|' + term;
  var url = '../getsuggest.php?term=' + encodeURIComponent(term) +
   '&dict=' + ENGINE_DICT + '&input=' + encodeURIComponent(s);
  return requestJson(key, url).then(function (d) { return d || []; });
 }

 function fetchWordlist(term, engineInput) {
  var key = 'wordlist|' + ENGINE_DICT + '|' + engineInput + '|' + term;
  var url = '../simple-search/v1.1/getword_list_1.0.php' +
   '?key=' + encodeURIComponent(term) +
   '&input=' + encodeURIComponent(engineInput) +
   '&output=slp1&accent=no&dict=' + ENGINE_DICT;
  return requestJson(key, url).then(function (d) {
   return (d && d.result) ? d.result : [];
  });
 }

 // ONE round-trip resolves which dictionaries/homonym dockeys contain the
 // headword (R6) -- this is what makes "all dictionaries always" affordable.
 function fetchDalglob(row) {
  var keyArg, inputArg, cacheKey;
  if (row.slp1) {
   keyArg = row.slp1; inputArg = 'slp1';
   cacheKey = 'dalglob|' + row.slp1;
  } else {
   // HK/ITRANS prefix suggestion: let the server transcode.
   keyArg = row.raw; inputArg = row.scheme;
   cacheKey = 'dalglob|' + row.scheme + ':' + row.raw;
  }
  var url = '../dalglob.php?key=' + encodeURIComponent(keyArg) +
   '&input=' + encodeURIComponent(inputArg) +
   '&output=slp1&accent=no&dbglob=keydoc_glob1';
  return requestJson(cacheKey, url).then(function (d) {
   if (!d || d.status !== 200 || !d.dicts) { return []; }
   return d.dicts;
  });
 }

 function fetchBatch(dict, dockeys, output, accent) {
  var cacheKey = 'batch|' + dict + '|' + output + '|' + accent + '|' + dockeys.join(',');
  var url = '../getword_batch.php?dict=' + encodeURIComponent(dict) +
   '&keys=' + encodeURIComponent(dockeys.join(',')) +
   '&input=slp1' + // dockeys from dalglob.php are already SLP1-normalized
   '&output=' + encodeURIComponent(output) +
   '&accent=' + encodeURIComponent(accent);
  return requestJson(cacheKey, url); // null on 404 -> sequential fallback
 }

 function fetchText(url) {
  return fetch(url).then(function (res) {
   return res.text().then(function (text) {
    return { ok: res.ok, status: res.status, text: text };
   });
  });
 }

 // getword.php fallback with 429 exponential backoff -- lookup.js's fallback,
 // kept verbatim in behavior (spec §Endpoint bindings, States "Batch 404").
 function fetchOneWithBackoff(dict, key, output, accent, attempt) {
  attempt = attempt || 0;
  var cacheKey = 'getword|' + dict + '|' + output + '|' + accent + '|' + key;
  if (state.cache.has(cacheKey)) { return Promise.resolve(state.cache.get(cacheKey)); }
  if (FIXTURES) {
   return loadFixtures().then(function (fx) {
    var r = Object.prototype.hasOwnProperty.call(fx, cacheKey)
     ? fx[cacheKey]
     : { key: key, status: 404, html: '<p>Not in fixtures: ' + key + '</p>' };
    state.cache.set(cacheKey, r);
    return r;
   });
  }
  var url = '../getword.php?key=' + encodeURIComponent(key) +
   '&output=' + encodeURIComponent(output) +
   '&dict=' + encodeURIComponent(dict) +
   '&accent=' + encodeURIComponent(accent) +
   '&input=slp1&dispcss=no';
  return fetchText(url).then(function (res) {
   if (res.status === 429 && attempt < MAX_RETRIES) {
    var backoff = SPACING_MS * Math.pow(2, attempt + 1);
    setStatus('Server is throttling requests — retrying in ' + Math.round(backoff / 1000) + 's...', 'retry');
    return sleep(backoff).then(function () {
     return fetchOneWithBackoff(dict, key, output, accent, attempt + 1);
    });
   }
   var r = res.ok
    ? { key: key, status: 200, html: res.text }
    : { key: key, status: res.status, html: '<p>Network error (' + res.status + ') for ' + key + '</p>' };
   state.cache.set(cacheKey, r);
   return r;
  });
 }

 function fetchSequential(dict, dockeys, output, accent) {
  var results = [];
  var chain = Promise.resolve();
  dockeys.forEach(function (key, i) {
   chain = chain
    .then(function () { return i > 0 ? sleep(SPACING_MS) : null; })
    .then(function () { return fetchOneWithBackoff(dict, key, output, accent); })
    .then(function (r) { results.push(r); });
  });
  return chain.then(function () { return results; });
 }

 function fetchEntries(dict, dockeys, output, accent) {
  return fetchBatch(dict, dockeys, output, accent).then(function (batch) {
   if (batch) { return batch; }
   // Batch 404 -> silent sequential fallback (console only, per spec States)
   if (window.console && console.info) {
    console.info('getword_batch.php not deployed; falling back to sequential getword.php');
   }
   return fetchSequential(dict, dockeys, output, accent);
  });
 }

 // ---- input-scheme detection (R3: ASCII schemes never auto-detected) -----

 function detectScript(text) {
  if (DEVA_RE.test(text)) { return 'deva'; }
  if (IAST_RE.test(text)) { return 'iast'; }
  return null;
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

 function rowSlp1(word, scheme) {
  var SU = window.SanskritUtil;
  if (!SU) { return null; }
  if (scheme === 'slp1') { return word; }
  if (scheme === 'iast') { return SU.to_slp1(word); }
  if (scheme === 'deva') { return SU.deva_to_slp1(word); }
  return null; // hk/itrans: no client-side transcoder; server resolves
 }

 // ---- results list (R7: one row per headword, dict badges) ----------------

 function clearResults() {
  detachReader();
  els.results.innerHTML = '';
  els.reader.innerHTML = '<p class="ap-reader-hint">Select a headword to read its entry.</p>';
  state.rows = [];
  state.openRow = null;
  state.openDict = null;
  state.openDockey = null;
  renderDictFilter([]);
 }

 function showWorkbench(hasRows) {
  els.workbench.hidden = !hasRows;
  els.empty.hidden = hasRows;
 }

 function renderRows(rows, token) {
  els.results.innerHTML = '';
  rows.forEach(function (row) {
   var el = document.createElement('div');
   el.className = 'ap-row';
   el.setAttribute('role', 'button');
   el.tabIndex = 0;
   el.setAttribute('aria-pressed', 'false');

   var head = document.createElement('div');
   head.className = 'ap-row-head';
   var hw = document.createElement('span');
   hw.className = 'ap-headword';
   hw.lang = 'sa';
   hw.textContent = renderHeadword(row);
   head.appendChild(hw);
   el.appendChild(head);

   var badges = document.createElement('div');
   badges.className = 'ap-row-badges';
   badges.innerHTML = '<span class="ap-row-pending">…</span>';
   el.appendChild(badges);

   row.el = el;
   row.hwEl = hw;
   row.badgesEl = badges;

   function open() {
    ensureDicts(row, token).then(function () {
     if (token !== state.searchToken) { return; }
     if (row.dicts && row.dicts.length) {
      openEntry(row, row.dicts[0].dict, 0);
     }
    });
   }
   el.addEventListener('click', open);
   el.addEventListener('keydown', function (evt) {
    if (evt.key === 'Enter' || evt.key === ' ') { evt.preventDefault(); open(); }
   });

   els.results.appendChild(el);
  });
 }

 function orderDicts(dicts) {
  return dicts.slice().sort(function (a, b) {
   var oa = DICT_ORDER[a.dict], ob = DICT_ORDER[b.dict];
   if (oa === undefined && ob === undefined) { return 0; }
   if (oa === undefined) { return 1; }
   if (ob === undefined) { return -1; }
   return oa - ob;
  });
 }

 var SUP_DIGITS = ['⁰', '¹', '²', '³', '⁴', '⁵', '⁶', '⁷', '⁸', '⁹'];
 function supNum(n) {
  return String(n).split('').map(function (d) { return SUP_DIGITS[+d]; }).join('');
 }

 function renderRowBadges(row) {
  var badges = row.badgesEl;
  badges.innerHTML = '';
  if (!row.dicts || !row.dicts.length) {
   badges.innerHTML = '<span class="ap-row-pending">not found</span>';
   return;
  }
  var filterActive = Object.keys(state.dictFilter).length > 0;
  row.dicts.forEach(function (rec) {
   if (filterActive && !state.dictFilter[rec.dict]) { return; }
   var multi = rec.dockeys.length > 1;
   rec.dockeys.forEach(function (dockey, di) {
    var b = document.createElement('button');
    b.type = 'button';
    b.className = 'ap-dictbadge';
    b.title = metaFor(rec.dict).title;
    // Homonyms render as numbered sub-badges (MW¹ MW²), same convention
    // as lookup's homonym tabs (spec §Results list).
    b.innerHTML = rec.dict.toUpperCase() + (multi ? '<sup>' + supNum(di + 1) + '</sup>' : '');
    var pressed = state.openRow === row && state.openDict === rec.dict && state.openDockey === dockey;
    b.setAttribute('aria-pressed', pressed ? 'true' : 'false');
    b.addEventListener('click', function (evt) {
     evt.stopPropagation();
     openEntry(row, rec.dict, di);
    });
    badges.appendChild(b);
   });
  });
  if (!badges.children.length) {
   badges.innerHTML = '<span class="ap-row-pending">filtered out</span>';
  }
 }

 function ensureDicts(row, token) {
  if (row.dicts) { return Promise.resolve(row.dicts); }
  return fetchDalglob(row).then(function (dicts) {
   if (token !== state.searchToken) { return row.dicts; }
   row.dicts = orderDicts(dicts);
   renderRowBadges(row);
   updateDictFilterChips();
   return row.dicts;
  });
 }

 /* ONE sequential badge-resolution chain per search: rows resolve in order,
    250ms apart (only when a real network fetch happened -- cache and fixture
    hits don't sleep), and a newer search token abandons the chain. This is
    the "one in-flight request chain per user action" rule. */
 function resolveBadgeChain(rows, token) {
  var chain = Promise.resolve();
  rows.forEach(function (row) {
   chain = chain.then(function () {
    if (token !== state.searchToken) { return null; }
    if (row.dicts) { return null; }
    var cached = FIXTURES || state.cache.has(
     row.slp1 ? 'dalglob|' + row.slp1 : 'dalglob|' + row.scheme + ':' + row.raw);
    return ensureDicts(row, token).then(function () {
     return cached ? null : sleep(SPACING_MS);
    });
   });
  });
  return chain;
 }

 // ---- dictionary filter chips (advanced panel) ----------------------------

 function updateDictFilterChips() {
  var seen = {};
  state.rows.forEach(function (row) {
   (row.dicts || []).forEach(function (rec) { seen[rec.dict] = true; });
  });
  renderDictFilter(Object.keys(seen).sort(function (a, b) {
   return (DICT_ORDER[a] || 0) - (DICT_ORDER[b] || 0);
  }));
 }

 function renderDictFilter(dicts) {
  els.dictfilter.innerHTML = '';
  if (!dicts.length) {
   els.dictfilter.innerHTML = '<span class="ap-dictfilter-empty">run a search to filter its dictionaries</span>';
   return;
  }
  dicts.forEach(function (dict) {
   var chip = document.createElement('button');
   chip.type = 'button';
   chip.className = 'ap-chip';
   chip.textContent = dict.toUpperCase();
   chip.title = metaFor(dict).title;
   chip.setAttribute('aria-pressed', state.dictFilter[dict] ? 'true' : 'false');
   chip.addEventListener('click', function () {
    if (state.dictFilter[dict]) { delete state.dictFilter[dict]; }
    else { state.dictFilter[dict] = true; }
    chip.setAttribute('aria-pressed', state.dictFilter[dict] ? 'true' : 'false');
    state.rows.forEach(function (row) { if (row.dicts) { renderRowBadges(row); } });
   });
   els.dictfilter.appendChild(chip);
  });
 }

 // ---- entry reader ---------------------------------------------------------

 function detachReader() {
  // Mobile accordion: the reader lives under the tapped row; put it back
  // in its desktop column whenever we reset.
  if (els.reader.parentNode !== els.readerCol) {
   els.readerCol.appendChild(els.reader);
  }
 }

 function placeReader(row) {
  if (isMobile()) {
   row.el.insertAdjacentElement('afterend', els.reader);
  } else {
   detachReader();
  }
 }

 function openEntry(row, dict, dockeyIndex) {
  var rec = null;
  (row.dicts || []).forEach(function (r) { if (r.dict === dict) { rec = r; } });
  if (!rec) { return; }
  var dockey = rec.dockeys[dockeyIndex] || rec.dockeys[0];
  var output = state.display; // roman | deva (R5)
  var accent = els.accent.value;
  var token = state.searchToken;

  state.openRow = row;
  state.openDict = dict;
  state.openDockey = dockey;
  state.rows.forEach(function (r) {
   r.el.setAttribute('aria-pressed', r === row ? 'true' : 'false');
   if (r.dicts) { renderRowBadges(r); }
  });
  placeReader(row);
  els.reader.innerHTML = '<p class="ap-reader-hint">Loading ' + dict.toUpperCase() + '…</p>';
  updatePermalink();

  fetchEntries(dict, rec.dockeys, output, accent).then(function (items) {
   if (token !== state.searchToken || state.openDockey !== dockey || state.openDict !== dict) { return; }
   var item = null;
   items.forEach(function (it) { if (it.key === dockey) { item = it; } });
   if (!item) { item = items[0]; }
   renderReader(row, dict, dockey, item);
  }).catch(function () {
   if (token !== state.searchToken) { return; }
   setStatus(SERVER_DOWN_MSG, 'error');
  });
 }

 function renderReader(row, dict, dockey, item) {
  var meta = metaFor(dict);
  els.reader.innerHTML = '';

  var head = document.createElement('div');
  head.className = 'ap-reader-head';
  var hw = document.createElement('span');
  hw.className = 'ap-reader-headword';
  hw.lang = 'sa';
  hw.textContent = renderHeadword(row);
  head.appendChild(hw);
  var src = document.createElement('span');
  src.className = 'ap-reader-source';
  src.textContent = dict.toUpperCase() + (meta.year ? ' ' + meta.year : '');
  src.title = meta.title;
  head.appendChild(src);
  els.reader.appendChild(head);

  var tools = document.createElement('div');
  tools.className = 'ap-reader-tools';
  // Scan click-through stays a plain link action (must-preserve; never a
  // default split pane).
  var scan = document.createElement('a');
  scan.className = 'ap-link-button';
  scan.href = '../servepdf.php?dict=' + encodeURIComponent(dict) + '&key=' + encodeURIComponent(dockey);
  scan.target = '_blank';
  scan.rel = 'noopener';
  scan.textContent = 'Scan';
  tools.appendChild(scan);
  var copy = document.createElement('button');
  copy.type = 'button';
  copy.className = 'ap-link-button';
  copy.textContent = 'Copy permalink';
  copy.addEventListener('click', copyLink);
  tools.appendChild(copy);
  els.reader.appendChild(tools);

  var entry = document.createElement('div');
  entry.className = 'ap-entry';
  entry.innerHTML = fixCssPath((item && item.html) || '<p>No entry text.</p>');
  els.reader.appendChild(entry);
 }

 // ---- top-level search ------------------------------------------------------

 function runSearch(pushUrl) {
  var term = els.key.value.trim();
  var token = ++state.searchToken;
  clearResults();
  setStatus('', null);
  if (!term) { showWorkbench(false); els.copylink.hidden = true; return; }

  var scheme = resolveScheme();
  if (pushUrl !== false) { updatePermalink(); }
  els.copylink.hidden = false;
  setStatus('Searching…', 'loading');

  var rowsPromise;
  if (state.mode === 'prefix') {
   // Prefix mode: getsuggest.php prefix matching rendered as a results list.
   rowsPromise = fetchSuggest(term, scheme).then(function (matches) {
    var s = scheme === 'default' ? 'hk' : scheme;
    return matches
     .filter(function (m) { return !/\?\?$/.test(m); }) // engine's no-match marker
     .slice(0, MAX_ROWS)
     .map(function (m) { return { slp1: rowSlp1(m, s), raw: m, scheme: s, dicts: null }; });
   });
  } else {
   // Fuzzy: engine input 'default' (it auto-detects Devanagari itself).
   // Exact: the resolved scheme passes through restrict_to_user_word
   // server-side; with 'default' resolved we filter on user_key_flag
   // client-side instead -- same semantics for plain-ASCII exact input.
   var engineInput = state.mode === 'exact' ? scheme : 'default';
   rowsPromise = fetchWordlist(term, engineInput).then(function (result) {
    var rows = result.filter(function (r) { return r.status === 200; });
    if (state.mode === 'exact') {
     rows = rows.filter(function (r) { return r.user_key_flag; });
    }
    return rows.slice(0, MAX_ROWS).map(function (r) {
     return { slp1: r.dicthw, raw: r.dicthw, scheme: 'slp1', dicts: null };
    });
   });
  }

  rowsPromise.then(function (rows) {
   if (token !== state.searchToken) { return; }
   state.rows = rows;
   if (!rows.length) {
    showWorkbench(false);
    var offer = (state.mode !== 'fuzzy')
     ? ' Try the Fuzzy tab for close spellings.' // spec §States: offer fuzzy
     : '';
    setStatus('“' + term + '” was not found.' + offer, 'notfound');
    return;
   }
   setStatus('', null);
   showWorkbench(true);
   renderRows(rows, token);
   resolveBadgeChain(rows, token).then(function () {
    if (token !== state.searchToken) { return; }
    // GET-prefill deep link: reopen the dict named in the URL, else the
    // first row's first badge on desktop (keeps mobile taps deliberate).
    if (pendingDict) {
     var want = pendingDict; pendingDict = null;
     for (var i = 0; i < rows.length; i++) {
      var match = (rows[i].dicts || []).some(function (r) { return r.dict === want; });
      if (match) { openEntry(rows[i], want, 0); return; }
     }
    }
   });
  }).catch(function () {
   if (token !== state.searchToken) { return; }
   showWorkbench(false);
   setStatus(SERVER_DOWN_MSG, 'error');
  });
 }

 // ---- autocomplete -----------------------------------------------------------

 var updateSuggestions = debounce(function (term) {
  if (state.mode === 'prefix') { return; } // prefix results ARE the suggestions
  if (term.length < 2 || detectScript(term) === 'deva') {
   els.suggestions.innerHTML = '';
   return;
  }
  fetchSuggest(term, resolveScheme()).then(function (matches) {
   els.suggestions.innerHTML = '';
   matches.forEach(function (m) {
    if (/\?\?$/.test(m)) { return; }
    var opt = document.createElement('option');
    opt.value = m;
    els.suggestions.appendChild(opt);
   });
  }).catch(function () { /* suggestions are best-effort */ });
 }, DEBOUNCE_MS);

 // ---- URL contract / permalinks (spec §URL contract) --------------------------
 // app/index.php?key=X&input=Y&output=Z&dict=DICT (+mode; fixtures preserved)

 var pendingDict = null;

 function updatePermalink() {
  var params = new URLSearchParams();
  params.set('key', els.key.value.trim());
  params.set('input', resolveScheme());
  params.set('output', state.display);
  if (state.openDict) { params.set('dict', state.openDict); }
  if (state.mode !== 'fuzzy') { params.set('mode', state.mode); }
  if (FIXTURES) { params.set('fixtures', '1'); }
  var url = window.location.pathname + '?' + params.toString();
  if (url !== window.location.pathname + window.location.search) {
   window.history.pushState(null, '', url);
  }
 }

 function copyLink() {
  var url = window.location.href;
  var done = function () { setStatus('Link copied to clipboard.', 'copied'); };
  var fail = function () {
   var ta = document.createElement('textarea');
   ta.value = url;
   ta.style.position = 'fixed';
   ta.style.opacity = '0';
   document.body.appendChild(ta);
   ta.select();
   try { document.execCommand('copy'); done(); }
   catch (e) { setStatus('Could not copy automatically — copy from the address bar.', 'error'); }
   document.body.removeChild(ta);
  };
  if (navigator.clipboard && navigator.clipboard.writeText) {
   navigator.clipboard.writeText(url).then(done, fail);
  } else {
   fail();
  }
 }

 function applyParams(params) {
  var key = params.key || '';
  var input = params.input || '';
  var output = params.output || '';
  var mode = params.mode || '';
  els.key.value = key;
  if (ASCII_SCHEMES.indexOf(input) !== -1 || input === 'iast') { els.scheme.value = input; }
  if (output === 'deva' || output === 'roman') { setDisplay(output, false); }
  setMode(['fuzzy', 'exact', 'prefix'].indexOf(mode) !== -1 ? mode : 'fuzzy');
  pendingDict = params.dict ? params.dict.toLowerCase() : null;
  resolveScheme();
  if (key) { runSearch(false); } else { clearResults(); showWorkbench(false); }
 }

 function restoreFromLocation() {
  var p = new URLSearchParams(window.location.search);
  applyParams({
   key: p.get('key'), input: p.get('input'), output: p.get('output'),
   dict: p.get('dict'), mode: p.get('mode')
  });
 }

 // ---- mode + display toggles ---------------------------------------------------

 function setMode(mode) {
  state.mode = mode;
  Array.prototype.forEach.call(document.querySelectorAll('.ap-mode-tab[data-mode]'), function (tab) {
   tab.setAttribute('aria-pressed', tab.getAttribute('data-mode') === mode ? 'true' : 'false');
  });
 }

 function setDisplay(display, rerender) {
  state.display = display;
  els.displayRoman.setAttribute('aria-pressed', display === 'roman' ? 'true' : 'false');
  els.displayDeva.setAttribute('aria-pressed', display === 'deva' ? 'true' : 'false');
  if (rerender === false) { return; }
  // R5: headwords re-render client-side; the open entry re-fetches with
  // the new output value (cached per output, so toggling back is free).
  state.rows.forEach(function (row) {
   if (row.hwEl) { row.hwEl.textContent = renderHeadword(row); }
  });
  if (state.openRow && state.openDict) {
   var rec = null;
   state.openRow.dicts.forEach(function (r) { if (r.dict === state.openDict) { rec = r; } });
   var di = rec ? rec.dockeys.indexOf(state.openDockey) : 0;
   openEntry(state.openRow, state.openDict, di === -1 ? 0 : di);
  } else {
   updatePermalink();
  }
 }

 // ---- init -----------------------------------------------------------------------

 function init() {
  els.form = qs('#ap-form');
  els.key = qs('#ap-key');
  els.suggestions = qs('#ap-suggestions');
  els.schemeField = qs('#ap-scheme-field');
  els.scheme = qs('#ap-scheme');
  els.schemeAuto = qs('#ap-scheme-auto');
  els.displayRoman = qs('#ap-display-roman');
  els.displayDeva = qs('#ap-display-deva');
  els.copylink = qs('#ap-copylink');
  els.status = qs('#ap-status');
  els.empty = qs('#ap-empty');
  els.workbench = qs('#ap-workbench');
  els.results = qs('#ap-results');
  els.readerCol = qs('#ap-reader-col');
  els.reader = qs('#ap-reader');
  els.advancedToggle = qs('#ap-advanced-toggle');
  els.advanced = qs('#ap-advanced');
  els.accent = qs('#ap-accent');
  els.dictfilter = qs('#ap-dictfilter');

  els.form.addEventListener('submit', function (evt) {
   evt.preventDefault();
   runSearch();
  });
  els.key.addEventListener('input', function () {
   resolveScheme();
   updateSuggestions(els.key.value.trim());
  });
  els.scheme.addEventListener('change', resolveScheme);
  els.displayRoman.addEventListener('click', function () { setDisplay('roman'); });
  els.displayDeva.addEventListener('click', function () { setDisplay('deva'); });
  els.copylink.addEventListener('click', copyLink);
  els.advancedToggle.addEventListener('click', function () {
   var open = els.advanced.hidden;
   els.advanced.hidden = !open;
   els.advancedToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
  els.accent.addEventListener('change', function () {
   if (state.openRow && state.openDict) { setDisplay(state.display); } // refetch open entry
  });
  Array.prototype.forEach.call(document.querySelectorAll('.ap-mode-tab[data-mode]'), function (tab) {
   if (tab.disabled) { return; } // suffix: slice 2
   tab.addEventListener('click', function () {
    setMode(tab.getAttribute('data-mode'));
    if (els.key.value.trim()) { runSearch(); }
   });
  });
  Array.prototype.forEach.call(document.querySelectorAll('.ap-example'), function (btn) {
   btn.addEventListener('click', function () {
    els.key.value = btn.textContent;
    setMode('fuzzy');
    runSearch();
   });
  });
  window.addEventListener('popstate', restoreFromLocation);
  // Keep a reference to the MediaQueryList (an unreferenced MQL's listener
  // can be GC'd) and back it up with a debounced resize handler -- DevTools
  // viewport emulation doesn't always fire MQL 'change'.
  var onViewportChange = function () {
   if (state.openRow) { placeReader(state.openRow); } else { detachReader(); }
  };
  els.mql = window.matchMedia(MOBILE_MQ);
  if (els.mql.addEventListener) { els.mql.addEventListener('change', onViewportChange); }
  else if (els.mql.addListener) { els.mql.addListener(onViewportChange); }
  window.addEventListener('resize', debounce(onViewportChange, 150));

  renderDictFilter([]);
  applyParams(window.APP_PREFILL || {});
 }

 if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
 } else {
  init();
 }
})();
