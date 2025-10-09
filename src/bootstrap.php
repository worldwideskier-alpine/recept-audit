<?php
declare(strict_types=1);

namespace ReceptAudit;

use ReceptAudit\Support\Config;
use ReceptAudit\Support\Log;

const APP_NAMESPACE = 'ReceptAudit\\';

if (!defined('BASE_DIR')) {
    define('BASE_DIR', dirname(__DIR__));
}

if (!defined('STORAGE_DIR')) {
    define('STORAGE_DIR', BASE_DIR . '/storage');
}

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, APP_NAMESPACE)) {
        return;
    }

    $relative = substr($class, strlen(APP_NAMESPACE));
    $path = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($path)) {
        require $path;
    }
});

if (!is_dir(STORAGE_DIR)) {
    mkdir(STORAGE_DIR, 0775, true);
}

if (!is_dir(STORAGE_DIR . '/logs')) {
    mkdir(STORAGE_DIR . '/logs', 0775, true);
}

Config::init(require __DIR__ . '/config.php');
Log::init(STORAGE_DIR . '/logs/app.log');
