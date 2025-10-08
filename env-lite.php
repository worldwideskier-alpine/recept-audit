<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Support\Response;

Response::json([
    'ok' => true,
    'kind' => 'env-lite',
]);
