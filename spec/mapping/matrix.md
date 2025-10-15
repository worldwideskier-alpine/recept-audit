# タスク ⇔ SPEC ⇔ エビデンス 対応表

| Task ID | 目的 | 主な includes | 生成/更新ファイル | 受入エビデンス |
|---|---|---|---|---|
| 00_boot | BNORM/FC骨格 + env/health-lite | foundation/policies.md, env/policies.md, env/apache/htaccess.bnorm.conf | .htaccess, app.php, env-lite.php, health-lite.php, src/Support/* | HTML_LS_GLOBAL_OK.txt, TOOLS_OK.txt |
| 11_endpoints_env | /env（直返し） | features/endpoints.md, features/policies.md | src/App/Controllers/EnvController.php (+ app.php touch) | ENV_ENDPOINT_OK.txt |
| 12_endpoints_redirects | redirects（/→/login 等） | features/endpoints.md, features/policies.md | src/App/Controllers/RedirectsController.php (+ app.php touch) | REDIRECTS_OK.txt |
| 10_health | /health 最小ブート | env/sql/policies.md, features/schema.required.json | src/App/Controllers/HealthController.php, src/App/Support/Schema.php, schema.sql (+ app.php) | HEALTH_BOOT_HEAD_OK.txt, storage/logs/app.log |
| 20_provider_setup | 初回のみ公開 | features/provider_setup.md, features/policies.md | src/App/Controllers/ProviderSetupController.php (+ app.php) | PROVIDER_SETUP_OK.txt |
| 30_imports_cli | CLI 群 | features/cli.md, features/imports.md | cli/sync.php, cli/build_rules.php, cli/import_db.php | CLI_OK.txt |
| 40_provider_db | /provider/db | features/imports.md | src/App/Controllers/ProviderDbController.php (+ app.php) | IMPORT_DB_OK.txt |
| 50_ui_rbac | UI/RBAC | features/ui_rbac.md, features/policies.md, features/logs.md | controllers + templates/*.php | UI_RBAC_OK.txt |
