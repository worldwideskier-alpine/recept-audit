<?php
declare(strict_types=1);

namespace App\Support;

final class Log
{
    public static function write(string $event, array $context = [], string $level = 'info'): void
    {
        $config = Config::get();
        $path = $config['storage']['logs'] ?? Paths::basePath('storage/logs/app.log');
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $record = [
            'timestamp' => (new \DateTimeImmutable('now'))
                ->setTimezone(new \DateTimeZone('UTC'))
                ->format(DATE_ATOM),
            'level' => $level,
            'event' => $event,
            'context' => $context,
            'file' => $context['file'] ?? null,
            'line' => $context['line'] ?? null,
        ];
        file_put_contents($path, json_encode($record, JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND | LOCK_EX);
    }
}
