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
if (preg_match('~^/syllabus/?$~', $path)) { require __DIR__ . '/syllabus.php'; return true; }
if (preg_match('~^/search/?$~', $path)) { require __DIR__ . '/search.php'; return true; }
if (preg_match('~^/cbt/([a-z0-9-]+)/play/(.+)$~', $path, $m)) { $_GET['f'] = $m[1]; $_GET['p'] = $m[2]; require __DIR__ . '/cbt.php'; return true; }
if (preg_match('~^/cbt/([a-z0-9-]+)/?$~', $path, $m)) { $_GET['f'] = $m[1]; require __DIR__ . '/cbt.php'; return true; }
if (preg_match('~^/login/?$~', $path)) { require __DIR__ . '/student-login.php'; return true; }
if (preg_match('~^/auth/google/?$~', $path)) { require __DIR__ . '/auth-google.php'; return true; }
if (preg_match('~^/logout/?$~', $path)) { require __DIR__ . '/student-logout.php'; return true; }
if (preg_match('~^/student/?$~', $path)) { require __DIR__ . '/student-dashboard.php'; return true; }
if (preg_match('~^/app/download/?$~', $path)) { $_GET['dl'] = 1; require __DIR__ . '/app-page.php'; return true; }
if (preg_match('~^/app/?$~', $path)) { require __DIR__ . '/app-page.php'; return true; }
if (preg_match('~^/app-auth/claim/?$~', $path)) { $_GET['claim'] = 1; require __DIR__ . '/app-auth.php'; return true; }
if (preg_match('~^/app-auth/?$~', $path)) { require __DIR__ . '/app-auth.php'; return true; }
if ($path === '/.well-known/assetlinks.json') { require __DIR__ . '/assetlinks.php'; return true; }
if (preg_match('~^/api/attempt/?$~', $path)) { require __DIR__ . '/api-attempt.php'; return true; }
if ($path === '/') { require __DIR__ . '/index.php'; return true; }
$_GET['slug'] = trim($path, '/');
require __DIR__ . '/page.php';
return true;
