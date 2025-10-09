<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\HttpException;
use App\Support\Request;
use App\Support\Response;
use App\Support\Schema;
use Throwable;
use function App\Support\log_exception;
use function App\Support\log_line;

final class HealthController
{
    public static function show(Request $request): Response
    {
        try {
            $schema = Schema::ensure();
            log_line('health_min_boot_pass', ['schema' => $schema]);
            $payload = [
                'ok' => true,
                'kind' => 'health',
                'db_ok' => true,
                'initialized' => true,
                'schema' => $schema,
            ];
            return Response::json($payload);
        } catch (Throwable $throwable) {
            log_exception($throwable, 'health_min_boot_fail');
            $payload = [
                'ok' => false,
                'kind' => 'health',
                'db_ok' => false,
                'error' => $throwable->getMessage(),
            ];
            return Response::json($payload, 500);
        }
    }
}
