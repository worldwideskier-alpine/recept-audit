# features / data_model（DDL規範・UPSERT・固定行）

## 列・制約（抜粋 / MUST）
- `users` に `tenant_id INT NULL`（FK: tenants.id）。Provider は NULL、Admin/Clerk は作成テナントの ID を必須設定。
- `users.email` は **全体ユニーク**。`users.username` は **廃止**（参照禁止）。
- `provider_rules` は `rule_condition` / `rule_action`（入力JSONの `condition` / `action` をマッピング）。
- `sync_commands` に `ix_sc_status_requested(status, requested_at, id)`（IF NOT EXISTS 禁止→存在確認後に作成）。
- **固定行**：`rules_sync_state(id=1)` / `sync_runner_state(id=1)` を保証。

## UPSERT 方針（MUST）
- **採用**：`INSERT ... VALUES (...) AS new ON DUPLICATE KEY UPDATE col = new.col`
- **禁止**：`... UPDATE col = VALUES(col)`（`/* FORBIDDEN: VALUES() */`）
- 適用：`provider_rules`, `departments`, `users`（/provider/setup 作成時）ほか全UPSERT。

## 予約語回避
- DB の識別子（テーブル/列/インデックス/制約名）に予約語を使用しない。入力データのキー名が予約語でも DB 列名へ転用しない。

## 代表テーブル（規範）
- `tenants`, `users`, `departments`, `provider_rules`, `patients`, `claims`, `claim_items`, `audit_rules`, `tenant_rule_overrides`, `sync_commands`, `rules_sync_state`, `sync_runner_state`, `import_runs`, `job_runs`。
