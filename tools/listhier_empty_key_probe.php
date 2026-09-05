#!/usr/bin/env php
<?php
/* tools/listhier_empty_key_probe.php
 H3853 (csl-apidev#153) regression probe.

 Asserts, against a staged MW sqlite (XAMPP layout: sibling <dict>/web/sqlite/):
   1. VALID keys render a left pane with NO empty-key onclick
      (`getWordAlt_keyboard("")` / `getWordlistUp/Down_keyboard("")`) - the
      dead-link failure mode Jim Funderburk hit in issue #153.
   2. The onclick payload always equals a clean slp1 key (raw key1), since
      apidev listview.js always sends input=slp1.
   3. Empty keys FAIL LOUD (RuntimeException -> nonzero exit), never a
      silently-empty pane; an unmatchable-but-nonempty key degrades
      gracefully - the revived prefix fallback (list1b $more fix, the #153
      root cause) anchors the pane at the nearest matching headword, never
      an empty-key onclick.

 Usage: php tools/listhier_empty_key_probe.php
 Exit 0 = all green; exit 1 = any assertion failed.
*/
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
$fails = 0;
$check = function($name,$cond) use (&$fails) {
 if ($cond) { echo "PASS: $name\n"; }
 else { echo "FAIL: $name\n"; $fails++; }
};
$run = function($key) {
 $cmd = PHP_BINARY . " " . escapeshellarg(__DIR__ . "/listhier_harness.php")
      . " " . escapeshellarg($key) . " 2>&1";
 $out = shell_exec($cmd);
 return array($out, ($out !== null && strpos($out, 'THROW:') === false));
};

// 1. valid keys: no empty-key onclick anywhere in the pane
$validkeys = array('gur','gurvI','gurukaRWa','gUrtavacas','guRana','a');
foreach($validkeys as $k) {
 list($out,$ok) = $run($k);
 $ok = $ok && (strpos($out,'getWordAlt_keyboard("")') === false)
        && (strpos($out,'getWordlistUp_keyboard("")') === false)
        && (strpos($out,'getWordlistDown_keyboard("")') === false);
 $check("valid key '$k' renders with no empty-key onclick", $ok);
}

// 2. onclick payloads are pure slp1 (ASCII letters/digits/period), never
//    display-alphabet text (devanagari punctuation such as em-dash)
list($out,$ok) = $run('gur');
preg_match_all("/getWordAlt_keyboard\(\"([^\"]*)\"\)/",$out,$m);
$bad = array_filter($m[1], function($s){ return !preg_match('/^[A-Za-z0-9.\'-]*$/',$s); });
$ok = $ok && count($m[1]) > 0 && count($bad) == 0;
$check("onclick payloads are clean slp1 (n=" . count($m[1]) . ")", $ok);

// 3. unmatchable key degrades gracefully (#153 root-cause fix): the revived
//    prefix fallback anchors the pane at the first headword with the same
//    first letter ('z' = slp1 ś) - never an empty-key onclick.
list($out,$ok) = $run('zzqqqx');
preg_match_all("/getWordAlt_keyboard\(\"([^\"]*)\"\)/",$out,$mz);
$anchored = false;
foreach($mz[1] as $p) { if (strpos($p,'z') === 0) { $anchored = true; break; } }
$ok = ($out !== null && strpos($out,'THROW:') === false)
      && (strpos($out,'getWordAlt_keyboard("")') === false)
      && (strpos($out,'getWordlistUp_keyboard("")') === false)
      && (strpos($out,'getWordlistDown_keyboard("")') === false)
      && $anchored;
$check("unmatchable key 'zzqqqx' renders a 'z'-anchored pane (no empty onclick)", $ok);

// 4. empty key still fails loud
list($out,$ok) = $run('');
$check("empty key fails loud (THROW, no pane)", (!$ok) && (strpos($out,'THROW:') !== false));

// 5. the 0.4.2 live repro: approximate spelling 'mahArASwrIya' (real
//    headword 'mahArAzwrIya') renders the nearest mahArA-family list
//    (center row = longest matching prefix) instead of dead links / 404.
list($out,$ok) = $run('mahArASwrIya');
preg_match_all("/getWordAlt_keyboard\(\"([^\"]*)\"\)/",$out,$mm);
$anchored = false;
foreach($mm[1] as $p) { if (strpos($p,'mahArA') === 0) { $anchored = true; break; } }
$ok = ($out !== null && strpos($out,'THROW:') === false)
      && (strpos($out,'getWordAlt_keyboard("")') === false)
      && $anchored;
$check("approximate key 'mahArASwrIya' renders a 'mahArA'-anchored pane", $ok);

echo $fails == 0 ? "PROBE GREEN\n" : "PROBE RED ($fails failure(s))\n";
exit($fails == 0 ? 0 : 1);
