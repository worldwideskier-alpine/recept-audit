<?php
declare(strict_types=1);

use PDO;
use Throwable;
use function App\ensure_storage_directories;
use function App\handle_exception;
use function App\href;
use function App\is_head;
use function App\log_line;
use function App\redirect;
use function App\render_html;
use function App\request_path;
use function App\respond_json;

require __DIR__ . '/src/helpers.php';

set_exception_handler('App\\handle_exception');
ensure_storage_directories();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = request_path();

// Early BNORM to ensure /env/ behaves same as /env
if ($path === '/env/') {
    $path = '/env';
}

if ($path === '/health/') {
    $path = '/health';
}

switch ($path) {
    case '/env':
        handleEnv($method);
        return;
    case '/health':
        handleHealth($method);
        return;
    case '/':
        redirect('/login');
        return;
    case '/login':
        renderLogin();
        return;
    case '/provider':
        redirect('/provider/login');
        return;
    case '/provider/login':
        renderProviderLogin();
        return;
    case '/provider/setup':
        handleProviderSetup($method);
        return;
    default:
        respond_json(['ok' => false, 'error' => 'not_found'], 404, !is_head($method));
}

/**
 * Handle /env endpoint.
 */
function handleEnv(string $method): void
{
    $payload = [
        'ok' => true,
        'env' => 'production',
    ];

    respond_json($payload, 200, !is_head($method));
}

/**
 * Handle /health endpoint with minimal boot sequence.
 */
function handleHealth(string $method): void
{
    $result = [
        'ok' => true,
        'db_ok' => false,
        'initialized' => false,
    ];

    try {
        $pdo = db();
        $result['db_ok'] = true;

        ensureSchema($pdo);
        $result['initialized'] = true;

        log_line('health_min_boot_pass');
    } catch (Throwable $e) {
        $result['ok'] = false;
        $result['error'] = $e->getMessage();
        log_line('health_min_boot_fail', ['error' => $e->getMessage()]);
    }

    respond_json($result, $result['ok'] ? 200 : 500, !is_head($method));
}

/**
 * Render general login placeholder.
 */
function renderLogin(): void
{
    $body = '<h1>ログイン</h1><p>ここからシステムへログインできます。</p>';
    render_html('ログイン', $body);
}

/**
 * Render provider login page.
 */
function renderProviderLogin(): void
{
    $body = '<h1>Provider Dashboard</h1>'
        . '<form method="post" action="' . htmlspecialchars(href('/provider/login'), ENT_QUOTES, 'UTF-8') . '">'
        . '<label>Email<input type="email" name="email" required></label>'
        . '<label>Password<input type="password" name="password" required></label>'
        . '<button type="submit">ログイン</button>'
        . '</form>';

    render_html('Provider Login', $body);
}

/**
 * Handle /provider/setup with no persistence (placeholder).
 */
function handleProviderSetup(string $method): void
{
    header('X-Robots-Tag: noindex, nofollow, noarchive');

    if ($method === 'GET' || $method === 'HEAD') {
        log_line('setup_allowed');

        $body = '<h1>初期セットアップ</h1>'
            . '<p>初回ユーザーを作成するには以下のフォームを送信します。</p>'
            . '<form method="post" action="' . htmlspecialchars(href('/provider/setup'), ENT_QUOTES, 'UTF-8') . '">'
            . '<label>Email<input type="email" name="email" required></label>'
            . '<label>Password<input type="password" name="password" required minlength="8"></label>'
            . '<button type="submit">作成</button>'
            . '</form>';

        if ($method === 'HEAD') {
            header('Cache-Control: no-store');
            header('Content-Type: text/html; charset=utf-8');
            return;
        }

        render_html('Provider Setup', $body);
        return;
    }

    if ($method === 'POST') {
        log_line('setup_created');
        redirect('/provider/login');
        return;
    }

    respond_json(['ok' => false, 'error' => 'method_not_allowed'], 405, !is_head($method));
}

/**
 * Create PDO connection using config.php.
 */
function db(): PDO
{
    $config = require __DIR__ . '/config.php';
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $config['db']['host'],
        $config['db']['port'],
        $config['db']['name']
    );

    return new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

/**
 * Ensure base schema exists (placeholder subset).
 */
function ensureSchema(PDO $pdo): void
{
    $queries = [
        'CREATE TABLE IF NOT EXISTS tenants (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;',
        'CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(32) NOT NULL,
            force_reset TINYINT(1) NOT NULL DEFAULT 0,
            tenant_id INT UNSIGNED NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_users_tenant_id FOREIGN KEY (tenant_id)
                REFERENCES tenants(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;',
        'CREATE TABLE IF NOT EXISTS provider_rules (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL UNIQUE,
            rule_condition TEXT NOT NULL,
            rule_action TEXT NOT NULL,
            version VARCHAR(64) NULL,
            source_date DATE NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;',
        'CREATE TABLE IF NOT EXISTS departments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(32) NOT NULL UNIQUE,
            name VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;',
        'CREATE TABLE IF NOT EXISTS rules_sync_state (
            id INT PRIMARY KEY,
            last_sha256 CHAR(64) NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;',
        'CREATE TABLE IF NOT EXISTS sync_runner_state (
            id INT PRIMARY KEY,
            backoff_seconds INT NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;',
        'CREATE TABLE IF NOT EXISTS sync_commands (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            status VARCHAR(32) NOT NULL,
            requested_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;',
    ];

    foreach ($queries as $query) {
        try {
            $pdo->exec($query);
            log_line('schema_exec_ok', ['sql' => $query]);
        } catch (Throwable $e) {
            log_line('schema_exec_failed', ['sql' => $query, 'error' => $e->getMessage()]);
        }
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = :table AND index_name = :name');
    $stmt->execute(['table' => 'sync_commands', 'name' => 'ix_sc_status_requested']);
    if ((int) $stmt->fetchColumn() === 0) {
        $pdo->exec('CREATE INDEX ix_sc_status_requested ON sync_commands (status, requested_at, id)');
        log_line('schema_index_created', ['name' => 'ix_sc_status_requested']);
    } else {
        log_line('schema_index_exists', ['name' => 'ix_sc_status_requested']);
    }

    $pdo->prepare('INSERT IGNORE INTO rules_sync_state (id, last_sha256) VALUES (1, NULL)')->execute();
    $pdo->prepare('INSERT IGNORE INTO sync_runner_state (id, backoff_seconds) VALUES (1, 0)')->execute();
    log_line('schema_bootstrap_ok');
}
