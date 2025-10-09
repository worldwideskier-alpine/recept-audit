<?php
declare(strict_types=1);

namespace App\Support;

use DateTimeImmutable;
use RuntimeException;

function base_path(string $path = ''): string
{
    $full = \BASE_DIR . ($path !== '' ? '/' . ltrim($path, '/') : '');
    return $full;
}

function storage_path(string $path = ''): string
{
    $base = \STORAGE_DIR;
    return $base . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

function config_path(): string
{
    return base_path('config.php');
}

function load_config(): array
{
    static $config;
    if ($config === null) {
        $path = config_path();
        if (!is_file($path)) {
            throw new RuntimeException('config.php not found at base path');
        }
        $config = require $path;
    }
    return $config;
}

function app_config(string $key, mixed $default = null): mixed
{
    $segments = explode('.', $key);
    $value = load_config();
    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

function now(): DateTimeImmutable
{
    return new DateTimeImmutable('now');
}

function ensure_dir(string $path): void
{
    if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
        throw new RuntimeException("Unable to create directory: {$path}");
    }
}
