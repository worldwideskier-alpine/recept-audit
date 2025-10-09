<?php
declare(strict_types=1);

return [
    'app' => [
        'name' => 'Recept Audit',
        'env' => 'production',
        'debug' => false,
        'base_url' => '',
        'log_level' => 'info',
    ],
    'http' => [
        'timeout' => 30,
        'retry' => 3,
    ],
    'db' => [
        'host' => 'mysql320.phy.lolipop.lan',
        'port' => 3306,
        'name' => 'LAA1577731-recept',
        'user' => 'LAA1577731',
        'pass' => 'tP2H3ibSBegLQnDs',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_0900_ai_ci',
    ],
    'storage' => [
        'base' => 'storage',
    ],
];
