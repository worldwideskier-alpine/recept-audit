<?php
declare(strict_types=1);

namespace ReceptAudit\Support;

final class Log
{
    private static string $logFile;

    public static function init(string $logFile): void
    {
        self::$logFile = $logFile;
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function event(string $event, array $context = [], string $level = 'info'): void
    {
        self::write([
            'timestamp' => gmdate('c'),
            'level' => $level,
            'event' => $event,
            'context' => $context,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function write(array $payload): void
    {
        if (!isset(self::$logFile)) {
            return;
        }

        $line = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($line === false) {
            return;
        }

        file_put_contents(self::$logFile, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
