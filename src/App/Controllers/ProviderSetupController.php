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

final class ProviderSetupController
{
    public static function show(Request $request): Response
    {
        $count = self::userCount();
        if ($count > 0) {
            log_line('setup_redirected', ['count' => $count]);
            throw new HttpException(302, 'Redirect', '/provider/login');
        }
        log_line('setup_allowed', []);
        $html = render_layout('Provider Setup', <<<HTML
            <main>
                <h1>Provider Setup</h1>
                <p>初回のみセットアップを実行できます。</p>
                <form method="post" action="/provider/setup">
                    {{csrf}}
                    <label>メールアドレス<input type="email" name="email" required></label>
                    <label>パスワード<input type="password" name="password" minlength="8" required></label>
                    <button type="submit">ユーザー作成</button>
                </form>
            </main>
            HTML, ['csrf' => true]);
        return Response::html($html);
    }

    public static function store(Request $request): Response
    {
        if (!CSRF::verify($_POST['csrf_token'] ?? null)) {
            throw new HttpException(400, 'CSRF token mismatch');
        }
        $count = self::userCount();
        if ($count > 0) {
            log_line('setup_redirected', ['count' => $count]);
            throw new HttpException(302, 'Redirect', '/provider/login');
        }
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            return Response::redirect('/provider/setup');
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            DB::transaction(static function () use ($email, $hash): void {
                DB::execute(
                    'INSERT INTO users (tenant_id, email, password_hash, role, force_reset) VALUES (NULL, :email, :password_hash, :role, :force_reset)',
                    [
                        'email' => $email,
                        'password_hash' => $hash,
                        'role' => 'provider',
                        'force_reset' => 1,
                    ]
                );
            });
            log_line('setup_created', ['email' => $email]);
        } catch (Throwable $throwable) {
            log_exception($throwable, 'setup_failed');
            return Response::redirect('/provider/setup');
        }
        return Response::redirect('/provider/login');
    }

    private static function userCount(): int
    {
        $rows = DB::select('SELECT COUNT(*) AS count FROM users');
        return (int) ($rows[0]['count'] ?? 0);
    }
}
