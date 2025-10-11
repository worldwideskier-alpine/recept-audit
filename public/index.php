<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\App;

$app = new App();
$name = $_GET['name'] ?? null;

header('Content-Type: text/plain; charset=utf-8');

try {
    echo $app->greet($name);
} catch (Throwable $exception) {
    http_response_code(500);
