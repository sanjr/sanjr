<?php
/* Served at /robots.txt */
require __DIR__ . '/includes/config.php';
header('Content-Type: text/plain; charset=utf-8');
echo "User-agent: *\nAllow: /\nDisallow: /search/\nDisallow: /admin/\n\nSitemap: " . SITE_URL . "/sitemap.xml\n";
