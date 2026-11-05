<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Database as DB;
use App\Core\HttpException;
use App\Services\Backup;

/** State Admin: backup files, the backup database (replica) and its health. */
final class BackupController extends Controller
{
    private function guard(): void
    {
        Auth::requireRole('STATE_ADMIN');
    }

    public function index(): void
    {
        $this->guard();
        $b = (array) config('db_backup', []);
        $replica = ['set' => !empty($b['name']), 'ok' => false, 'copy' => null, 'error' => null, 'name' => $b['name'] ?? '', 'host' => $b['host'] ?? ''];
        if ($replica['set']) {
            try {
                $pdo = Backup::connect($b);
                $replica['ok'] = true;
                $replica['copy'] = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'backup_copy_of'")->fetchColumn() ?: null;
            } catch (\Throwable $e) {
                $replica['error'] = mb_substr($e->getMessage(), 0, 160);
            }
        }
        $setting = static fn (string $k) => DB::value('SELECT setting_value FROM settings WHERE setting_key = ?', [$k]);
        $this->render('admin/backups', ['title' => 'Database Backups', 'files' => Backup::files(), 'replica' => $replica,
            'lastBackup' => $setting('last_backup_at'), 'lastMirror' => $setting('last_mirror_at'), 'readOnly' => DB::readOnly(),
            'mainName' => (string) config('db.name'), 'mainHost' => (string) config('db.host'), 'dir' => Backup::dir(),
            'cron' => 'php ' . dirname(__DIR__, 2) . '/bin/backup.php',
            'log' => DB::all("SELECT * FROM integration_logs WHERE integration = 'BACKUP' ORDER BY id DESC LIMIT 10")]);
    }

    public function run(): void
    {
        $this->guard();
        $r = Backup::dump();
        $m = Backup::mirror($r['file']);
        Backup::rotate();
        DB::insert('integration_logs', ['integration' => 'BACKUP', 'operation' => 'dump', 'status' => 'OK', 'duration_ms' => (int) ($r['seconds'] * 1000)]);
        Audit::log('BACKUP', 'settings', null, null, null, null, ['file' => basename($r['file']), 'mirror' => $m['ok']]);
        flash('success', sprintf('Backup made: %s (%.1f MB, %s rows). %s', basename($r['file']), $r['bytes'] / 1048576, number_format($r['rows']),
            $m['ok'] ? 'Backup database updated.' : 'Backup database not updated: ' . $m['reason'] . '.'));
        redirect('/backups');
    }

    public function download(): void
    {
        $this->guard();
        $name = basename((string) input('f', ''));
        $path = Backup::dir() . '/' . $name;
        if (!preg_match('/^careertrack-\d{8}-\d{6}\.sql\.gz$/', $name) || !is_file($path)) {
            throw new HttpException(404);
        }
        Audit::log('DOWNLOAD_BACKUP', 'settings', null, null, null, null, ['file' => $name]);
        header('Content-Type: application/gzip');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
}
