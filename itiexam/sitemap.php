<?php
/* Dynamic sitemap: served at /sitemap.xml – includes every published trade and page automatically. */
require __DIR__ . '/includes/functions.php';
header('Content-Type: application/xml; charset=utf-8');
$urls = [['/', date('Y-m-d'), 'daily', '1.0'], ['/trades/', date('Y-m-d'), 'weekly', '0.9']];
foreach (trades() as $t) $urls[] = [trade_url($t), substr($t['updated_at'], 0, 10), 'weekly', '0.9'];
$urls[] = ['/jobs/', date('Y-m-d'), 'daily', '0.9'];
$urls[] = ['/app/', date('Y-m-d'), 'monthly', '0.8'];
foreach (array_keys(JOB_TYPES) as $k) $urls[] = ["/jobs/$k/", date('Y-m-d'), 'daily', '0.8'];
foreach (db()->query("SELECT slug, updated_at FROM jobs j WHERE " . job_active_sql()) as $j) $urls[] = ['/jobs/' . $j['slug'] . '/', substr($j['updated_at'], 0, 10), 'weekly', '0.8'];
foreach (db()->query("SELECT folder, updated_at FROM cbt_tests WHERE status = 'published'") as $c) $urls[] = ['/cbt/' . $c['folder'] . '/', substr($c['updated_at'], 0, 10), 'monthly', '0.7'];
foreach (db()->query("SELECT slug, updated_at FROM pages WHERE status = 'published'") as $p) {
    $urls[] = ['/' . $p['slug'] . '/', substr($p['updated_at'], 0, 10), 'weekly', '0.7'];
}
echo '<?xml version="1.0" encoding="UTF-8"?>', "\n", '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', "\n";
foreach ($urls as [$loc, $mod, $freq, $pr]) {
    echo '  <url><loc>', e(SITE_URL . $loc), '</loc><lastmod>', e($mod), '</lastmod><changefreq>', $freq, '</changefreq><priority>', $pr, "</priority></url>\n";
}
echo "</urlset>\n";
