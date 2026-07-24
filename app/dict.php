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
<title>Cologne Sanskrit Lexicon &mdash; Dictionary</title>
<link rel="stylesheet" type="text/css" href="../css/basic.css">
<link rel="stylesheet" type="text/css" href="app.css">
<script src="theme.js"></script>
<script>window.THEME && THEME.applyEarly();</script>
</head>
<body>
<noscript>This page needs JavaScript. Use the <a href="index.php">search page</a> instead.</noscript>

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
    <a class="ap-navlink" href="home.php">Home</a>
    <a class="ap-navlink" href="index.php">Search</a>
   </nav>
   <button type="button" id="ap-theme" class="ap-theme" aria-label="Toggle theme">&#9789;</button>
  </div>
 </div>
</header>

<section class="dt-head">
 <div class="dt-head-inner">
  <div class="dt-crumbs">
   <a href="home.php">All dictionaries</a> &rsaquo; <span id="dt-crumb">&hellip;</span>
  </div>
  <div class="dt-title-row">
   <span class="dt-code" id="dt-code">&hellip;</span>
   <h1 class="dt-title" id="dt-title">Dictionary</h1>
  </div>
  <div class="dt-meta" id="dt-meta"></div>
  <form class="dt-search" id="dt-search" action="index.php" method="get" role="search">
   <input type="text" name="key" id="dt-key" aria-label="Look up a word"
          placeholder="Look up a word in this dictionary&hellip;"
          autocapitalize="off" spellcheck="false">
   <input type="hidden" name="dict" id="dt-dict-field" value="">
   <button type="submit">Search</button>
  </form>
 </div>
</section>

<main class="dt-body" id="dt-body">
 <section class="dt-panel">
  <h3>Explore</h3>
  <div class="dt-actions" id="dt-actions"></div>
 </section>
 <section class="dt-panel">
  <h3>About this edition</h3>
  <p id="dt-about" class="ap-reader-hint"></p>
 </section>
</main>

<script src="../lookup/dictmeta.js" defer></script>
<script src="dict.js" defer></script>
</body>
</html>
