<?php
declare(strict_types=1);

define('BASE_DIR', __DIR__ . '/..');
define('APP_DIR', __DIR__);

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = APP_DIR . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});

require_once APP_DIR . '/Support/helpers.php';

App\Support\Storage::ensure();
