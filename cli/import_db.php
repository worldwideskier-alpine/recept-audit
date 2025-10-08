<?php
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use App\Support\Schema;

Schema::ensure();

echo "Import completed" . PHP_EOL;
