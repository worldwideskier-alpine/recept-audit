<?php
declare(strict_types=1);
/** @var string $title */
/** @var string $body */
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; padding: 2rem; background: #f8fafc; }
        .container { max-width: 720px; margin: 0 auto; background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 16px rgba(15,23,42,0.1); }
        h1 { font-size: 1.8rem; margin-bottom: 1rem; }
        form { display: grid; gap: 1rem; }
        label { font-weight: 600; }
        input[type="text"], input[type="email"], input[type="password"] { padding: 0.6rem; border: 1px solid #cbd5f5; border-radius: 4px; font-size: 1rem; }
        button { padding: 0.8rem 1.2rem; background: #2563eb; color: #fff; border: none; border-radius: 4px; font-size: 1rem; cursor: pointer; }
        button:hover { background: #1d4ed8; }
        .error { color: #b91c1c; background: #fee2e2; padding: 0.5rem 0.75rem; border-radius: 4px; }
        nav { margin-bottom: 1.5rem; }
        nav a { margin-right: 1rem; text-decoration: none; color: #2563eb; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem; border-bottom: 1px solid #e2e8f0; text-align: left; }
    </style>
</head>
<body>
    <div class="container">
        <?= $body ?>
    </div>
</body>
</html>
