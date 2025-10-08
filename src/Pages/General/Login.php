<?php
declare(strict_types=1);
/** @var string $csrf */
/** @var string|null $error */
ob_start();
?>
<nav>
    <a href="<?= href('/provider/login') ?>">プロバイダー向けログイン</a>
</nav>
<h1>一般ログイン</h1>
<?php if (!empty($error)): ?>
    <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<form method="post" action="<?= href('/login') ?>">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <label for="email">メールアドレス</label>
    <input type="email" id="email" name="email" required>
    <label for="password">パスワード</label>
    <input type="password" id="password" name="password" required>
    <button type="submit">ログイン</button>
</form>
<?php
$body = ob_get_clean();
include __DIR__ . '/../Components/Layout.php';
