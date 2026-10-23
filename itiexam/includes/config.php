<?php
/* ITIExam.com – site configuration
   Default database is SQLite (no setup needed). To use MySQL, set DB_DRIVER to 'mysql'
   and fill in the MySQL details from your hosting panel. */

define('SITE_NAME', 'ITIExam.com');

// Your website address, no trailing slash, e.g. 'https://blog.copamcq.com'.
// Leave empty to use the address the site is opened on.
$siteUrl = '';
if ($siteUrl === '') {
    $https = ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    $host = preg_replace('/[^A-Za-z0-9.:-]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
    $siteUrl = ($https || !preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/', $host) ? 'https' : 'http') . '://' . $host;
}
define('SITE_URL', rtrim($siteUrl, '/'));

define('DB_DRIVER', 'sqlite');                    // 'sqlite' or 'mysql'
define('DB_SQLITE_PATH', __DIR__ . '/../data/itiexam.sqlite');
define('DB_HOST', 'localhost');
define('DB_NAME', 'itiexam');
define('DB_USER', 'root');
define('DB_PASS', '');

define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('UPLOAD_URL', '/uploads');
define('MAX_UPLOAD_BYTES', 3 * 1024 * 1024);      // images: 3 MB
define('MAX_PDF_BYTES', 20 * 1024 * 1024);        // PDFs (notes, papers): 20 MB
define('CBT_DIR', __DIR__ . '/../data/tests');     // uploaded HTML CBT tests (served only through /cbt/…)
define('MAX_CBT_BYTES', 30 * 1024 * 1024);        // HTML file or ZIP: 30 MB
define('MAX_VIDEO_BYTES', 1024 * 1024 * 1024);    // Video Learning: one video up to 1 GB (sent in small pieces)

date_default_timezone_set('Asia/Kolkata');
