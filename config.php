<?php
return [
    'app' => [
        'name' => 'Recept Audit',
        'base_path' => __DIR__,
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
    'http' => [
        'timeout' => 30,
        'retry' => 3,
    ],
    'storage' => [
        'base' => __DIR__ . '/storage',
        'logs' => __DIR__ . '/storage/logs/app.log',
    ],
    'sync' => [
        'max_per_cycle' => 1000,
        'db_min_rows' => 100,
    ],
    'security' => [
        'session_name' => 'recept_audit_session',
    ],
];
