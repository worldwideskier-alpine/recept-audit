<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\Support\Response;

Response::json([
    'ok' => true,
    'kind' => 'health-lite',
    'timestamp' => gmdate('c'),
]);
