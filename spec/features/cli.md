# features / cli（CLI 要件）

- `cli/sync.php`：cURL ベースの実ネット取得。`--cycle` は EmptyDB Auto-Fetch を実装。`SYNC_SEED_URLS` 未設定は exit=3。
- `cli/build_rules.php`：テンプレ到達性検査。`--strict` 到達0件は exit=2。
- `cli/import_db.php`：/provider/db と等価の取込。診療科 upsert も実施。
