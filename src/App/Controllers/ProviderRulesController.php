<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Support\Auth;
use App\Support\DB;
use App\Support\Request;
use App\Support\Response;
use function App\Support\render_layout;

final class ProviderRulesController
{
    public static function index(Request $request): Response
    {
        Auth::requireLogin(Auth::REALM_PROVIDER);
        $rules = DB::select('SELECT id, title, version, source_date, created_at FROM provider_rules ORDER BY title');
        $rows = '';
        foreach ($rules as $rule) {
            $rows .= '<tr>'
                . '<td>' . htmlspecialchars($rule['title']) . '</td>'
                . '<td>' . htmlspecialchars((string) $rule['version']) . '</td>'
                . '<td>' . htmlspecialchars((string) $rule['source_date']) . '</td>'
                . '<td>' . htmlspecialchars((string) $rule['created_at']) . '</td>'
                . '</tr>';
        }
        if ($rows === '') {
            $rows = '<tr><td colspan="4">ルールがまだ登録されていません。</td></tr>';
        }
        $html = render_layout('ルール一覧', <<<HTML
            <main>
                <h1>ルール一覧</h1>
                <table>
                    <thead>
                        <tr><th>タイトル</th><th>バージョン</th><th>配布日</th><th>登録日</th></tr>
                    </thead>
                    <tbody>{$rows}</tbody>
                </table>
            </main>
            HTML);
        return Response::html($html);
    }
}
