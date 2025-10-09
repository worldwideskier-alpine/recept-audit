<?php
declare(strict_types=1);

namespace ReceptAudit\Support;

final class Url
{
    public static function basePath(): string
    {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');

        return $dir === '' ? '' : $dir;
    }

    public static function href(string $path = ''): string
    {
        $normalized = '/' . ltrim($path, '/');
        $base = self::basePath();

        return ($base !== '' ? $base : '') . $normalized;
    }
}
