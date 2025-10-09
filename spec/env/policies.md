# env / policies（方針・拘束事項）

## 1) ランタイム前提（MUST）
- PHP 8.3（CLI/CGI同一想定）。フレームワーク前提なし。PSR 準拠は任意。
- Web: **Apache + mod_rewrite**。**AllowOverride FileInfo 以上**を付与。
- HTTP クライアントは **cURL** を使用（`file_get_contents` 等のHTTP用途は不可）。

## 2) Apache / BNORM（MUST）
- `.htaccess` は **ディレクトリ相対で `app.php` へ集約**（BNORM）。**`RewriteBase /` の常時固定は不可**。
- **`Options -MultiViews -Indexes` を必須**（内容ネゴシエーションを無効化）。
- 正本は `apache/htaccess.bnorm.conf` を参照し、**そのまま配置**する。

## 3) HTTP キャッシュ（MUST）
- すべての応答に **`Cache-Control: no-store`** を付与（3xx を含む。301 は運用上禁止）。

## 4) DB 方言ロック（MUST）
- エンジン: **MySQL 8.0.x**（MariaDB 非対象）。
- 文字コード: **utf8mb4**。照合: **utf8mb4_0900_ai_ci** を第一候補。不可時 **utf8mb4_unicode_ci** へ自動フォールバックし、**L2ログ**を記録。

## 5) /health（最小ブート）
- 役割: **スキーマ自己確保のみ**を行う最小ブート。データ投入（seed/import）は禁止。

## 6) ログ
- `/health` の成功/失敗を **1行JSON(L2)** で `storage/logs/app.log` に記録（`health_min_boot_pass|fail`）。

## 7) コード実装スタイル・最低拘束
- DB アクセスは **`App\Support\DB` の静的ファサード**を提供（`pdo()` / `q()` / `ensure_index()`）。
- Support/** は **先頭〜80行以内**で自前依存を `require_once` し、読み込み順に依存しない。
- リダイレクトは **常に BNORM 経由**（`href()` 等）。`header('Location: /...')` の直書きは禁止。
