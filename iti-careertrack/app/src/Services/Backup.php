<?php
declare(strict_types=1);

namespace App\Services;

use PDO;

/**
 * Database safety.
 *  1. Backup file: a consistent snapshot of the whole database (tables, data, views, triggers,
 *     procedures) as storage/backups/careertrack-YYYYmmdd-HHiiss.sql.gz — kept for N days.
 *  2. Backup database ("replica"): the same snapshot is loaded into a SECOND database every night
 *     (config.php → 'db_backup'), ideally on another server. If the main database stops answering,
 *     the website switches to that copy automatically, in READ-ONLY mode (see Database::pdo()).
 *  3. Restore: bin/backup.php --restore=FILE (command line only, with --yes).
 *
 * Plain PHP (no mysqldump needed), so it also runs on shared hosting.
 */
final class Backup
{
    private const END = "\n-- @@END\n";   // statement separator (procedures / triggers contain ";")

    public static function dir(): string
    {
        $d = (string) config('backup.dir', dirname(__DIR__, 2) . '/storage/backups');
        if (!is_dir($d)) {
            @mkdir($d, 0770, true);
        }
        if (!is_file("$d/.htaccess")) {
            @file_put_contents("$d/.htaccess", "Require all denied\nDeny from all\n");
        }
        return $d;
    }

