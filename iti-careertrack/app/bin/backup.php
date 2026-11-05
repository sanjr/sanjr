<?php
declare(strict_types=1);

/**
 * Nightly database backup — run by cron once a day, BEFORE the auto import, e.g. 01:30:
 *     php /home/USER/domains/DOMAIN/public_html/app/app/bin/backup.php
 *
 *  1. Writes a full backup file: storage/backups/careertrack-YYYYmmdd-HHiiss.sql.gz
 *  2. Loads it into the backup database (config.php → 'db_backup'), the copy the website uses
 *     automatically (read-only) if the main database stops answering.
 *  3. Keeps the newest 14 files (config.php → 'backup' => ['keep' => 14]).
 *
 * Restore a file into the MAIN database (replaces everything in it!):
 *     php app/bin/backup.php --restore=storage/backups/careertrack-20261105-013000.sql.gz --yes
 * Options: --no-mirror (skip step 2)
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../src/bootstrap.php';

use App\Core\Database as DB;
use App\Services\Backup;

$opt = getopt('', ['restore:', 'yes', 'no-mirror']);
$say = static fn (string $m) => fwrite(STDOUT, '[' . date('Y-m-d H:i:s') . "] $m\n");

if (isset($opt['restore'])) {
    $file = (string) $opt['restore'];
    if (!is_file($file)) {
        $file = Backup::dir() . '/' . basename($file);
    }
    if (!is_file($file)) {
        $say('File not found.');
        exit(1);
    }
    if (!isset($opt['yes'])) {
        $say('This REPLACES all data in the main database "' . config('db.name') . '" with ' . basename($file) . '. Run again with --yes to do it.');
        exit(1);
    }
    $pdo = Backup::connect((array) config('db'));
    $say('Safety copy of the current state first …');
    $safe = Backup::dump($pdo);
    $say('  ' . basename($safe['file']));
    [$n, $skipped] = Backup::restore($file, Backup::connect((array) config('db')));
    @unlink(dirname(__DIR__) . '/storage/cache/schema_version');   // let the website re-check its version
    $say("Restored $n statements" . ($skipped ? ", $skipped triggers skipped (rules run in PHP)" : '') . '.');
    exit(0);
}

DB::pdo();
if (DB::readOnly()) {
    $say('Main database is not reachable — no backup made (the website runs on the backup copy).');
    exit(1);
}
try {
    $r = Backup::dump();
    $say(sprintf('Backup: %s (%.1f MB, %d tables, %s rows, %.1f s)', basename($r['file']), $r['bytes'] / 1048576, $r['tables'], number_format($r['rows']), $r['seconds']));
    DB::insert('integration_logs', ['integration' => 'BACKUP', 'operation' => 'dump', 'status' => 'OK', 'duration_ms' => (int) ($r['seconds'] * 1000)]);
    if (!isset($opt['no-mirror'])) {
        $m = Backup::mirror($r['file']);
        $say($m['ok'] ? "Backup database updated ({$m['statements']} statements, {$m['seconds']} s)" : 'Backup database not updated: ' . $m['reason']);
        DB::insert('integration_logs', ['integration' => 'BACKUP', 'operation' => 'mirror', 'status' => $m['ok'] ? 'OK' : 'SKIPPED', 'error_code' => $m['ok'] ? null : mb_substr($m['reason'], 0, 60)]);
    }
    $say('Old files removed: ' . Backup::rotate());
} catch (Throwable $e) {
    $say('BACKUP FAILED: ' . $e->getMessage());
    try {
        DB::insert('integration_logs', ['integration' => 'BACKUP', 'operation' => 'dump', 'status' => 'FAILED', 'error_code' => mb_substr($e->getMessage(), 0, 60)]);
    } catch (Throwable) {
    }
    exit(1);
}
