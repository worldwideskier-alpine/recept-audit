<?php
declare(strict_types=1);

namespace App\Support;

final class Config
{
    private static ?array $cache = null;

    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $config = self::all();
        foreach ($segments as $segment) {
            if (!is_array($config) || !array_key_exists($segment, $config)) {
                return $default;
            }
            $config = $config[$segment];
        }
        return $config;
    }

    public static function all(): array
    {
        if (self::$cache === null) {
            $path = config_path();
            /** @var array $loaded */
            $loaded = require $path;
            self::$cache = $loaded;
        }
        return self::$cache;
    }
}
