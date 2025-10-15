# spec / env（環境プロファイル：プラットフォーム非依存）

本ディレクトリは **実行・配備環境の実値と方言ロック**を記述します。生成（foundation）や検査（tests）とは
役割を分離し、**ここを唯一の実値SOT（Single Source of Truth）**とします。実値（パスワード等）は
Git に直接書かず、`secrets/` のサンプルと CI/CD のシークレット管理を用います。

- `policies.md` …… 主要方針（Apache/BNORM、no-store、DB方言、HTTPクライアント等）
- `profiles/*.yml` …… 環境値のスキーマ化されたプロファイル（base/dev/prod）
- `apache/htaccess.bnorm.conf` …… ディレクトリ相対BNORMの正本（サブディレクトリ安全）
- `sql/policies.md` …… MySQL 8.0 方言ロック（DDL/Upsert/照合フォールバック等）
- `cron/runner.md` …… 定期実行ランナの要件と例
- `secrets/.env.example` …… 実値の置き場（**サンプルのみ**。実値はCI等で注入）

> 本構成は、アップロードいただいた環境仕様（v1.0.6）の要求を抽象化し、値はプレースホルダ化しています。
> 実値は CI のシークレットやサーバの `.env` で注入してください。
