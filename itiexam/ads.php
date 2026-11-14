<?php
/* Served at /ads.txt – AdSense needs this file to pay you fully. Built from Admin → Ads. */
require __DIR__ . '/includes/functions.php';
header('Content-Type: text/plain; charset=utf-8');
echo ads_txt();
