<?php
declare(strict_types=1);

$cwd = getcwd();
if ($cwd === false) {
    throw new RuntimeException('Cannot determine working directory');
}

$output = shell_exec('cd ' . escapeshellarg($cwd) . ' && php src/env-lite.php');
if ($output === null) {
    throw new RuntimeException('Failed to execute env-lite.php');
}

$data = json_decode(trim($output), true);
if (!is_array($data)) {
    throw new RuntimeException('env-lite did not return JSON');
}
if (($data['ok'] ?? null) !== true || ($data['kind'] ?? null) !== 'env-lite') {
    throw new RuntimeException('env-lite response invalid');
}

$envRequest = [
    'REQUEST_METHOD' => 'GET',
    'REQUEST_URI' => '/health',
    'SERVER_PROTOCOL' => 'HTTP/1.1',
];
$cmd = 'cd ' . escapeshellarg($cwd) . ' && php -r ' . escapeshellarg('$_SERVER=' . var_export($envRequest, true) . '; require "src/app.php";');
$health = shell_exec($cmd);
if ($health === null) {
    throw new RuntimeException('Failed to execute health endpoint');
}
$healthData = json_decode(trim($health), true);
if (!is_array($healthData) || ($healthData['kind'] ?? null) !== 'health') {
    throw new RuntimeException('health response invalid');
}
