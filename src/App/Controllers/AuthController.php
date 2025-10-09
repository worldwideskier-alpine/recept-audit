<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\HttpException;
use App\Support\Auth;
use App\Support\CSRF;
use App\Support\Request;
use App\Support\Response;
use function App\Support\render_layout;

final class AuthController
{
    public static function redirectToLogin(Request $request): Response
    {
        return Response::redirect('/login');
    }

    public static function showLogin(Request $request): Response
    {
        $html = render_layout('一般ログイン', <<<HTML
            <main>
                <h1>一般ログイン</h1>
                <form method="post" action="/login">
                    {{csrf}}
                    <label>メールアドレス<input type="email" name="email" required></label>
                    <label>パスワード<input type="password" name="password" required></label>
                    <button type="submit">ログイン</button>
                </form>
            </main>
            HTML, ['csrf' => true]);
        return Response::html($html);
    }

    public static function handleLogin(Request $request): Response
    {
        if (!CSRF::verify($_POST['csrf_token'] ?? null)) {
            throw new HttpException(400, 'CSRF token mismatch');
        }
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($email === '' || $password === '') {
            return Response::redirect('/login');
        }
        if (!Auth::attempt($email, $password, Auth::REALM_GENERAL)) {
            return Response::redirect('/login');
        }
        return Response::redirect('/admin/dashboard');
    }

    public static function showProviderLogin(Request $request): Response
    {
        $html = render_layout('Provider Login', <<<HTML
            <main>
                <h1>Provider Login</h1>
                <p>初回セットアップが完了していない場合は担当者にお問い合わせください。</p>
                <form method="post" action="/provider/login">
                    {{csrf}}
                    <label>メールアドレス<input type="email" name="email" required></label>
                    <label>パスワード<input type="password" name="password" required></label>
                    <button type="submit">ログイン</button>
                </form>
            </main>
            HTML, ['csrf' => true]);
        return Response::html($html);
    }

    public static function handleProviderLogin(Request $request): Response
    {
        if (!CSRF::verify($_POST['csrf_token'] ?? null)) {
            throw new HttpException(400, 'CSRF token mismatch');
        }
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($email === '' || $password === '') {
            return Response::redirect('/provider/login');
        }
        if (!Auth::attempt($email, $password, Auth::REALM_PROVIDER)) {
            return Response::redirect('/provider/login');
        }
        return Response::redirect('/provider/dashboard');
    }
}
