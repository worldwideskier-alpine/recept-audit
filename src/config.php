<?php
declare(strict_types=1);

return [
    'db' => [
        'host'      => 'mysql320.phy.lolipop.lan',
        'port'      => 3306,
        'name'      => 'LAA1577731-recept',
        'user'      => 'LAA1577731',
        'pass'      => 'tP2H3ibSBegLQnDs',
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_0900_ai_ci',
    ],
    'http' => [
        'no_store_header' => 'Cache-Control: no-store',
    ],
    'storage' => [
        'base' => __DIR__ . '/storage',
        'logs' => __DIR__ . '/storage/logs/app.log',
    ],
];
