# 機能仕様（個別）: /env（最上流・直返し）

- FC 最上流で JSON を直返し（no-store）。`/env/` も 200。
- このフェーズでは `require/include/autoload/session_start/ob_start` を **禁止**。
- 出力例: ランタイム/プロファイルの要点（機密値は伏せる）
