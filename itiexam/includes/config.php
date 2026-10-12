<?php
/* ITIExam.com – site configuration
   Default database is SQLite (no setup needed). To use MySQL, set DB_DRIVER to 'mysql'
   and fill in the MySQL details from your hosting panel. */

define('SITE_NAME', 'ITIExam.com');
define('SITE_URL', 'https://www.itiexam.com');   // no trailing slash

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

date_default_timezone_set('Asia/Kolkata');
