<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Support\Auth;
use App\Support\Request;
use App\Support\Response;
use function App\Support\render_layout;

final class AdminDashboardController
{
    public static function show(Request $request): Response
    {
        Auth::requireLogin(Auth::REALM_GENERAL);
        $user = Auth::user();
        $email = htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8');
        $html = render_layout('管理ダッシュボード', <<<HTML
            <main>
                <h1>管理ダッシュボード</h1>
                <p>ログイン中: {$email}</p>
                <nav>
                    <ul>
                        <li><a href="/admin/users">ユーザー一覧</a></li>
                        <li><a href="/admin/clerk/new">事務員の登録</a></li>
                    </ul>
                </nav>
            </main>
            HTML);
        return Response::html($html);
    }
}
