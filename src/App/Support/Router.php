<?php
declare(strict_types=1);

namespace App\Support;

use App\Controllers\AdminClerkController;
use App\Controllers\AdminDashboardController;
use App\Controllers\AuthController;
use App\Controllers\EnvController;
use App\Controllers\HealthController;
use App\Controllers\ProviderDashboardController;
use App\Controllers\ProviderRulesController;
use App\Controllers\ProviderSetupController;
use App\Controllers\ProviderTenantsController;
use App\Exceptions\HttpException;
use App\Support\Response;

final class Router
{
    /** @var array<string, callable> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->addRoute('GET', $path, $handler);
        $this->addRoute('HEAD', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    private function addRoute(string $method, string $path, callable $handler): void
    {
        $normalizedPath = rtrim($path, '/') ?: '/';
        $key = strtoupper($method) . ' ' . $normalizedPath;
        $this->routes[$key] = $handler;
    }

    public function dispatch(Request $request): Response
    {
        $key = $request->method . ' ' . $request->path;
        if (isset($this->routes[$key])) {
            return ($this->routes[$key])($request);
        }
        throw new HttpException(404, 'Not Found');
    }

    public static function buildDefault(): self
    {
        $router = new self();
        $router->get('/env', [EnvController::class, 'show']);
        $router->get('/health', [HealthController::class, 'show']);
        $router->post('/health', [HealthController::class, 'show']);
        $router->get('/', [AuthController::class, 'redirectToLogin']);
        $router->get('/login', [AuthController::class, 'showLogin']);
        $router->post('/login', [AuthController::class, 'handleLogin']);
        $router->get('/provider/login', [AuthController::class, 'showProviderLogin']);
        $router->post('/provider/login', [AuthController::class, 'handleProviderLogin']);
        $router->get('/provider/setup', [ProviderSetupController::class, 'show']);
        $router->post('/provider/setup', [ProviderSetupController::class, 'store']);
        $router->get('/provider/dashboard', [ProviderDashboardController::class, 'show']);
        $router->get('/provider/tenants', [ProviderTenantsController::class, 'index']);
        $router->get('/provider/tenants/new', [ProviderTenantsController::class, 'create']);
        $router->post('/provider/tenants/new', [ProviderTenantsController::class, 'store']);
        $router->get('/provider/rules', [ProviderRulesController::class, 'index']);
        $router->get('/admin/dashboard', [AdminDashboardController::class, 'show']);
        $router->get('/admin/clerk/new', [AdminClerkController::class, 'create']);
        $router->post('/admin/clerk/new', [AdminClerkController::class, 'store']);
        return $router;
    }
}
