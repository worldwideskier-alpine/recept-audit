<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\Support\Config;
use App\Support\HealthCheck;
use App\Support\Response;
use App\Support\Router;
use function App\Support\base_path;

$router = (new Router())
    ->get('/env', function (string $method, string $path, bool $headFallback): void {
        $payload = [
            'ok' => true,
            'kind' => 'env',
            'script' => 'app.php',
        ];
        Response::json($payload, 200, $headFallback);
    })
    ->head('/env', function (string $method, string $path, bool $headFallback): void {
        Response::json([
            'ok' => true,
            'kind' => 'env',
        ], 200, true);
    })
    ->get('/health', function (string $method, string $path, bool $headFallback): void {
        $result = HealthCheck::run();
        Response::json($result, $result['ok'] ? 200 : 500, $headFallback);
    })
    ->head('/health', function (string $method, string $path, bool $headFallback): void {
        $result = HealthCheck::run();
        Response::json($result, $result['ok'] ? 200 : 500, true);
    })
    ->get('/', function (): void {
        Response::redirect('/login');
    })
    ->get('/login', function (): void {
        Response::json([
            'ok' => true,
            'kind' => 'login',
        ]);
    });

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
