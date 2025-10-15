# 環境仕様（個別）: Runtime / Profiles

- PHP 8.3（CLI/CGI 同一前提）
- HTTP クライアントは **cURL のみ**（`file_get_contents` でのHTTPは禁止）
- MySQL 8.0 / utf8mb4、照合優先 `utf8mb4_0900_ai_ci`（不可時 unicode_ci に自動フォールバックして L2 ログ）
- ログは **1行 JSON（L2）** を基本
