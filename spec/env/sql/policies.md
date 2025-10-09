# sql / policies（MySQL 8.0.x 方言ロック）

- **DDL: `CREATE INDEX IF NOT EXISTS` を禁止。**
  - 実装: `information_schema.STATISTICS` による存在確認 → `CREATE INDEX` 実行の二段階で確保。
- **Upsert: `INSERT … ON DUPLICATE KEY UPDATE` を使用し、`VALUES()` 参照は禁止。**
- **文字コード/照合: `utf8mb4` / `utf8mb4_0900_ai_ci`。不可時 `utf8mb4_unicode_ci` にフォールバックし、L2ログへ `charset_fallback` を出力。**
