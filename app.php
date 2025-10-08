<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Support\Auth;
use App\Support\Config;
use App\Support\Csrf;
use App\Support\DB;
use App\Support\Response;
use App\Support\Schema;
use App\Support\View;
use PDO;
use Throwable;

Csrf::ensureSession();
Auth::init();

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rtrim($path, '/') === '' ? '/' : rtrim($path, '/');

switch (true) {
    case $path === '/env':
        handleEnv($method);
        break;
    case $path === '/health':
        handleHealth($method);
        break;
    case $path === '/login':
        handleLogin($method);
        break;
    case $path === '/logout':
        Auth::logout();
        Response::redirect(href('/login'));
        break;
    case $path === '/provider':
        Response::redirect(href('/provider/login'));
        break;
    case $path === '/provider/login':
        handleProviderLogin($method);
        break;
    case $path === '/provider/setup':
        handleProviderSetup($method);
        break;
    case $path === '/provider/dashboard':
        Auth::requireProviderLogin();
        renderProviderDashboard();
        break;
    case $path === '/provider/tenants':
        Auth::requireProviderLogin();
        renderProviderTenants();
        break;
    case $path === '/provider/tenants/new':
        Auth::requireProviderLogin();
        handleProviderTenantsNew($method);
        break;
    case $path === '/provider/db':
        Auth::requireProviderLogin();
        handleProviderDb($method);
        break;
    case $path === '/admin/clerk/new':
        Auth::requireRole(['admin']);
        handleAdminClerkNew($method);
        break;
    case $path === '/':
        Response::redirect(href('/login'));
        break;
    default:
        Response::json(['ok' => false, 'error' => 'not_found'], 404);
}

function handleEnv(string $method): void
{
    if (!in_array($method, ['GET', 'HEAD'], true)) {
        Response::json(['ok' => false, 'error' => 'method_not_allowed'], 405);
        return;
    }
    $config = Config::get();
    $payload = [
        'ok' => true,
        'kind' => 'env',
        'app' => $config['app']['name'] ?? 'recept-audit',
        'db_engine' => 'mysql8',
    ];
    if ($method === 'HEAD') {
        http_response_code(200);
        Response::applyNoStoreHeaders();
        header('Content-Type: application/json; charset=utf-8', true);
        return;
    }
    Response::json($payload);
}

function handleHealth(string $method): void
{
    if (!in_array($method, ['GET', 'HEAD'], true)) {
        Response::json(['ok' => false, 'error' => 'method_not_allowed'], 405);
        return;
    }
    try {
        $results = Schema::ensure();
        log_line('health_min_boot_pass', ['method' => $method]);
        if ($method === 'HEAD') {
            http_response_code(200);
            Response::applyNoStoreHeaders();
            header('Content-Type: application/json; charset=utf-8', true);
            return;
        }
        Response::json([
            'ok' => true,
            'kind' => 'health',
            'db_ok' => true,
            'initialized' => true,
            'results' => $results,
        ]);
    } catch (Throwable $e) {
        log_line('health_min_boot_fail', ['error' => $e->getMessage()], 'error');
        Response::json(['ok' => false, 'kind' => 'health', 'error' => 'boot_failed'], 500);
    }
}

