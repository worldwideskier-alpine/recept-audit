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

final class AdminClerkController
{
    public static function create(Request $request): Response
    {
        Auth::requireRoles(['admin']);
        $html = render_layout('事務員の登録', <<<HTML
            <main>
                <h1>事務員の登録</h1>
                <form method="post" action="/admin/clerk/new">
                    {{csrf}}
                    <label>メールアドレス<input type="email" name="clerk_email" required></label>
                    <label>パスワード<input type="password" name="clerk_password" minlength="8" required></label>
                    <button type="submit">登録</button>
                </form>
            </main>
            HTML, ['csrf' => true]);
        return Response::html($html);
    }

    public static function store(Request $request): Response
    {
        Auth::requireRoles(['admin']);
        if (!CSRF::verify($_POST['csrf_token'] ?? null)) {
            throw new HttpException(400, 'CSRF token mismatch');
        }
        $email = trim($_POST['clerk_email'] ?? '');
        $password = $_POST['clerk_password'] ?? '';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            return Response::redirect('/admin/clerk/new');
        }
        $admin = Auth::user();
        $tenantId = (int) ($admin['tenant_id'] ?? 0);
        if ($tenantId <= 0) {
            log_line('tenant_clerk_failed', ['reason' => 'tenant_missing']);
            return Response::redirect('/admin/clerk/new');
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            DB::execute(
                'INSERT INTO users (tenant_id, email, password_hash, role, force_reset) VALUES (:tenant_id, :email, :password_hash, :role, :force_reset)',
                [
                    'tenant_id' => $tenantId,
                    'email' => $email,
                    'password_hash' => $hash,
                    'role' => 'clerk',
                    'force_reset' => 1,
                ]
            );
            log_line('tenant_clerk_created', ['tenant_id' => $tenantId, 'email' => $email]);
        } catch (Throwable $throwable) {
            log_exception($throwable, 'tenant_clerk_failed');
            return Response::redirect('/admin/clerk/new');
        }
        return Response::redirect('/admin/dashboard');
    }
}
