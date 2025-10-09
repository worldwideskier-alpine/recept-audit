<?php
declare(strict_types=1);

namespace App\Support;

final class Request
{
    private function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $post,
        public readonly array $cookies,
        public readonly array $server
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = explode('?', $uri, 2)[0];
        $path = '/' . ltrim($path, '/');
        $path = rtrim($path, '/') ?: '/';
        return new self(
            $method,
            $path,
            $_GET,
            $_POST,
            $_COOKIE,
            $_SERVER
        );
    }

    public function isHead(): bool
    {
        return $this->method === 'HEAD';
    }

    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }
}
