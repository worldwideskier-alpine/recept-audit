<?php
declare(strict_types=1);
/** @var string $csrf */
/** @var string|null $error */
ob_start();
?>
<h1>Provider Login</h1>
<p>プロバイダーの方はここからサインインしてください。</p>
<?php if (!empty($error)): ?>
    <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<form method="post" action="<?= href('/provider/login') ?>">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <label for="email">メールアドレス</label>
    <input type="email" id="email" name="email" required>
    <label for="password">パスワード</label>
    <input type="password" id="password" name="password" required>
    <button type="submit">ログイン</button>
</form>
<p>初めてご利用の際は /provider/setup 経由で初期ユーザーを作成してください。</p>
<?php
$body = ob_get_clean();
include __DIR__ . '/../Components/Layout.php';
