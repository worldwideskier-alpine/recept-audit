<?php
declare(strict_types=1);

$schema = file_get_contents(__DIR__ . '/../schema.sql');
if ($schema === false || strpos($schema, 'CREATE TABLE') === false) {
    throw new \RuntimeException('schema.sql must contain CREATE TABLE statements');
}
