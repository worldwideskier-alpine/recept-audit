<?php
declare(strict_types=1);
/** @var array $tenants */
ob_start();
?>
<nav>
    <a href="<?= href('/provider/dashboard') ?>">ダッシュボード</a>
    <a href="<?= href('/provider/tenants/new') ?>">テナント作成</a>
    <a href="<?= href('/provider/db') ?>">ルール適用状況</a>
    <a href="<?= href('/logout') ?>">ログアウト</a>
</nav>
<h1>テナント一覧</h1>
<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>名称</th>
        <th>作成日時</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($tenants as $tenant): ?>
        <tr>
            <td><?= htmlspecialchars((string) $tenant['id'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($tenant['name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($tenant['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($tenants)): ?>
        <tr><td colspan="3">テナントはまだありません。</td></tr>
    <?php endif; ?>
    </tbody>
</table>
<?php
$body = ob_get_clean();
include __DIR__ . '/../Components/Layout.php';
