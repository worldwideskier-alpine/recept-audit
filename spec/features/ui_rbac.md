# features / ui_rbac（UI と RBAC の要件）

## Provider Dashboard（最小）
- `/provider/dashboard`：ログイン後ランディング。見出し「Provider Dashboard」＋クイックリンク（Tenants, ルール適用 `/provider/db`, グローバルルール `/provider/rules`, ジョブ, ログアウト）。
- **機能が 0 件でも 200**。

## Provider Global Rules
- `/provider/rules`：一覧/検索/詳細を提供（編集は MUST）。表示：title, version, source_date, created_at、詳細は `rule_condition` / `rule_action`。

## Tenants（新規）
- **GET `/provider/tenants/new`**：200（no-store）。フォーム：`tenant_name`, `admin_email`, `admin_password`, `csrf_token`。
- **POST `/provider/tenants/new`**：CSRF 必須。**1 トランザクション**で `tenants` 作成 → `users(admin)` 作成（`tenant_id` 紐付け、`force_reset=1`）。成功は **201** または **302→/provider/tenants**、失敗は **422/400**（部分作成禁止）。

## Admin Clerk 新規作成
- **GET `/admin/clerk/new`**：200（no-store）。フォーム：`clerk_email`, `clerk_password`, `csrf_token`。
- **POST `/admin/clerk/new`**：`users(role='clerk', tenant_id=<adminのtenant_id>, force_reset=1)` を作成。成功は **201** または **302→/admin/users`、失敗は **422/400**。
