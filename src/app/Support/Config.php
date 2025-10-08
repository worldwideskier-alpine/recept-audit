<?php
declare(strict_types=1);

namespace App\Support;

final class Config
{
    private static ?self $instance = null;

    /** @var array<string, mixed> */
    private array $config;

    private function __construct()
    {
        /** @var array<string, mixed> $cfg */
        $cfg = require base_path('config.php');
        $this->config = $cfg;
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = $this->config;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}
