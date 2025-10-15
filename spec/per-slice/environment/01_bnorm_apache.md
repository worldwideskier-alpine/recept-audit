# 環境仕様（個別）: BNORM + Apache

- `.htaccess` は **ディレクトリ相対で `app.php` に集約**（BNORM）
- `Options -MultiViews -Indexes` を明示で無効化
- `RewriteBase` の常時固定は禁止
- `/env-lite` `/health-lite` は **物理ファイル直返し**（-f 素通し）
- プロファイル（`env/profiles/*.yml`）は slice とは独立管理。slice 側は**読取りのみ**。
