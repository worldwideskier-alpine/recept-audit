# cron / runner（例：単一cron + EmptyDB Auto-Fetch）

# Asia/Tokyo（10分間隔）の例：
*/10 * * * * cd {BASE} && php cli/sync.php --cycle >> storage/logs/cron.sync.log 2>&1

## --cycle の疑似フロー（要約）
- 排他取得 → 空判定（コア表の未存在、provider_rules=0 など）
- 空なら --force で取得 → 変化があれば build_rules → import_db
- 新着 0 が3サイクル継続で指数バックオフ（最大6h）
