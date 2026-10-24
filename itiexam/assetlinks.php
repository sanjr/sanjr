<?php
/* /.well-known/assetlinks.json – proves the ITI Exam Android app belongs to this website, so website links open in the app. */
require __DIR__ . '/includes/functions.php';
$keys = [APP_SHA256];
foreach (preg_split('/[\s,]+/', strtoupper(setting('app_sha256'))) as $k) {
    if (preg_match('/^([0-9A-F]{2}:){31}[0-9A-F]{2}$/', $k)) $keys[] = $k;          // e.g. the Play App Signing key
}
header('Content-Type: application/json');
header('Cache-Control: public, max-age=3600');
echo json_encode([[
    'relation' => ['delegate_permission/common.handle_all_urls'],
    'target' => ['namespace' => 'android_app', 'package_name' => APP_PACKAGE, 'sha256_cert_fingerprints' => array_values(array_unique($keys))],
]], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
