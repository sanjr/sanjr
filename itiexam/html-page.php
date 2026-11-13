<?php
/* .html pages uploaded straight into the website folder (e.g. /fitter-mcq.html or /tests/day-1.html).
   .htaccess sends them here at the SAME address, so links already shared keep working. The page is sent exactly
   as uploaded, with only the Google AdSense code added (Auto ads + the optional top / bottom ad units).
   To change such a page later, upload the new file with the same name: the link stays the same. */
require __DIR__ . '/includes/functions.php';

$root = realpath(__DIR__);
$rel = ltrim(str_replace('\\', '/', (string)($_GET['_hp'] ?? '')), '/');
$file = $rel === '' ? false : realpath(__DIR__ . '/' . $rel);
$inside = $file ? str_replace('\\', '/', substr($file, strlen($root) + 1)) : '';
if (!$file || !str_starts_with($file, $root . DIRECTORY_SEPARATOR) || !is_file($file) || !preg_match('/\.html?$/i', $file)
    || preg_match('~^(data|includes|admin|uploads|assets)/|(^|/)\.~i', $inside)) not_found();

$html = (string)file_get_contents($file);
$cs = preg_match('/<meta[^>]+charset\s*=\s*["\']?([\w-]+)/i', $html, $m) ? $m[1] : 'utf-8';
header('Content-Type: text/html; charset=' . $cs);
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', (int)filemtime($file)) . ' GMT');
header('Cache-Control: no-cache');
header_remove('X-Frame-Options');                 // the uploaded file was never sent with it; keep the page working as before

// Sent untouched: site-ownership files (Google / Yandex / Bing check their exact text), very big files,
// pages that already carry their own AdSense code, and when "ads on uploaded HTML pages" is switched off
$untouched = preg_match('/^(google[0-9a-f]+|yandex_[0-9a-f]+|BingSiteAuth)\.html?$/i', basename($file))
    || strlen($html) > 8 * 1024 * 1024 || setting('ads_html_pages') === '0' || stripos($html, 'adsbygoogle.js') !== false;
if (!$untouched && ($head = adsense_head()) !== '') {
    $style = '<style>.itx-ad{clear:both;max-width:100%;margin:12px auto;text-align:center;min-height:0}.itx-ad .ad-box{margin:0}'
        . '.itx-ad .ad-label{display:block;font:10px/1.4 system-ui,sans-serif;letter-spacing:.08em;text-transform:uppercase;color:#8a8f9c;margin-bottom:4px}'
        . '.itx-ad:has(ins[data-ad-status="unfilled"]){display:none}</style>';
    $top = ad('html_top');
    $bottom = ad('html_bottom');
    $top = $top !== '' ? '<div class="itx-ad">' . $top . '</div>' : '';
    $bottom = $bottom !== '' ? '<div class="itx-ad">' . $bottom . '</div>' : '';
    $head = $head . (($top . $bottom) !== '' ? $style : '');

    // AdSense code just before </head> (or at the very start when the page has no <head>)
    $html = preg_match('~</head\s*>~i', $html, $m, PREG_OFFSET_CAPTURE)
        ? substr_replace($html, $head, $m[0][1], 0)
        : (preg_match('~<body\b[^>]*>~i', $html, $m, PREG_OFFSET_CAPTURE) ? substr_replace($html, $head, $m[0][1], 0) : $head . $html);
    // optional ad units: right after <body …> and just before the last </body>
    if ($top !== '') $html = preg_match('~<body\b[^>]*>~i', $html, $m, PREG_OFFSET_CAPTURE)
        ? substr_replace($html, $top, $m[0][1] + strlen($m[0][0]), 0) : $top . $html;
    if ($bottom !== '') {
        $pos = strripos($html, '</body');
        $html = $pos !== false ? substr_replace($html, $bottom, $pos, 0) : $html . $bottom;
    }
}
echo $html;
