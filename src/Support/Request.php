<?php
declare(strict_types=1);

namespace ReceptAudit\Support;

final class Request
{
    private string $method;
    private string $path;

    private function __construct(string $method, string $path)
    {
        $this->method = strtoupper($method);
        $this->path = $path;
    }

    public static function fromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        if ($scriptDir !== '' && str_starts_with($path, $scriptDir)) {
            $path = substr($path, strlen($scriptDir));
        }

        if ($path === '') {
            $path = '/';
        }

        return new self($method, self::normalize($path));
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function is(string $method, string $path): bool
    {
        return $this->method === strtoupper($method) && $this->path === self::normalize($path);
    }

    private static function normalize(string $path): string
    {
        $normalized = '/' . ltrim($path, '/');
        if ($normalized !== '/' && str_ends_with($normalized, '/')) {
            $normalized = rtrim($normalized, '/');
        }

        return $normalized;
    }
}