function handleLogin(string $method): void
{
    if ($method === 'GET') {
        Response::html(View::render('General/Login', ['csrf' => csrf_token()]));
        return;
    }
    if ($method === 'POST') {
        $token = $_POST['_csrf'] ?? '';
        if (!verify_csrf($token)) {
            Response::json(['ok' => false, 'error' => 'invalid_csrf'], 422);
            return;
        }
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        if (Auth::attempt($email, $password)) {
            $user = Auth::user();
            $redirect = $user && $user['role'] === 'admin' ? '/admin/clerk/new' : '/provider/dashboard';
            Response::redirect(href($redirect));
            return;
        }
        Response::html(View::render('General/Login', [
            'csrf' => csrf_token(),
            'error' => '認証に失敗しました。',
        ]), 422);
        return;
    }
    Response::json(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

function handleProviderLogin(string $method): void
{
    if ($method === 'GET') {
        Response::html(View::render('Provider/Login', ['csrf' => csrf_token()]));
        return;
    }
    if ($method === 'POST') {
        $token = $_POST['_csrf'] ?? '';
        if (!verify_csrf($token)) {
            Response::html(View::render('Provider/Login', [
                'csrf' => csrf_token(),
                'error' => 'CSRFトークンが無効です。',
            ]), 422);
            return;
        }
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        if (Auth::attempt($email, $password) && (Auth::user()['role'] ?? '') === 'provider') {
            Response::redirect(href('/provider/dashboard'));
            return;
        }
        Response::html(View::render('Provider/Login', [
            'csrf' => csrf_token(),
            'error' => '認証に失敗しました。',
        ]), 401);
        return;
    }
    Response::json(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

function handleProviderSetup(string $method): void
{
    header('X-Robots-Tag: noindex, nofollow, noarchive', true);
    $pdo = DB::pdo();
    $count = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $allow = $count === 0;
    if (!$allow) {
        log_line('setup_redirected', ['users' => $count]);
        Response::redirect(href('/provider/login'));
        return;
    }
    if ($method === 'GET' || $method === 'HEAD') {
        log_line('setup_allowed', ['method' => $method]);
        if ($method === 'HEAD') {
            http_response_code(200);
            Response::applyNoStoreHeaders();
            header('Content-Type: text/html; charset=utf-8', true);
            return;
        }
        Response::html(View::render('Provider/Setup', ['csrf' => csrf_token()]));
        return;
    }
    if ($method === 'POST') {
        $token = $_POST['_csrf'] ?? '';
        if (!verify_csrf($token)) {
            Response::html(View::render('Provider/Setup', [
                'csrf' => csrf_token(),
                'error' => 'CSRFトークンが無効です。',
            ]), 422);
            return;
        }
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        if ($email === '' || $password === '') {
            Response::html(View::render('Provider/Setup', [
                'csrf' => csrf_token(),
                'error' => 'メールアドレスとパスワードは必須です。',
            ]), 422);
            return;
        }
        try {
            DB::transaction(static function (PDO $pdo) use ($email, $password): void {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, role, force_reset, created_at) VALUES (?, ?, "provider", 1, NOW())');
                $stmt->execute([$email, $hash]);
            });
            log_line('setup_created', ['email' => $email]);
        } catch (Throwable $e) {
            log_line('setup_failed', ['email' => $email, 'error' => $e->getMessage()], 'error');
            Response::html(View::render('Provider/Setup', [
                'csrf' => csrf_token(),
                'error' => 'ユーザー作成に失敗しました。',
            ]), 500);
            return;
        }
        Response::redirect(href('/provider/login'));
        return;
    }
    Response::json(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

function renderProviderDashboard(): void
{
    $user = Auth::user();
    Response::html(View::render('Provider/Dashboard', ['user' => $user]));
}

function renderProviderTenants(): void
{
    $pdo = DB::pdo();
    $tenants = $pdo->query('SELECT id, name, created_at FROM tenants ORDER BY created_at DESC')->fetchAll();
    Response::html(View::render('Provider/Tenants', [
        'tenants' => $tenants,
    ]));
}

function handleProviderTenantsNew(string $method): void
{
    if ($method === 'GET') {
        Response::html(View::render('Provider/TenantsNew', ['csrf' => csrf_token()]));
        return;
    }
    if ($method === 'POST') {
        $token = $_POST['_csrf'] ?? '';
        if (!verify_csrf($token)) {
            Response::html(View::render('Provider/TenantsNew', [
                'csrf' => csrf_token(),
                'error' => 'CSRFトークンが無効です。',
            ]), 422);
            return;
        }
        $name = trim((string) ($_POST['tenant_name'] ?? ''));
        $adminEmail = trim((string) ($_POST['admin_email'] ?? ''));
        $adminPassword = (string) ($_POST['admin_password'] ?? '');
        if ($name === '' || $adminEmail === '' || $adminPassword === '') {
            Response::html(View::render('Provider/TenantsNew', [
                'csrf' => csrf_token(),
                'error' => 'すべての項目を入力してください。',
            ]), 422);
            return;
        }
        log_line('tenants_create_start', ['name' => $name, 'email' => $adminEmail]);
        try {
            DB::transaction(static function (PDO $pdo) use ($name, $adminEmail, $adminPassword): void {
                $stmt = $pdo->prepare('INSERT INTO tenants (name, created_at) VALUES (?, NOW())');
                $stmt->execute([$name]);
                $tenantId = (int) $pdo->lastInsertId();
                $hash = password_hash($adminPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (tenant_id, email, password_hash, role, force_reset, created_at) VALUES (?, ?, ?, "admin", 1, NOW())');
                $stmt->execute([$tenantId, $adminEmail, $hash]);
                log_line('tenant_admin_created', ['tenant_id' => $tenantId, 'email' => $adminEmail]);
            });
            log_line('tenants_create_ok', ['name' => $name]);
        } catch (Throwable $e) {
            log_line('tenants_create_failed', ['name' => $name, 'error' => $e->getMessage()], 'error');
            Response::html(View::render('Provider/TenantsNew', [
                'csrf' => csrf_token(),
                'error' => '作成に失敗しました。',
            ]), 500);
            return;
        }
        Response::redirect(href('/provider/tenants'));
        return;
    }
    Response::json(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

function handleProviderDb(string $method): void
{
    if (!in_array($method, ['GET'], true)) {
        Response::json(['ok' => false, 'error' => 'method_not_allowed'], 405);
        return;
    }

    $pdo = DB::pdo();
    $counts = [
        'provider_rules' => (int) $pdo->query('SELECT COUNT(*) FROM provider_rules')->fetchColumn(),
        'departments' => (int) $pdo->query('SELECT COUNT(*) FROM departments')->fetchColumn(),
    ];

    Response::json([
        'ok' => true,
        'applied' => false,
        'counts' => $counts,
    ]);
}

function handleAdminClerkNew(string $method): void
{
    if ($method === 'GET') {
        Response::html(View::render('Admin/ClerkNew', ['csrf' => csrf_token()]));
        return;
    }
    if ($method === 'POST') {
        $token = $_POST['_csrf'] ?? '';
        if (!verify_csrf($token)) {
            Response::html(View::render('Admin/ClerkNew', [
                'csrf' => csrf_token(),
                'error' => 'CSRFトークンが無効です。',
            ]), 422);
            return;
        }
        $email = trim((string) ($_POST['clerk_email'] ?? ''));
        $password = (string) ($_POST['clerk_password'] ?? '');
        $user = Auth::user();
        if ($email === '' || $password === '' || !$user) {
            Response::html(View::render('Admin/ClerkNew', [
                'csrf' => csrf_token(),
                'error' => '入力に不備があります。',
            ]), 422);
            return;
        }
        try {
            DB::transaction(static function (PDO $pdo) use ($user, $email, $password): void {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (tenant_id, email, password_hash, role, force_reset, created_at) VALUES (?, ?, ?, "clerk", 1, NOW())');
                $stmt->execute([$user['tenant_id'], $email, $hash]);
                log_line('tenant_clerk_created', ['tenant_id' => $user['tenant_id'], 'email' => $email]);
            });
        } catch (Throwable $e) {
            log_line('tenant_clerk_failed', ['email' => $email, 'error' => $e->getMessage()], 'error');
            Response::html(View::render('Admin/ClerkNew', [
                'csrf' => csrf_token(),
                'error' => '作成に失敗しました。',
            ]), 500);
            return;
        }
        Response::redirect(href('/provider/dashboard'));
        return;
    }
    Response::json(['ok' => false, 'error' => 'method_not_allowed'], 405);
}
