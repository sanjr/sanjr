<?php
/* Local testing only: php -S localhost:8000 router.php  (mimics .htaccess) */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('~^/(data|includes)(/|$)~', $path)) { http_response_code(403); exit('Forbidden'); }
if ($path !== '/' && is_file(__DIR__ . $path)) {
    if (str_starts_with($path, '/uploads/') && preg_match('/\.php/i', $path)) { http_response_code(403); exit; }
    return false;
}
if ($path === '/sitemap.xml') { require __DIR__ . '/sitemap.php'; return true; }
if ($path === '/ads.txt') { require __DIR__ . '/ads.php'; return true; }
if ($path === '/robots.txt') { require __DIR__ . '/robots.php'; return true; }
if (preg_match('~^/admin(/.*)?$~', $path, $m)) {
    $f = __DIR__ . '/admin' . (($m[1] ?? '') === '' || $m[1] === '/' ? '/index.php' : $m[1]);
    if (is_file($f)) { chdir(dirname($f)); require $f; return true; }
}
if (preg_match('~^/trades/?$~', $path)) { require __DIR__ . '/trades.php'; return true; }
if (preg_match('~^/trades/([a-z0-9-]+)/?$~', $path, $m)) { $_GET['slug'] = $m[1]; require __DIR__ . '/trade.php'; return true; }
if (preg_match('~^/jobs/?$~', $path)) { require __DIR__ . '/jobs.php'; return true; }
if (preg_match('~^/jobs/([a-z0-9-]+)/?$~', $path, $m)) { $_GET['seg'] = $m[1]; require __DIR__ . '/jobs.php'; return true; }
if (preg_match('~^/search/?$~', $path)) { require __DIR__ . '/search.php'; return true; }
if ($path === '/') { require __DIR__ . '/index.php'; return true; }
$_GET['slug'] = trim($path, '/');
require __DIR__ . '/page.php';
return true;
