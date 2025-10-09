<?php
declare(strict_types=1);

namespace ReceptAudit;

use ReceptAudit\Support\Log;
use ReceptAudit\Support\Request;
use ReceptAudit\Support\Response;
use ReceptAudit\Support\Schema;
use ReceptAudit\Support\Url;

final class Application
{
    public function run(): void
    {
        $request = Request::fromGlobals();
        $path = $request->path();

        if ($path === '/env') {
            $this->handleEnv();
            return;
        }

        if ($path === '/health') {
            $this->handleHealth($request);
            return;
        }

        if ($path === '/') {
            Response::redirect(Url::href('/login'));
            return;
        }

        Response::json([
            'ok' => false,
            'status' => 404,
            'message' => 'Not Found',
        ], 404);
    }

    private function handleEnv(): void
    {
        Response::json([
            'ok' => true,
            'kind' => 'env',
            'timestamp' => gmdate('c'),
        ]);
    }

    private function handleHealth(Request $request): void
    {
        $result = Schema::ensure();
        $ok = $result['ok'] && empty($result['errors']);
        $payload = [
            'ok' => $ok,
            'db_ok' => $ok,
            'initialized' => $ok,
            'errors' => $result['errors'],
            'timestamp' => gmdate('c'),
        ];

        if ($ok) {
            Log::event('health_min_boot_pass', ['result' => $payload]);
        } else {
            Log::event('health_min_boot_fail', ['result' => $payload], 'error');
        }

        if ($request->method() === 'HEAD') {
            http_response_code($ok ? 200 : 500);
            header('Cache-Control: no-store');
            header('Content-Type: application/json');
            return;
        }

        Response::json($payload, $ok ? 200 : 500);
    }
}
