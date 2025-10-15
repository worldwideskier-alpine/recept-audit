# spec / features（機能仕様：プラットフォーム非依存）

本ディレクトリは **機能要件**（URL動作、RBAC、データモデル、取込、UI、ログ、E2E受入）を言語やFWに依存せずに定義します。
生成（foundation）・検査（tests）・環境（env）とは役割を分離し、ここでは**「何を実現するか」**のみを規範化します。

- `policies.md` …… 前提・BNORM・no-store・HTTP/DB・禁止事項（MUST）
- `endpoints.md` …… URL/早期ハンドラ/BNORM・各応答の規範
- `provider_setup.md` …… `/provider/setup` の厳格ポリシー（GET無副作用/POST作成のみ/二回目以降非公開）
- `data_model.md` …… DDL規範（列/固定行/インデックス/予約語回避/UPSERT方式）
- `schema.required.json` …… **Schema SOT（唯一の機械可読正本）**
- `env_matrix.json` …… 機能トグルと必須ENVキー（機械可読）
- `imports.md` …… `/provider/db` と CLI 相当の取込要件・件数下限・冪等規範
- `cli.md` …… `cli/sync.php` / `cli/build_rules.php` / `cli/import_db.php` の要件
- `cron.md` …… 単一cron + EmptyDB Auto-Fetch 方針
- `ui_rbac.md` …… 画面要件（Provider Dashboard/Rules、Tenants 新規、Admin Clerk 新規）とRBAC
- `logs.md` …… L2ログ（1行JSON）イベント規範
- `acceptance_e2e.md` …… 受入試験（H/A/B/C/D/E/F/G/I 等）

> 値（DB接続等）は `spec/env/**` を唯一のSOTとし、本書は**機能の振る舞い**のみを規範化します。
