<?php
declare(strict_types=1);
/** @var string $csrf */
/** @var string|null $error */
ob_start();
?>
<h1>初期ユーザーの作成</h1>
<p>この画面は初回セットアップ時のみ公開されます。フォーム送信後はログインページへリダイレクトします。</p>
<?php if (!empty($error)): ?>
    <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<form method="post" action="<?= href('/provider/setup') ?>">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <label for="email">メールアドレス</label>
    <input type="email" id="email" name="email" required>
    <label for="password">パスワード</label>
    <input type="password" id="password" name="password" required minlength="8">
    <button type="submit">ユーザーを作成</button>
</form>
<?php
$body = ob_get_clean();
include __DIR__ . '/../Components/Layout.php';
