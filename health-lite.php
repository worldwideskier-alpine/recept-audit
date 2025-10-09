<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

echo json_encode([
    'ok' => true,
    'kind' => 'health-lite',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