    /** New connection from a config block ('db' or 'db_backup'). */
    public static function connect(array $c): PDO
    {
        $pdo = new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'] ?? 'localhost', (int) ($c['port'] ?? 3306), $c['name'] ?? ''),
            (string) ($c['user'] ?? ''), (string) ($c['pass'] ?? ''), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 5]);
        $pdo->exec('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec("SET time_zone = '+05:30', SESSION sql_mode = 'NO_AUTO_VALUE_ON_ZERO,NO_ENGINE_SUBSTITUTION'");
        return $pdo;
    }

    // ------------------------------------------------------------------ backup file

    /** @return array{file:string, bytes:int, tables:int, rows:int, seconds:float} */
    public static function dump(?PDO $src = null): array
    {
        @set_time_limit(0);
        $t0 = microtime(true);
        $src ??= self::connect((array) config('db'));
        $src->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        $one = static fn (string $sql) => $src->query($sql)->fetchAll(PDO::FETCH_NUM)[0] ?? [];   // read all rows: the connection is unbuffered
        $db = (string) ($one('SELECT DATABASE()')[0] ?? '');
        $file = self::dir() . '/careertrack-' . date('Ymd-His') . '.sql.gz';
        $tmp = $file . '.part';
        $gz = gzopen($tmp, 'wb6') ?: throw new \RuntimeException('Cannot write the backup file in ' . self::dir());
        $w = static fn (string $s) => gzwrite($gz, $s . self::END);
        gzwrite($gz, "-- ITI CareerTrack database backup of `$db`, " . date('Y-m-d H:i:s') . "\n-- Restore: php app/bin/backup.php --restore=FILE --yes\n");
        $w('SET NAMES utf8mb4');
        $w('SET FOREIGN_KEY_CHECKS = 0');
        $w('SET UNIQUE_CHECKS = 0');
        // one consistent view of all InnoDB tables while the website keeps running
        $src->exec('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $src->exec('START TRANSACTION WITH CONSISTENT SNAPSHOT');
        $tables = $src->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME")->fetchAll(PDO::FETCH_COLUMN);
        $rowsTotal = 0;
        foreach ($tables as $t) {
            $create = $one('SHOW CREATE TABLE `' . str_replace('`', '', $t) . '`')[1];
            $w("DROP TABLE IF EXISTS `$t`");
            $w($create);
            $cols = $src->query("SELECT COLUMN_NAME, DATA_TYPE, EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = " . $src->quote($t) . ' ORDER BY ORDINAL_POSITION')->fetchAll();
            $keep = array_values(array_filter($cols, static fn ($c) => !preg_match('/GENERATED|PERSISTENT/i', (string) $c['EXTRA'])));   // computed columns are rebuilt
            if (!$keep) {
                continue;
            }
            $bin = array_map(static fn ($c) => (bool) preg_match('/binary|blob/i', (string) $c['DATA_TYPE']), $keep);
            $names = implode(', ', array_map(static fn ($c) => '`' . $c['COLUMN_NAME'] . '`', $keep));
            $st = $src->query("SELECT $names FROM `$t`");
            $batch = [];
            while ($r = $st->fetch(PDO::FETCH_NUM)) {
                $vals = [];
                foreach ($r as $i => $v) {
                    $vals[] = $v === null ? 'NULL' : ($bin[$i] ? '0x' . bin2hex((string) $v) : (is_int($v) || is_float($v) ? (string) $v : self::q((string) $v)));
                }
                $batch[] = '(' . implode(',', $vals) . ')';
                $rowsTotal++;
                if (count($batch) >= 400) {
                    $w("INSERT INTO `$t` ($names) VALUES " . implode(",\n", $batch));
                    $batch = [];
                }
            }
            $st->closeCursor();
            if ($batch) {
                $w("INSERT INTO `$t` ($names) VALUES " . implode(",\n", $batch));
            }
        }
        $strip = static fn (string $s) => preg_replace('/\sDEFINER\s*=\s*(`[^`]*`|\S+)@(`[^`]*`|\S+)/i', '', $s) ?? $s;
        foreach ($src->query("SELECT TABLE_NAME FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE()")->fetchAll(PDO::FETCH_COLUMN) as $v) {
            $sql = $one('SHOW CREATE VIEW `' . $v . '`')[1];
            $w("DROP VIEW IF EXISTS `$v`");
            $w(str_replace('SQL SECURITY DEFINER', 'SQL SECURITY INVOKER', $strip($sql)));
        }
        foreach ($src->query("SELECT ROUTINE_NAME, ROUTINE_TYPE FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = DATABASE()")->fetchAll() as $r) {
            $type = $r['ROUTINE_TYPE'] === 'FUNCTION' ? 'FUNCTION' : 'PROCEDURE';
            $row = $one("SHOW CREATE $type `{$r['ROUTINE_NAME']}`");
            if (!empty($row[2])) {
                $w("DROP $type IF EXISTS `{$r['ROUTINE_NAME']}`");
                $w('-- ROUTINE' . "\n" . $strip($row[2]));
            }
        }
        foreach ($src->query("SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE()")->fetchAll(PDO::FETCH_COLUMN) as $tr) {
            $row = $one("SHOW CREATE TRIGGER `$tr`");
            $w("DROP TRIGGER IF EXISTS `$tr`");
            $w('-- ROUTINE' . "\n" . $strip($row[2]));
        }
        $src->exec('COMMIT');
        $w('SET FOREIGN_KEY_CHECKS = 1');
        gzwrite($gz, "-- @@COMPLETE\n");
        gzclose($gz);
        rename($tmp, $file);
        $bytes = (int) filesize($file);
        \App\Core\Database::run("REPLACE INTO settings (setting_key, setting_value, description) VALUES ('last_backup_at', ?, 'Last database backup file (automatic)')", [date('Y-m-d H:i:s')]);
        return ['file' => $file, 'bytes' => $bytes, 'tables' => count($tables), 'rows' => $rowsTotal, 'seconds' => round(microtime(true) - $t0, 1)];
    }

    private static function q(string $s): string
    {
        return "'" . strtr($s, ["\\" => "\\\\", "'" => "\\'", "\0" => "\\0", "\n" => "\\n", "\r" => "\\r", "\x1a" => "\\Z"]) . "'";
    }

    // ------------------------------------------------------------------ restore / backup database

    /**
     * Load a backup file into a database (the backup database every night, or the main one when
     * restoring). Everything in the target is replaced. Returns [statements, skipped routines].
     */
    public static function restore(string $file, PDO $target): array
    {
        @set_time_limit(0);
        $raw = '';
        $gz = gzopen($file, 'rb') ?: throw new \RuntimeException('Cannot open ' . basename($file));
        while (!gzeof($gz)) {
            $raw .= gzread($gz, 1 << 20);
        }
        gzclose($gz);
        if (!str_contains($raw, '-- @@COMPLETE')) {
            throw new \RuntimeException(basename($file) . ' is incomplete — not restored.');
        }
        // empty the target first (tables, views, routines, triggers)
        $target->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($target->query("SELECT TABLE_NAME, TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()")->fetchAll() as $t) {
            $target->exec(($t['TABLE_TYPE'] === 'VIEW' ? 'DROP VIEW IF EXISTS `' : 'DROP TABLE IF EXISTS `') . $t['TABLE_NAME'] . '`');
        }
        foreach ($target->query("SELECT ROUTINE_NAME, ROUTINE_TYPE FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = DATABASE()")->fetchAll() as $r) {
            $target->exec('DROP ' . ($r['ROUTINE_TYPE'] === 'FUNCTION' ? 'FUNCTION' : 'PROCEDURE') . ' IF EXISTS `' . $r['ROUTINE_NAME'] . '`');
        }
        $n = 0;
        $skipped = 0;
        foreach (explode(self::END, $raw) as $stmt) {
            $stmt = trim(preg_replace('/^(--[^\n]*\n)+/', '', $stmt . "\n") ?? '');
            $isRoutine = (bool) preg_match('/^CREATE\s+(DEFINER\S*\s+)?(TRIGGER|PROCEDURE|FUNCTION)/i', $stmt);
            if ($stmt === '' || str_starts_with($stmt, '-- @@COMPLETE')) {
                continue;
            }
            try {
                $target->exec($stmt);
                $n++;
            } catch (\PDOException $e) {
                if ($isRoutine && preg_match('/\[(1419|1227|1142|1044|1370)\]|SUPER/', $e->getMessage())) {
                    $skipped++;   // host does not allow triggers: the website applies these rules in PHP
                    continue;
                }
                throw $e;
            }
        }
        if ($skipped) {
            $target->exec("REPLACE INTO settings (setting_key, setting_value, description) VALUES ('db_triggers', '0', 'MySQL triggers not allowed by the host; rules run in PHP (DbRules service)')");
        }
        $target->exec('SET FOREIGN_KEY_CHECKS = 1');
        return [$n, $skipped];
    }

    /** Copy the newest backup into the backup database (config 'db_backup'). */
    public static function mirror(string $file): array
    {
        $c = (array) config('db_backup', []);
        if (empty($c['name'])) {
            return ['ok' => false, 'reason' => 'no backup database set in config.php'];
        }
        $main = (array) config('db');
        if (($c['host'] ?? '') === ($main['host'] ?? '') && ($c['name'] ?? '') === ($main['name'] ?? '') && (int) ($c['port'] ?? 3306) === (int) ($main['port'] ?? 3306)) {
            return ['ok' => false, 'reason' => 'the backup database must be a different database'];
        }
        $t0 = microtime(true);
        $pdo = self::connect($c);
        [$n, $skipped] = self::restore($file, $pdo);
        $pdo->prepare("REPLACE INTO settings (setting_key, setting_value, description) VALUES ('backup_copy_of', ?, 'This database is the nightly backup copy')")
            ->execute([date('Y-m-d H:i:s') . ' from ' . basename($file)]);
        \App\Core\Database::run("REPLACE INTO settings (setting_key, setting_value, description) VALUES ('last_mirror_at', ?, 'Last copy into the backup database')", [date('Y-m-d H:i:s')]);
        return ['ok' => true, 'statements' => $n, 'skipped' => $skipped, 'seconds' => round(microtime(true) - $t0, 1)];
    }

    /** Keep the newest N backup files. */
    public static function rotate(): int
    {
        $keep = max(2, (int) config('backup.keep', 14));
        $files = self::files();
        $gone = 0;
        foreach (array_slice($files, $keep) as $f) {
            @unlink(self::dir() . '/' . $f['name']) && $gone++;
        }
        return $gone;
    }

    /** @return list<array{name:string, bytes:int, time:int}> newest first */
    public static function files(): array
    {
        $out = [];
        foreach (glob(self::dir() . '/careertrack-*.sql.gz') ?: [] as $f) {
            $out[] = ['name' => basename($f), 'bytes' => (int) filesize($f), 'time' => (int) filemtime($f)];
        }
        usort($out, static fn ($a, $b) => $b['time'] <=> $a['time']);
        return $out;
    }
}
