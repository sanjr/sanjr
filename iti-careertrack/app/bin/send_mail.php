<?php
declare(strict_types=1);

/**
 * Sends the e-mail queue of "Email & Circulars" — run by cron every 5 minutes, e.g. on Hostinger
 * (hPanel → Advanced → Cron Jobs):   php /home/USER/public_html/app/app/bin/send_mail.php
 * Keeps to config.php mail.per_hour. Options: --limit=200
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../src/bootstrap.php';

use App\Core\Database as DB;
use App\Services\Mail;

$opt = getopt('', ['limit:']);
DB::pdo();
if (DB::readOnly()) {
    fwrite(STDOUT, "Main database is not reachable — nothing sent.\n");
    exit(1);
}
\App\Core\Migrator::run();
if (!Mail::isLive()) {
    fwrite(STDOUT, "E-mail is not set up in config.php ('mail') — DEMO mode, nothing to send.\n");
    exit(0);
}
$r = Mail::pump(max(1, (int) ($opt['limit'] ?? 200)));
fwrite(STDOUT, '[' . date('Y-m-d H:i:s') . "] sent {$r['sent']}, failed {$r['failed']}, left {$r['left']}" . ($r['reason'] !== '' ? " ({$r['reason']})" : '') . "\n");
