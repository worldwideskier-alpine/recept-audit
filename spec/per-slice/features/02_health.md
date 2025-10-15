# 機能仕様（個別）: /health（最小ブート）

- **スキーマ自己確保のみ**を行う最小ブート。
- seed/import は **禁止**。
- 成否を `storage/logs/app.log` に **1行JSON(L2)** で記録（`health_min_boot_pass|fail`）。
