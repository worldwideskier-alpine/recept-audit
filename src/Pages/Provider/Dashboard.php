<?php
declare(strict_types=1);
/** @var array $user */
ob_start();
?>
<nav>
    <a href="<?= href('/provider/dashboard') ?>">ダッシュボード</a>
    <a href="<?= href('/provider/tenants') ?>">テナント一覧</a>
    <a href="<?= href('/provider/tenants/new') ?>">テナント作成</a>
    <a href="<?= href('/provider/db') ?>">ルール適用状況</a>
    <a href="<?= href('/logout') ?>">ログアウト</a>
</nav>
<h1>Provider Dashboard</h1>
<p>ようこそ、<?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?> さん。</p>
<ul>
    <li><a href="<?= href('/provider/tenants') ?>">テナント管理</a></li>
    <li><a href="<?= href('/provider/db') ?>">ルール適用</a></li>
    <li><a href="<?= href('/provider/login') ?>">ログイン画面</a>（リンクのみ、公開導線で setup へのリンクはありません）</li>
</ul>
<?php
$body = ob_get_clean();
include __DIR__ . '/../Components/Layout.php';
