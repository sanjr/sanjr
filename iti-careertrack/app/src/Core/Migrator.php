<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Automatic database update after a new version of the website is uploaded.
 *
 * Updating = upload the new ZIP over the old folder. app/config/config.php (database name,
 * user, password) is not in the ZIP, so it stays; on the next page view the missing
 * database/mysql/NN_*.sql migrations listed below run once. No installer, no password.
 */
final class Migrator
{
    /** version => file. Add new migrations at the end. */
    public const MIGRATIONS = [7 => '07_performance.sql', 8 => '08_trade_master.sql'];

    public static function latest(): int
    {
        return (int) max(array_keys(self::MIGRATIONS));
    }

    /** Cheap check on every request: a marker file says "already at the latest version". */
    public static function runIfNeeded(): void
    {
        $marker = self::markerFile();
        if (is_file($marker) && (int) @file_get_contents($marker) >= self::latest()) {
            return;
        }
        $lock = @fopen($marker . '.lock', 'c');
        if ($lock) {
            flock($lock, LOCK_EX);   // only one request runs the update; the others wait for it
        }
        try {
            self::run();
            @file_put_contents($marker, (string) self::latest());
        } catch (\Throwable $e) {
            // Never break the website because of an update step; the error is logged and retried later.
            error_log('ITI CareerTrack database update failed: ' . $e->getMessage());
        } finally {
            if ($lock) {
                flock($lock, LOCK_UN);
                fclose($lock);
            }
        }
    }

    public static function run(): void
    {
        $pdo = Database::pdo();
        $current = (int) ($pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'schema_version'")->fetchColumn() ?: 6);
        $dir = self::sqlDir();
        $maria = stripos((string) $pdo->query('SELECT VERSION()')->fetchColumn(), 'mariadb') !== false;
        foreach (self::MIGRATIONS as $version => $file) {
            if ($version <= $current) {
                continue;
            }
            if (!is_file($dir . '/' . $file)) {
                throw new \RuntimeException("Missing database/mysql/$file");
            }
            foreach (self::statements($dir . '/' . $file) as $sql) {
                if ($maria) {
                    $sql = preg_replace('/\bDROP\s+CHECK\s+/i', 'DROP CONSTRAINT ', $sql);
                }
                try {
                    $pdo->exec($sql);
                } catch (\PDOException $e) {
                    // index already there (installed by hand) = fine
                    if (!str_contains($e->getMessage(), '1061') && !str_contains($e->getMessage(), 'Duplicate key name')) {
                        throw $e;
                    }
                }
            }
            self::setVersion($pdo, $version);
        }
    }

    public static function setVersion(\PDO $pdo, int $version): void
    {
        $pdo->prepare("REPLACE INTO settings (setting_key, setting_value, description) VALUES ('schema_version', ?, 'Database version (automatic updates)')")
            ->execute([(string) $version]);
    }

    public static function sqlDir(): string
    {
        $app = dirname(__DIR__, 2);
        return is_dir(dirname($app) . '/database/mysql') ? dirname($app) . '/database/mysql' : $app . '/database/mysql';
    }

    private static function markerFile(): string
    {
        $dir = dirname(__DIR__, 2) . '/storage/cache';
        if (!is_dir($dir)) {
            @mkdir($dir, 0770, true);
        }
        return $dir . '/schema_version';
    }

    /** SQL file -> statements (understands DELIMITER blocks). */
    public static function statements(string $file): \Generator
    {
        $fh = fopen($file, 'rb');
        $delim = ';';
        $buf = '';
        while (($line = fgets($fh)) !== false) {
            $trim = trim($line);
            if ($buf === '' && ($trim === '' || str_starts_with($trim, '--') || str_starts_with($trim, '#'))) {
                continue;
            }
            if (preg_match('/^DELIMITER\s+(\S+)\s*$/i', $trim, $m)) {
                $delim = $m[1];
                continue;
            }
            $buf .= $line;
            if (str_ends_with(rtrim($line), $delim)) {
                $stmt = trim(substr(rtrim($buf), 0, -strlen($delim)));
                $buf = '';
                if ($stmt !== '') {
                    yield $stmt;
                }
            }
        }
        if (trim($buf) !== '') {
            yield trim($buf);
        }
        fclose($fh);
    }
}
