<?php
declare(strict_types=1);

namespace App\Support;

use function json_encode;

function base_path(string $path = ''): string
{
    $base = BASE_DIR;
    return $path === '' ? $base : $base . '/' . ltrim($path, '/');
}

function log_line(array $context): void
{
    $logFile = Config::getInstance()->get('storage.logs');
    $context['timestamp'] = $context['timestamp'] ?? gmdate('c');
    $context['level'] = $context['level'] ?? 'info';
    $line = json_encode($context, JSON_UNESCAPED_SLASHES);
    if ($line === false) {
        $line = '{"timestamp":"' . gmdate('c') . '","level":"error","event":"log_json_encode_failed"}';
    }
    file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
}
