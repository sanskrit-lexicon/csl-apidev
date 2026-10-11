<?php
// og_preview.php — Open Graph / Twitter-card meta for Cologne dictionary entry
// pages (H6478, 11-10-2026; procedure: Uprava docs/PLAYBOOK_OG_PREVIEW_CARDS_2026-10-11.md).
//
// Call og_preview_meta($parms, $matches) once inside the <head> template after
// $parms (dict, key) and $matches are populated. Returns the meta lines joined
// with newlines, or '' when the request is not an entry page.
//
// og:image points at the kosha preview-card set on samskrtam.ru (dictionaries
// mw, pwg, ap90). For uncovered dictionaries, or a headword kosha has no card
// for yet, the image URL 404s and chat apps (Telegram, Slack, X) fall back to
// the text-only preview — so the image tags are emitted only for covered
// dictionaries, and coverage grows with the kosha og/ card set without any
// change here.
//
// Telegram renders the large full-width card only when
// twitter:card=summary_large_image rides next to og:image — the hint GitHub
// pages carry; do not remove it.

function og_preview_image_url($dictlower, $slp1_key) {
 // kosha og/ cards are keyed by the filesystem-safe SLP1 token:
 // [a-z0-9] verbatim, every other UTF-8 byte as _<hexbyte> (kosha
 // app/word_page.py card_token; query keys are case-folded, kosha#433).
 $covered = array('mw', 'pwg', 'ap90');
 if (!in_array($dictlower, $covered)) {
  return null;
 }
 $key = strtolower($slp1_key);
 $token = preg_replace_callback('/[^a-z0-9]/', function ($m) {
  return sprintf('_%02x', ord($m[0]));
 }, $key);
 return 'https://samskrtam.ru/og/' . $token . '.png';
}

function og_preview_description($matches) {
 // First record's <body> text, tags stripped, cut at ~200 chars on a word
 // boundary; falls back to '' when there are no records (not-found pages).
 if (!is_array($matches) || count($matches) === 0) {
  return '';
 }
 $rec = $matches[0][2];
 if (preg_match('/<body>(.*)<\/body>/s', $rec, $m)) {
  // drop the record-ID tail (<span class="lnum">[ID=...]</span>) — noise in a
  // chat preview
  $html = preg_replace('/<span class="lnum">.*?<\/span>/s', '', $m[1]);
  $text = trim(preg_replace('/\s+/', ' ', strip_tags($html)));
  if (strlen($text) > 200) {
   $cut = strpos($text, ' ', 180);
   $text = rtrim(substr($text, 0, $cut === false ? 200 : $cut)) . '...';
  }
  return $text;
 }
 return '';
}

function og_preview_meta($parms, $matches) {
 $dictlower = strtolower($parms->dict);
 $key = $parms->key;
 if (!$dictlower || !$key) {
  return '';
 }
 if (function_exists('transcoder_processElements')) {
  $hw = transcoder_processElements($key, 'slp1', 'iast', 'SA');
 } else {
  $hw = $key;
 }
 $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
 $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
 $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
 $pageurl = $scheme . '://' . $host . $uri;
 $site = 'Cologne Digital Sanskrit Dictionaries';
 $title = $hw . ' — ' . $site;
 $desc = og_preview_description($matches);
 if ($desc === '') {
  $desc = $title;
 }
 $lines = array(
  '<meta property="og:type" content="website">',
  '<meta property="og:site_name" content="' . $site . '">',
  '<meta property="og:title" content="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '">',
  '<meta property="og:description" content="' . htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') . '">',
  '<meta property="og:url" content="' . htmlspecialchars($pageurl, ENT_QUOTES, 'UTF-8') . '">',
 );
 $img = og_preview_image_url($dictlower, $key);
 if ($img !== null) {
  $lines[] = '<meta property="og:image" content="' . $img . '">';
  $lines[] = '<meta property="og:image:width" content="1200">';
  $lines[] = '<meta property="og:image:height" content="630">';
  $lines[] = '<meta name="twitter:card" content="summary_large_image">';
  $lines[] = '<meta name="twitter:image" content="' . $img . '">';
 }
 return implode("\n", $lines) . "\n";
}
?>
