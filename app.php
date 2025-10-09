<?php
declare(strict_types=1);

use ReceptAudit\Application;

require __DIR__ . '/src/bootstrap.php';

$app = new Application();
$app->run();
