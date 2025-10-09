<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Support\Request;
use App\Support\Response;

final class EnvController
{
    public static function show(Request $request): Response
    {
        $payload = [
            'ok' => true,
            'kind' => 'env',
            'http_client' => 'curl',
        ];
        return Response::json($payload);
    }
}
