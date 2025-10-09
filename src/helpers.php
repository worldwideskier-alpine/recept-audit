<?php
declare(strict_types=1);

namespace App;

use Throwable;

const STORAGE_LOG = 'storage/logs/app.log';

/**
 * Ensure core storage directories exist.
 */
function ensure_storage_directories(): void
{
    $paths = [
        base_path('storage'),
        base_path('storage/logs'),
    ];

    foreach ($paths as $path) {
        if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
            throw new \RuntimeException(sprintf('Unable to create directory: %s', $path));
        }
    }
}

/**
 * Return an absolute path within the project root.
 */
function base_path(string $path = ''): string
{
    $root = \dirname(__DIR__);
    if ($path === '') {
        return $root;
    }

    return $root . '/' . ltrim($path, '/');
}

/**
 * Return the normalized request path component.
 */
function request_path(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH) ?: '/';

    if ($path === '') {
        return '/';
    }

    return '/' . ltrim($path, '/');
}

/**
 * Emit a JSON response with standard headers.
 *
 * @param array<string,mixed> $payload
 */
function respond_json(array $payload, int $status = 200, bool $sendBody = true): void
{
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
    }

    if ($sendBody) {
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

/**
 * Emit a redirect response honouring BNORM helpers.
 */
function redirect(string $path, int $status = 302): void
{
    $location = href($path);
    if (!headers_sent()) {
        http_response_code($status);
        header('Cache-Control: no-store');
        header('Location: ' . $location);
    }
}

/**
 * Basic HTML renderer with HTML LS baseline.
 */
function render_html(string $title, string $bodyHtml): void
{
    if (!headers_sent()) {
        header('Cache-Control: no-store');
        header('Content-Type: text/html; charset=utf-8');
    }

    echo '<!doctype html><html lang="ja"><head><meta charset="utf-8"><title>'
        . htmlspecialchars($title, ENT_QUOTES, 'UTF-8')
        . '</title></head><body><main>'
        . $bodyHtml
        . '</main></body></html>';
}

/**
 * Compute an application-relative href respecting subdirectory deployment.
 */
function href(string $path): string
{
    $clean = '/' . ltrim($path, '/');
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $baseDir = rtrim(\dirname($scriptName), '/');

    if ($baseDir === '' || $baseDir === '.') {
        return $clean;
    }

    return rtrim($baseDir, '/') . $clean;
}

/**
 * Write a structured log line.
 *
 * @param array<string,mixed> $context
 */
function log_line(string $event, array $context = []): void
{
    ensure_storage_directories();

    $payload = array_merge(
        [
            'timestamp' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(DATE_ATOM),
            'level' => 'info',
            'event' => $event,
        ],
        $context
    );

    $log = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    if ($log === false) {
        return;
    }

    $path = base_path(STORAGE_LOG);
    file_put_contents($path, $log . PHP_EOL, FILE_APPEND | LOCK_EX);
}

/**
 * Global exception handler to log unexpected failures.
 */
function handle_exception(Throwable $throwable): void
{
    log_line('unhandled_exception', [
        'message' => $throwable->getMessage(),
        'file' => $throwable->getFile(),
        'line' => $throwable->getLine(),
    ]);

    respond_json(['ok' => false, 'error' => 'internal_error'], 500);
}

/**
 * Normalize method for HEAD support.
 */
function is_head(string $method): bool
{
    return strtoupper($method) === 'HEAD';
}

