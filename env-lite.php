<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD') {
    exit;
}

echo json_encode(['ok' => true, 'kind' => 'env-lite'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

