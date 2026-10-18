<?php
// og_preview.php — Open Graph meta for Cologne dictionary entry
// pages (H6478, 11-10-2026; procedure: Uprava docs/PLAYBOOK_OG_PREVIEW_CARDS_2026-10-11.md).
//
// Call og_preview_meta($parms, $matches) once inside the <head> template after
// $parms (dict, key) and $matches are populated. Returns the meta lines joined
// with newlines, or '' when the request is not an entry page.
//
// Cards are Cologne-OWNED (MG ruling 11-10-2026 «keep them separate»): the
// og/ directory in this repo holds 1200x630 preview PNGs rendered by
// make_entry_cards.py from Cologne's own entry pages — nothing points at
// kosha or samskrtam.ru. og:image + twitter:card are emitted only when the
// card file exists server-side (file_exists), so coverage grows with the
// committed og/ set and a missing card degrades gracefully to the text-only
// preview. twitter:card=summary_large_image is what makes Telegram render the
// large full-width card — keep it paired with og:image.

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

function og_preview_card_token($slp1_key) {
 // Filesystem-safe SLP1 token: case-folded, [a-z0-9] verbatim, every other
 // UTF-8 byte as _<hexbyte> (the same scheme kosha's card_token uses — the
 // algorithm is shared, the card SETS are separate properties).
 $key = strtolower($slp1_key);
 return preg_replace_callback('/[^a-z0-9]/', function ($m) {
  return sprintf('_%02x', ord($m[0]));
 }, $key);
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
 // Cologne-owned preview card (existence-gated): og/<dict>_<token>.png ships
 // with the checkout, so file_exists is the coverage test — no cross-property
 // lookups.
 $cardfile = __DIR__ . '/og/' . $dictlower . '_' . og_preview_card_token($key) . '.png';
 if (file_exists($cardfile)) {
  $base = $scheme . '://' . $host . preg_replace('/getword\.php$/', '', $uri);
  $img = $base . 'og/' . $dictlower . '_' . og_preview_card_token($key) . '.png';
  $lines[] = '<meta property="og:image" content="' . $img . '">';
  $lines[] = '<meta property="og:image:width" content="1200">';
  $lines[] = '<meta property="og:image:height" content="630">';
  $lines[] = '<meta name="twitter:card" content="summary_large_image">';
  $lines[] = '<meta name="twitter:image" content="' . $img . '">';
 }
 return implode("\n", $lines) . "\n";
}
?>
