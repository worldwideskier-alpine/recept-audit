# features / cron（単一cron + EmptyDB Auto-Fetch）

- 例（Asia/Tokyo）: `*/10 * * * * cd {BASE} && php cli/sync.php --cycle >> storage/logs/cron.sync.log 2>&1`
- 空判定：コア表未存在／`provider_rules=0` または ENV 最小値未満（min 25）／`departments<35`。
- 空なら **force 取得 → build_rules → import（/provider/db 同等：ルール＋診療科）** を行う。
