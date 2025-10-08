<?php
declare(strict_types=1);

namespace App\Support;

final class Paths
{
    private static string $basePath;
    private static string $baseUrl;

    public static function bootstrap(): void
    {
        if (isset(self::$basePath)) {
            return;
        }
        self::$basePath = dirname(__DIR__, 2);
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $dir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        self::$baseUrl = $dir === '' || $dir === '.' ? '' : $dir;
    }

    public static function basePath(string $path = ''): string
    {
        return rtrim(self::$basePath . '/' . ltrim($path, '/'), '/');
    }

    public static function href(string $path = ''): string
    {
        $normalised = '/' . ltrim($path, '/');
        if ($normalised === '//') {
            $normalised = '/';
        }
        return (self::$baseUrl === '' ? '' : self::$baseUrl) . $normalised;
    }
}
