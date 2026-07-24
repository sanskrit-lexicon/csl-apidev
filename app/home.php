<?php
require_once(__DIR__ . '/../security_headers.php');
// Exclude WARNING messages also, to solve Peter Scharf Mac version.
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cologne Sanskrit Lexicon</title>
<meta name="description" content="Search 45 Sanskrit dictionaries at once — the Cologne Digital Sanskrit Dictionaries.">
<link rel="stylesheet" type="text/css" href="../css/basic.css">
<link rel="stylesheet" type="text/css" href="app.css">
<script src="theme.js"></script>
<script>window.THEME && THEME.applyEarly();</script>
</head>
<body>

<header class="ap-topbar">
 <div class="ap-topbar-inner">
  <a class="ap-brand" href="home.php">
   <span class="ap-seal" aria-hidden="true">UzK</span>
   <span class="ap-brand-text">
    <span class="ap-brand-title">Cologne Sanskrit Lexicon</span>
    <span class="ap-brand-subtitle">Cologne Digital Sanskrit Dictionaries</span>
   </span>
  </a>
  <div class="ap-topbar-controls">
   <nav class="ap-topbar-nav" aria-label="Primary">
    <a class="ap-navlink" href="home.php" aria-current="page">Home</a>
    <a class="ap-navlink" href="index.php">Search</a>
   </nav>
   <button type="button" id="ap-theme" class="ap-theme" aria-label="Toggle theme">&#9789;</button>
  </div>
 </div>
</header>

<section class="hm-hero">
 <div class="hm-hero-inner">
  <h1>One search, every Sanskrit dictionary</h1>
  <p>Look up a word across all 45 Cologne dictionaries at once. Type in
     Devanagari, IAST, HK, SLP1 or just plain letters &mdash; the script is
     detected for you.</p>
  <form class="hm-hero-search" action="index.php" method="get" role="search">
   <input type="text" name="key" aria-label="Search all dictionaries"
          placeholder="e.g. agni, m&#257;na, &#2309;&#2327;&#2381;&#2344;&#2367;"
          autocapitalize="off" spellcheck="false" autofocus>
   <button type="submit">Search</button>
  </form>
  <div class="hm-hero-examples" aria-label="Example searches">
   <a href="index.php?key=agni">agni</a>
   <a href="index.php?key=m&#257;na&amp;input=iast">m&#257;na</a>
   <a href="index.php?key=manas">manas</a>
   <a href="index.php?key=&#2343;&#2352;&#2381;&#2350;">&#2343;&#2352;&#2381;&#2350;</a>
  </div>
 </div>
</section>

<main class="hm-section">
 <div class="hm-section-head">
  <h2>Browse dictionaries</h2>
  <span class="hm-count" id="hm-count"></span>
 </div>
 <div class="hm-filter">
  <input type="search" id="hm-search" class="hm-filter-search"
         placeholder="Filter by name or code&hellip;" aria-label="Filter dictionaries">
  <div id="hm-langs" role="group" aria-label="Filter by language"></div>
 </div>
 <div id="hm-grid" class="hm-grid" aria-live="polite"></div>
 <p id="hm-empty" class="hm-empty" hidden>No dictionaries match that filter.</p>
</main>

<footer class="hm-footer">
 <div class="hm-footer-inner">
  <div>
   <strong>Cologne Digital Sanskrit Dictionaries</strong><br>
   Institute of Indology and Tamil Studies, University of Cologne
  </div>
  <div>
   <a href="//www.sanskrit-lexicon.uni-koeln.de/">Classic site</a> &middot;
   <a href="//www.sanskrit-lexicon.uni-koeln.de/citation.html">How to cite</a> &middot;
   <a href="//github.com/sanskrit-lexicon">Source on GitHub</a>
  </div>
 </div>
</footer>

<script src="../lookup/dictmeta.js" defer></script>
<script src="home.js" defer></script>
</body>
</html>
