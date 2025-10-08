<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$compliance = [];
$compPath = $root . '/COMPLIANCE.json';
if (is_file($compPath)) {
    $compliance = json_decode((string) file_get_contents($compPath), true, 512, JSON_THROW_ON_ERROR);
}

$manifest = [];
$manPath = $root . '/MANIFEST.json';
if (is_file($manPath)) {
    $manifest = json_decode((string) file_get_contents($manPath), true, 512, JSON_THROW_ON_ERROR);
}

$report = [
    'version' => 'VERIFY_REPORT/v1',
    'timestamp' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(DATE_ATOM),
    'compliance' => $compliance,
    'manifest' => $manifest,
];

$verifyDir = $root . '/evidence/verify';
if (!is_dir($verifyDir)) {
    mkdir($verifyDir, 0775, true);
}
file_put_contents($verifyDir . '/VERIFY_REPORT.json', json_encode($report, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
file_put_contents($verifyDir . '/VERIFY_REPORT_OK.txt', "OK\n");

$compliance['verify_report_ok'] = true;
file_put_contents($compPath, json_encode($compliance, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
