<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\HttpException;
use App\Support\Auth;
use App\Support\CSRF;
use App\Support\DB;
use App\Support\Request;
use App\Support\Response;
use Throwable;
use function App\Support\log_exception;
use function App\Support\log_line;
use function App\Support\render_layout;

final class ProviderTenantsController
{
    public static function index(Request $request): Response
    {
        Auth::requireLogin(Auth::REALM_PROVIDER);
        $tenants = DB::select('SELECT id, name, created_at FROM tenants ORDER BY id DESC');
        $rows = '';
        foreach ($tenants as $tenant) {
            $rows .= '<tr><td>' . htmlspecialchars((string) $tenant['id']) . '</td><td>' . htmlspecialchars($tenant['name']) . '</td><td>' . htmlspecialchars((string) $tenant['created_at']) . '</td></tr>';
        }
        if ($rows === '') {
            $rows = '<tr><td colspan="3">まだ医療機関は登録されていません。</td></tr>';
        }
        $html = render_layout('医療機関一覧', <<<HTML
            <main>
                <h1>医療機関一覧</h1>
                <p><a href="/provider/tenants/new">新規登録</a></p>
                <table>
                    <thead><tr><th>ID</th><th>名称</th><th>登録日</th></tr></thead>
                    <tbody>{$rows}</tbody>
                </table>
            </main>
            HTML);
        return Response::html($html);
    }

    public static function create(Request $request): Response
    {
        Auth::requireLogin(Auth::REALM_PROVIDER);
        $html = render_layout('医療機関の登録', <<<HTML
            <main>
                <h1>医療機関の登録</h1>
                <form method="post" action="/provider/tenants/new">
                    {{csrf}}
                    <label>医療機関名称<input type="text" name="tenant_name" maxlength="128" required></label>
                    <label>管理者メールアドレス<input type="email" name="admin_email" required></label>
                    <label>管理者パスワード<input type="password" name="admin_password" minlength="8" required></label>
                    <button type="submit">登録する</button>
                </form>
            </main>
            HTML, ['csrf' => true]);
        return Response::html($html);
    }

    public static function store(Request $request): Response
    {
        Auth::requireLogin(Auth::REALM_PROVIDER);
        if (!CSRF::verify($_POST['csrf_token'] ?? null)) {
            throw new HttpException(400, 'CSRF token mismatch');
        }
        $tenantName = trim($_POST['tenant_name'] ?? '');
        $email = trim($_POST['admin_email'] ?? '');
        $password = $_POST['admin_password'] ?? '';
        if ($tenantName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            return Response::redirect('/provider/tenants/new');
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        log_line('tenants_create_start', ['tenant_name' => $tenantName, 'email' => $email]);
        try {
            DB::transaction(static function () use ($tenantName, $email, $hash): void {
                DB::execute('INSERT INTO tenants (name) VALUES (:name)', ['name' => $tenantName]);
                $tenantId = (int) DB::pdo()->lastInsertId();
                DB::execute(
                    'INSERT INTO users (tenant_id, email, password_hash, role, force_reset) VALUES (:tenant_id, :email, :password_hash, :role, :force_reset)',
                    [
                        'tenant_id' => $tenantId,
                        'email' => $email,
                        'password_hash' => $hash,
                        'role' => 'admin',
                        'force_reset' => 1,
                    ]
                );
                log_line('tenant_admin_created', ['tenant_id' => $tenantId, 'email' => $email]);
            });
            log_line('tenants_create_ok', ['tenant_name' => $tenantName]);
        } catch (Throwable $throwable) {
            log_exception($throwable, 'tenants_create_failed');
            return Response::redirect('/provider/tenants/new');
        }
        return Response::redirect('/provider/tenants');
    }
}
