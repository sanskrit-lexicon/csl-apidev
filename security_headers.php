<?php
// security_headers.php
//
// Shared defensive HTTP headers for live apidev entry points.
// require_once this file, before ANY other output, at the top of every
// HTML/JSON-emitting entry point. Parity with csl-websanlexicon
// v02/makotemplates/web/security_headers.php (H1523).
//
// Stage 1+2: baseline hardening headers + Content-Security-Policy-Report-Only
// (measurement only -- nothing here blocks a request or breaks a page).
// Enforcing CSP is deferred until Report-Only telemetry is reviewed.
if (!headers_sent()) {
 header('X-Content-Type-Options: nosniff');
 header('Referrer-Policy: strict-origin-when-cross-origin');
 header('X-Frame-Options: SAMEORIGIN');

 // Report-Only CSP -- deliberately does NOT include 'unsafe-inline' on
 // script-src/style-src so telemetry captures every inline script/style
 // without blocking them. Allowlisted external origins used by Cologne
 // PDF/scan embeds and the jQuery CDN if present.
 $csp = "default-src 'self'; "
      . "script-src 'self' https://code.jquery.com; "
      . "style-src 'self' https://code.jquery.com; "
      . "img-src 'self' https://www.sanskrit-lexicon.uni-koeln.de data:; "
      . "font-src 'self'; "
      . "object-src 'self' https://www.sanskrit-lexicon.uni-koeln.de; "
      . "connect-src 'self'; "
      . "base-uri 'self'; "
      . "form-action 'self'; "
      . "frame-ancestors 'self'";
 header("Content-Security-Policy-Report-Only: $csp");
}
