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
    // Dashboard / report numbers are cached per data scope (seconds); list totals for count_ttl. 0 = off.
    'cache' => ['ttl' => 600, 'count_ttl' => 60],
];
