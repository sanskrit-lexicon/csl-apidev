// Regression check for app.js's foldIast()/rowSlp1() IAST case-folding.
//
// Why this file exists: `to_slp1` maps lowercase IAST and passes everything else
// through verbatim, into an output alphabet where case is PHONEMIC. Feeding it a
// capitalised headword therefore yields a different, plausible-looking word with
// no error path -- 'Rāma' -> 'RAma', which renders as 'ṇāma' and looks up the
// wrong dalglob key. IAST_RE deliberately auto-detects capitalised input, so this
// path is reachable by design. The same defect silently corrupted 60% of the keys
// in SanskritLexicography's ACC×NCC works crosswalk (integrity issue #779).
//
// The root cause there was not the bug itself but that NOTHING PINNED what a
// capital does -- to_slp1 is documented as "IAST -> SLP1" with no word on case and
// is tested only on lowercase. This file is that pin for the one place csl-apidev
// transcodes IAST client-side.
//
// Zero dependencies, no runner: node app/rowSlp1.check.js  (exit 0 = pass)

'use strict';

const fs = require('fs');
const path = require('path');

const APP_DIR = __dirname;
require(path.join(APP_DIR, 'vendor', 'sanskrit-util.global.js'));
const SU = globalThis.SanskritUtil;
global.window = { SanskritUtil: SU };

// app.js is a browser IIFE that touches `document` on load, so the two pure
// helpers are sliced out rather than importing the module. If either is renamed
// or restructured this throws loudly instead of silently passing.
const src = fs.readFileSync(path.join(APP_DIR, 'app.js'), 'utf8');
const start = src.indexOf(' function foldIast(word) {');
const rowStart = src.indexOf(' function rowSlp1(word, scheme) {');
if (start < 0 || rowStart < 0) {
  throw new Error('rowSlp1.check.js: could not find foldIast/rowSlp1 in app.js — '
    + 'they were renamed or moved; update this check to match.');
}
const end = src.indexOf('\n }', rowStart) + 3;
// Compiled with `new Function` rather than eval(): under 'use strict' an eval'd
// function declaration stays inside eval's own scope and never reaches us.
const rowSlp1 = new Function('window',
  src.slice(start, end) + '\n return rowSlp1;')(global.window);

const checks = [
  ['capitalised IAST keys the same as lowercase',
    rowSlp1('Rāma', 'iast') === rowSlp1('rāma', 'iast')],
  ['Rāma -> rAma (not RAma, which is ṇāma)',
    rowSlp1('Rāma', 'iast') === 'rAma'],
  ['Bhāgavata -> BAgavata (B is bh; not BhAgavata)',
    rowSlp1('Bhāgavata', 'iast') === 'BAgavata'],
  ['Ekāvalī -> ekAvalI (E is ai)',
    rowSlp1('Ekāvalī', 'iast') === 'ekAvalI'],
  ['Śiva -> Siva (non-ASCII capitals transliterate too)',
    rowSlp1('Śiva', 'iast') === 'Siva'],
  ['decomposed NFD input keys like precomposed NFC',
    rowSlp1('Rāma'.normalize('NFD'), 'iast') === rowSlp1('Rāma', 'iast')],
  ['SLP1 input is NEVER folded — its case is phonemic',
    rowSlp1('RAma', 'slp1') === 'RAma'],
  ['Devanagari path unaffected',
    rowSlp1('राम', 'deva') === 'rAma'],
  ['empty input is safe',
    rowSlp1('', 'iast') === ''],
  ['unsupported scheme still defers to the server',
    rowSlp1('rAma', 'hk') === null],
];

let failed = 0;
for (const [name, ok] of checks) {
  if (!ok) { failed++; }
  console.log((ok ? 'PASS ' : 'FAIL ') + name);
}
console.log('\n' + (checks.length - failed) + '/' + checks.length + ' passed');
process.exit(failed ? 1 : 0);
