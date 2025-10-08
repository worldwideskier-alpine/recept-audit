<?php
declare(strict_types=1);

namespace App\Support;

use Closure;

final class Router
{
    /** @var array<string, array<string, Closure>> */
    private array $routes = [];

    public function get(string $path, Closure $handler): self
    {
        $this->routes['GET'][$path] = $handler;
        return $this;
    }

    public function post(string $path, Closure $handler): self
    {
        $this->routes['POST'][$path] = $handler;
        return $this;
    }

    public function head(string $path, Closure $handler): self
    {
        $this->routes['HEAD'][$path] = $handler;
        return $this;
    }

    public function dispatch(string $method, string $path): void
    {
        $method = strtoupper($method);
        $path = $this->normalizePath($path);
        $handler = $this->routes[$method][$path] ?? null;

        if ($handler === null && $method === 'HEAD') {
            $handler = $this->routes['GET'][$path] ?? null;
            $headFallback = true;
        } else {
            $headFallback = false;
        }

        if ($handler === null) {
            Response::json([
                'ok' => false,
                'error' => 'not_found',
            ], 404);
            return;
        }

        $handler($method, $path, $headFallback);
    }

    private function normalizePath(string $path): string
    {
        $parsed = parse_url($path, PHP_URL_PATH);
        if ($parsed === null) {
            return '/';
        }
        if ($parsed === '') {
            return '/';
        }
        return rtrim($parsed, '/') ?: '/';
    }
}
