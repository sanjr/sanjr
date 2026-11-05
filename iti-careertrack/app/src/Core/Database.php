<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;
use Throwable;

/** Thin PDO wrapper. ALWAYS uses prepared statements (no string-built values). */
final class Database
{
    private static ?PDO $pdo = null;
    private static bool $readOnly = false;

    /** True when the main database did not answer and the website runs on the backup copy (view only). */
    public static function readOnly(): bool
    {
        return self::$readOnly;
    }

    private static function open(array $c): PDO
    {
        return new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'] ?? 'localhost', (int) ($c['port'] ?? 3306), $c['name'] ?? ''),
            (string) ($c['user'] ?? ''), (string) ($c['pass'] ?? ''), [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
                PDO::ATTR_TIMEOUT            => 5,
            ]);
    }

    /** Used by the installer, which already holds a connection. */
    public static function usePdo(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            try {
                self::$pdo = self::open((array) config('db'));
            } catch (\PDOException $e) {
                // main database down: continue on the nightly backup copy, view only (no changes are lost
                // because nothing can be saved there) — config.php → 'db_backup', 'backup' => ['failover' => true]
                $b = (array) config('db_backup', []);
                if (empty($b['name']) || config('backup.failover', true) === false) {
                    throw $e;
                }
                self::$pdo = self::open($b);
                self::$readOnly = true;
                error_log('ITI CareerTrack: main database not reachable (' . $e->getCode() . '), running READ-ONLY on the backup database');
            }
            // same text rules as the tables (utf8mb4_unicode_ci) — MariaDB 11 on shared hosting otherwise
            // defaults to another collation and comparing two values fails with error 1267
            self::$pdo->exec('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
            self::$pdo->exec("SET time_zone = '+05:30', SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION,ONLY_FULL_GROUP_BY'");
        }
        return self::$pdo;
    }

    public static function run(string $sql, array $params = []): PDOStatement
    {
        // Positional "?" parameters only.
        $st = self::pdo()->prepare($sql);
        $i = 1;
        foreach ($params as $v) {
            if (is_bool($v)) {
                $v = (int) $v;
            }
            $st->bindValue($i++, $v, is_int($v) ? PDO::PARAM_INT : (is_null($v) ? PDO::PARAM_NULL : PDO::PARAM_STR));
        }
        $st->execute();
        return $st;
    }

    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function value(string $sql, array $params = []): mixed
    {
        $v = self::run($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    /** INSERT a row from an associative array and return the new id. */
    public static function insert(string $table, array $row): int
    {
        $cols = array_keys($row);
        foreach ($cols as $c) {
            if (!preg_match('/^[a-z_][a-z0-9_]*$/', $c)) {
                throw new \InvalidArgumentException('Bad column name');
            }
        }
        $sql = sprintf('INSERT INTO `%s` (%s) VALUES (%s)', $table,
            implode(',', array_map(static fn ($c) => "`$c`", $cols)), implode(',', array_fill(0, count($cols), '?')));
        self::run($sql, array_values($row));
        return (int) self::pdo()->lastInsertId();
    }

    /** Run $fn inside a transaction; rolls back on any exception. */
    public static function tx(callable $fn): mixed
    {
        $pdo = self::pdo();
        if ($pdo->inTransaction()) {
            return $fn();
        }
        $pdo->beginTransaction();
        try {
            $result = $fn();
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Build "IN (?,?,?)" safely. */
    public static function in(array $values): string
    {
        return $values ? implode(',', array_fill(0, count($values), '?')) : 'NULL';
    }
}
