<?php
/* =========================================================
   COPA MCQ – MySQL settings (Hostinger hPanel → Databases → MySQL Databases)
   ---------------------------------------------------------
   1. Type your MySQL user's password in 'db_pass' below.
   2. Upload this file to  public_html/api/config.php
   3. Never share this file. (It cannot be opened from the browser – see api/.htaccess.)
   When you upload a newer version of the website later, do NOT overwrite this file.
   ========================================================= */
return [
    'db_host' => 'localhost',
    'db_name' => 'u427511805_newcopa071126',
    'db_user' => 'u427511805_Parth',
    'db_pass' => '',          // ← your MySQL password

    // Secret words needed ONCE to create the first admin account on admin.html.
    // Leave empty to use the MySQL password above as the setup key.
    'setup_key' => '',

    // Visitors' approximate location (city / state / country) from their IP address.
    // 'ipwho.is' (default) or 'ipapi.co' – free services, check their terms; 'none' = off.
    'geo_provider' => 'ipwho.is',
    'timezone'     => 'Asia/Kolkata',
    // Only if the site is behind Cloudflare: 'ip_header' => 'HTTP_CF_CONNECTING_IP',
];
