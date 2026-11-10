<?php
/**
 * ITI CareerTrack configuration.
 * Copy this file to config.php and change the values. config.php is git-ignored.
 * Every value can also be overridden with an environment variable (shown in brackets).
 */
return [
    'app' => [
        'name'                 => 'ITI CareerTrack',
        'env'                  => getenv('CT_ENV') ?: 'demo',          // demo | production  [CT_ENV]
        'debug'                => false,   // true = show PHP errors on screen (only on your own computer, never on the live site)
        // Long random secret used for Aadhaar HMAC and tokens. MUST be changed. [CT_SECRET]
        'secret'               => getenv('CT_SECRET') ?: 'dev-only-secret-change-me-0f9a7c1e5b2d4a6f8e3c',
        'timezone'             => 'Asia/Kolkata',
        'session_idle_minutes' => 30,
        'force_https_cookies'  => (bool) (getenv('CT_HTTPS') ?: false),  // set true behind HTTPS [CT_HTTPS]
    ],
    'db' => [
        'host' => getenv('CT_DB_HOST') ?: '127.0.0.1',
        'port' => (int) (getenv('CT_DB_PORT') ?: 3306),
        'name' => getenv('CT_DB_NAME') ?: 'careertrack',
        // Use the least-privilege account (no DELETE right) created by 04_app_user.sql
        'user' => getenv('CT_DB_USER') ?: 'careertrack_app',
        'pass' => getenv('CT_DB_PASS') ?: 'change_me_app_pass',
    ],
    'storage' => [
        // Private folder OUTSIDE the public web root
        'documents' => __DIR__ . '/../storage/documents',
        'max_upload_bytes' => 5 * 1024 * 1024,
    ],
    // Email & Circulars: without 'host' the portal Inbox works, but no e-mails are sent (DEMO).
    // Hostinger: create a mailbox in hPanel → Emails; cron every 5 min: php …/app/bin/send_mail.php
    'mail' => [
        'host'      => getenv('CT_MAIL_HOST') ?: '',          // e.g. smtp.hostinger.com
        'port'      => (int) (getenv('CT_MAIL_PORT') ?: 465),
        'secure'    => 'ssl',                                  // ssl (465) | tls (587)
        'username'  => getenv('CT_MAIL_USER') ?: '',
        'password'  => getenv('CT_MAIL_PASS') ?: '',
        'from'      => getenv('CT_MAIL_FROM') ?: '',           // same as username on Hostinger
        'from_name' => 'DVET Maharashtra — ITI CareerTrack',
        'per_hour'  => 300,                                    // sending limit of the hosting plan
        'max_file_mb' => 10, 'max_total_mb' => 25, 'attach_in_email_mb' => 10,
    ],
    // Dashboard / report numbers are cached per data scope (seconds); list totals for count_ttl. 0 = off.
    'cache' => ['ttl' => 600, 'count_ttl' => 60],
];
