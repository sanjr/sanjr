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
ini_set('display_errors', config('app.env') === 'production' ? '0' : '1');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../storage/logs/php-error.log');
