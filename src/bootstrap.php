<?php
declare(strict_types=1);

$__BASE_DIR = dirname(__DIR__);

function base_path(string $path = ''): string
{
    global $__BASE_DIR;
    return rtrim($__BASE_DIR . '/' . ltrim($path, '/'), '/');
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = base_path('src/' . str_replace('\\', '/', $relative) . '.php');
    if (file_exists($file)) {
        require_once $file;
    }
});

require_once base_path('src/Support/helpers.php');

App\Support\Paths::bootstrap();
