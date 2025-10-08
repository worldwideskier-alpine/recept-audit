<?php
declare(strict_types=1);
/** @var string $csrf */
/** @var string|null $error */
ob_start();
?>
<nav>
    <a href="<?= href('/provider/dashboard') ?>">ダッシュボード</a>
    <a href="<?= href('/logout') ?>">ログアウト</a>
</nav>
<h1>事務員（clerk）作成</h1>
<?php if (!empty($error)): ?>
    <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<form method="post" action="<?= href('/admin/clerk/new') ?>">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <label for="clerk_email">メールアドレス</label>
    <input type="email" id="clerk_email" name="clerk_email" required>
    <label for="clerk_password">パスワード</label>
    <input type="password" id="clerk_password" name="clerk_password" required minlength="8">
    <button type="submit">作成</button>
</form>
<?php
$body = ob_get_clean();
include __DIR__ . '/../Components/Layout.php';
