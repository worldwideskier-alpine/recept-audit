<?php
declare(strict_types=1);

namespace App\Support;

use Throwable;

const LOG_LEVELS = ['debug', 'info', 'notice', 'warning', 'error', 'critical'];

function log_line(string $event, array $context = [], string $level = 'info'): void
{
    if (!in_array($level, LOG_LEVELS, true)) {
        $level = 'info';
    }

    $record = [
        'timestamp' => now()->format('c'),
        'level' => $level,
        'event' => $event,
        'context' => $context,
    ];

    ensure_dir(dirname(\LOG_FILE));
    file_put_contents(\LOG_FILE, json_encode($record, JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
}

function log_exception(Throwable $throwable, string $event = 'exception'): void
{
    log_line($event, [
        'message' => $throwable->getMessage(),
        'file' => $throwable->getFile(),
        'line' => $throwable->getLine(),
        'trace' => $throwable->getTraceAsString(),
    ], 'error');
}
