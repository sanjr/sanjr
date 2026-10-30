<?php
/* ITIExam.com – site configuration
   Default database is SQLite (no setup needed). To use MySQL, open Admin → Database: it copies the data
   and switches the website over. */

define('SITE_NAME', 'ITIExam.com');

// Your website address, no trailing slash, e.g. 'https://itiexam.com'.
// Leave empty to use the address the site is opened on (only the addresses listed in $siteHosts are trusted).
$siteUrl = '';
// Domains this website answers on. The first one is the main address: an unknown or faked address falls back to it.
$siteHosts = ['itiexam.com', 'www.itiexam.com', 'blog.copamcq.com'];
if ($siteUrl === '') {
    $https = ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    $host = strtolower(preg_replace('/[^A-Za-z0-9.:-]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost'));
    $local = preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/', $host);
    if (!$local && !in_array(preg_replace('/:\d+$/', '', $host), $siteHosts, true)) $host = $siteHosts[0];
    $siteUrl = ($https || !$local ? 'https' : 'http') . '://' . $host;
}
define('SITE_URL', rtrim($siteUrl, '/'));

// Database: SQLite works with no setup. MySQL is switched on in Admin → Database, which keeps its details in
// data/db-config.php – a file that is never part of a website update, so uploading new files cannot undo it.
$dbc = is_file(__DIR__ . '/../data/db-config.php') ? (array)(include __DIR__ . '/../data/db-config.php') : [];
define('DB_DRIVER', ($dbc['driver'] ?? '') === 'mysql' ? 'mysql' : 'sqlite');
define('DB_SQLITE_PATH', __DIR__ . '/../data/itiexam.sqlite');
define('DB_HOST', (string)($dbc['host'] ?? 'localhost'));
define('DB_NAME', (string)($dbc['name'] ?? ''));
define('DB_USER', (string)($dbc['user'] ?? ''));
define('DB_PASS', (string)($dbc['pass'] ?? ''));
unset($dbc);

define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('UPLOAD_URL', '/uploads');
define('MAX_UPLOAD_BYTES', 3 * 1024 * 1024);      // images: 3 MB
define('MAX_PDF_BYTES', 20 * 1024 * 1024);        // PDFs (notes, papers): 20 MB
define('CBT_DIR', __DIR__ . '/../data/tests');     // uploaded HTML CBT tests (served only through /cbt/…)
define('MAX_CBT_BYTES', 30 * 1024 * 1024);        // HTML file or ZIP: 30 MB

date_default_timezone_set('Asia/Kolkata');
