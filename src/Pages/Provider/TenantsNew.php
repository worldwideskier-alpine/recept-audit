<?php
declare(strict_types=1);
/** @var string $csrf */
/** @var string|null $error */
ob_start();
?>
<nav>
    <a href="<?= href('/provider/dashboard') ?>">ダッシュボード</a>
    <a href="<?= href('/provider/tenants') ?>">テナント一覧</a>
    <a href="<?= href('/logout') ?>">ログアウト</a>
</nav>
<h1>テナント作成</h1>
<?php if (!empty($error)): ?>
    <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<form method="post" action="<?= href('/provider/tenants/new') ?>">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <label for="tenant_name">医療機関名</label>
    <input type="text" id="tenant_name" name="tenant_name" required maxlength="128">
    <label for="admin_email">管理者メールアドレス</label>
    <input type="email" id="admin_email" name="admin_email" required>
    <label for="admin_password">管理者パスワード</label>
    <input type="password" id="admin_password" name="admin_password" required minlength="8">
    <button type="submit">テナントを作成</button>
</form>
<?php
$body = ob_get_clean();
include __DIR__ . '/../Components/Layout.php';
