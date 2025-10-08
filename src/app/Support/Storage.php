<?php
declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class Storage
{
    public static function ensure(): void
    {
        $config = Config::getInstance();
        $baseDir = $config->get('storage.base');
        if (!is_string($baseDir)) {
            throw new RuntimeException('storage.base configuration missing');
        }

        $paths = [
            $baseDir,
            $baseDir . '/logs',
        ];

        foreach ($paths as $path) {
            if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
                throw new RuntimeException('Failed to create storage directory: ' . $path);
            }
        }

        $logFile = $config->get('storage.logs');
        if (is_string($logFile) && !file_exists($logFile)) {
            touch($logFile);
        }
    }
}
