<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Small file cache for expensive dashboard / report numbers (works on any hosting: no Redis needed).
 * Values are only ever aggregates (counts, rates) keyed by the user's data scope, so one cached
 * value is never shown to someone with a different scope.
 */
final class Cache
{
    public static function remember(string $key, int $ttl, callable $fn): mixed
    {
        if ($ttl <= 0 || (int) config('cache.ttl', 600) <= 0) {
            return $fn();
        }
        $dir = self::dir();
        $file = $dir . '/' . hash('sha256', $key) . '.cache';
        if (is_file($file) && filemtime($file) > time() - $ttl) {
            $raw = @file_get_contents($file);
            if ($raw !== false) {
                $v = @unserialize($raw, ['allowed_classes' => false]);
                if ($v !== false || $raw === serialize(false)) {
                    return $v;
                }
            }
        }
        // Stampede guard: when many users arrive at once with an empty cache, only ONE process
        // runs the expensive query; the others wait for it and then read its result.
        $lock = (is_dir($dir) || @mkdir($dir, 0770, true)) ? @fopen($file . '.lock', 'c') : false;
        if ($lock && flock($lock, LOCK_EX)) {
            clearstatcache(true, $file);
            if (is_file($file) && filemtime($file) > time() - $ttl && ($raw = @file_get_contents($file)) !== false) {
                $v = @unserialize($raw, ['allowed_classes' => false]);
                if ($v !== false || $raw === serialize(false)) {
                    flock($lock, LOCK_UN);
                    fclose($lock);
                    return $v;
                }
            }
        }
        try {
            $v = $fn();
            if (is_dir($dir)) {
                $tmp = $file . '.' . bin2hex(random_bytes(4));
                if (@file_put_contents($tmp, serialize($v)) !== false) {
                    @rename($tmp, $file);   // atomic: readers never see half a file
                }
            }
        } finally {
            if ($lock) { flock($lock, LOCK_UN); fclose($lock); }   // only after the file is written
        }
        return $v;
    }

    /** Called after data changes that should show at once (e.g. a new admission). */
    public static function flush(): void
    {
        foreach (glob(self::dir() . '/*.cache*') ?: [] as $f) {
            @unlink($f);
        }
    }

    private static function dir(): string
    {
        return dirname(__DIR__, 2) . '/storage/cache';
    }
}
