<?php
declare(strict_types=1);

namespace App\Support;

final class Config
{
    private static array $config;

    public static function get(): array
    {
        if (!isset(self::$config)) {
            $path = base_path('config.php');
            /** @var array $config */
            $config = require $path;
            self::$config = $config;
        }
        return self::$config;
    }

    public static function db(): array
    {
        $config = self::get();
        return $config['db'];
    }
}
