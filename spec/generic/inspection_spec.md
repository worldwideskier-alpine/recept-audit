# 検査仕様（Generic / Inspection Spec）

## 目的
- 受入の **一貫性**と **自動化**。slice ごとの合否判定を定義。

## 受入観点（抜粋）
- **HTTP 応答規範**：全応答に `Cache-Control: no-store`、301 禁止、302 は許容（/→/login 等）。
- **BNORM 準拠**：`/env` と `/env/` の両方が 200。Rewrite は `-f/-d` を優先素通し。
- **/health**：最小ブートのみ（スキーマ自己確保）。seed/import 禁止。成功/失敗は L2 ログ（1行JSON）。
- **DB 方言ロック**：MySQL 8.0/utf8mb4、照合優先 `utf8mb4_0900_ai_ci`（不可時 unicode_ci へフォールバック記録）。
- **AUTH-REALM-SPLIT**：`/provider` 系は `/provider/login` へ 302、`/provider/setup` は初回のみ公開。

## エビデンス/テスト（例）
- `evidence/static/HTML_LS_GLOBAL_OK.txt`（静的構成の妥当性）
- `evidence/runtime/HEALTH_BOOT_HEAD_OK.txt`（/health の最小ブート検査）
- `storage/logs/app.log`（L2 1行JSON ログ）

## フェーズ
- **phase_s**：静的/構成チェック（.htaccess, FC, no-store 等）
- **phase_b**：ブート/機能検査（/health 〜 CLI/imports 〜 UI/RBAC）
