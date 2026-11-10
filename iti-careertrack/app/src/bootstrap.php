<?php
declare(strict_types=1);

// ---------------------------------------------------------------------------
// Autoloader: App\Foo\Bar -> src/Foo/Bar.php
// ---------------------------------------------------------------------------
spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

require __DIR__ . '/helpers.php';

$configFile = __DIR__ . '/../config/config.php';
$GLOBALS['CT_CONFIG'] = require (is_file($configFile) ? $configFile : __DIR__ . '/../config/config.example.php');

date_default_timezone_set(config('app.timezone', 'Asia/Kolkata'));
error_reporting(E_ALL);
// Never show PHP errors / stack traces to visitors (they reveal server paths and SQL). Set
// 'debug' => true in config.php ['app'] only on your own computer to see them on screen.
ini_set('display_errors', (PHP_SAPI === 'cli' || config('app.debug') === true) ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../storage/logs/php-error.log');
if (PHP_SAPI !== 'cli') {
    // Anything not handled: log the details, show a plain error page (no paths, no SQL)
    set_exception_handler(static function (\Throwable $e): void {
        $ref = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        error_log("[ref $ref] " . $e::class . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString());
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
        }
        if (config('app.debug') === true) {
            echo '<pre>' . htmlspecialchars((string) $e, ENT_QUOTES) . '</pre>';
            return;
        }
        echo '<!doctype html><meta charset="utf-8"><title>Error</title><div style="font-family:sans-serif;max-width:560px;margin:60px auto;text-align:center">'
           . '<h1 style="color:#b91c1c">Something went wrong</h1><p>The error has been recorded. Please go back and try again.</p>'
           . '<p style="color:#666">Reference: ' . $ref . '</p></div>';
    });
}
