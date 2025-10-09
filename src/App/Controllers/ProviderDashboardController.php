<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Support\Auth;
use App\Support\Request;
use App\Support\Response;
use function App\Support\render_layout;

final class ProviderDashboardController
{
    public static function show(Request $request): Response
    {
        Auth::requireLogin(Auth::REALM_PROVIDER);
        $html = render_layout('Provider Dashboard', <<<HTML
            <main>
                <h1>Provider Dashboard</h1>
                <nav>
                    <ul>
                        <li><a href="/provider/tenants">医療機関一覧</a></li>
                        <li><a href="/provider/tenants/new">医療機関の登録</a></li>
                        <li><a href="/provider/rules">ルール一覧</a></li>
                        <li><span>/provider/setup はセットアップ完了後は非公開です。</span></li>
                        <li><a href="/provider/jobs">ジョブ状況</a></li>
                    </ul>
                </nav>
            </main>
            HTML);
        return Response::html($html);
    }
}
