# features / logs（L2：1行JSON）

- 代表イベント：
  - `db_connect_failed`, `schema_bootstrap_ok/failed`, `seed_upsert_fallback`
  - `rules_packs_apply_start/ok/failed`, `departments_seed_apply_start/ok/failed`
  - `auth_login/logout/guard_blocked`
  - `setup_allowed/setup_created/setup_redirected`
  - `tenants_create_start/ok/failed`, `tenant_admin_created`, `tenant_clerk_created`
- フォーマット：`{timestamp, level, event, file, line, ...}` を**1行JSON**で。
