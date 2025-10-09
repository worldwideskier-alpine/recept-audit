<?php
declare(strict_types=1);

namespace ReceptAudit\Support;

final class Config
{
    /** @var array<string, mixed> */
    private static array $config = [];

    /**
     * @param array<string, mixed> $config
     */
    public static function init(array $config): void
    {
        self::$config = $config;
    }

    /**
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$config[$key] ?? $default;
    }

    public static function baseUrl(): string
    {
        return (string) self::get('base_url', '');
    }
}
