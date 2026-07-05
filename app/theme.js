/* theme.js -- shared light/dark theme toggle for the app/ surface
   (index.php, home.php, dict.php). Vanilla, no build step.

   Two entry points:
   - THEME.applyEarly()  runs inline in <head> BEFORE first paint to set
     the persisted theme and avoid a flash of the wrong theme (FOUC).
   - THEME.wire(button)  attaches the toggle behaviour to a #ap-theme button
     after DOM ready, and keeps its label in sync.

   Persistence: localStorage 'cologne-theme' = 'light' | 'dark'. Absent =
   follow the OS via the CSS prefers-color-scheme media query (no attribute
   set). The toggle cycles light <-> dark explicitly. */
(function () {
 'use strict';
 var KEY = 'cologne-theme';

 function stored() {
  try { return localStorage.getItem(KEY); } catch (e) { return null; }
 }
 function store(v) {
  try { if (v) { localStorage.setItem(KEY, v); } else { localStorage.removeItem(KEY); } }
  catch (e) { /* private mode: in-memory only for this page */ }
 }
 function systemDark() {
  return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
 }
 // The effective theme right now (explicit override, else OS).
 function current() {
  var s = stored();
  if (s === 'light' || s === 'dark') { return s; }
  return systemDark() ? 'dark' : 'light';
 }
 function apply(theme) {
  document.documentElement.setAttribute('data-theme', theme);
 }
 function applyEarly() {
  var s = stored();
  if (s === 'light' || s === 'dark') { apply(s); }
  // else: leave no attribute so the media query drives it.
 }
 function label(btn, theme) {
  var toDark = theme !== 'dark';
  btn.textContent = toDark ? '☽' : '☀'; // ☽ moon / ☀ sun
  btn.setAttribute('aria-label', toDark ? 'Switch to dark theme' : 'Switch to light theme');
  btn.title = btn.getAttribute('aria-label');
 }
 function wire(btn) {
  if (!btn) { return; }
  label(btn, current());
  btn.addEventListener('click', function () {
   var next = current() === 'dark' ? 'light' : 'dark';
   store(next);
   apply(next);
   label(btn, next);
  });
 }

 window.THEME = { applyEarly: applyEarly, wire: wire, current: current };
}());
