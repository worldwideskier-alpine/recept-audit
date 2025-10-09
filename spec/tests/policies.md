# tests / policies（検査規約・共通）

## 1. 原則（MUST）
- **言語非依存**：まず合格条件を言語非依存で定義し、実行は言語アダプタに委譲。
- **静粛合格**：すべて合格のとき標準出力は空。失敗時のみ `NG:` 行を出し非0終了。
- **再現可能**：`export LC_ALL=C LANG=C` の固定、`set -Eeuo pipefail`（bash）。
- **証跡**：`evidence/static`, `evidence/runtime`, `evidence/pkg`, `evidence/verify` を常備し、OK/ERRをファイルで残す。
- **禁止**：`/dev/fd/*` やプロセス置換 `<( )`、Here-String `<<<` 等の環境依存I/O。

## 2. 基本ゲート（抜粋）
- **POLYGLOT-LINT（H-20G）**：言語検出に基づく実リンタ/構文検証を実施し、`polyglot_lint_ok=true`。
- **HTML-LS-GLOBAL（H-23G）**：出力HTMLのベースライン（`<!doctype html>`, `<meta charset="utf-8">`, `<html lang="..">`）。
- **HEALTH-BOOT-HEAD（H-21H）**：`/health` HEAD が `200` + `Cache-Control: no-store` + `Content-Type: application/json`。
- **VERIFY-REPORT**：`evidence/**` と `COMPLIANCE.json` を集約して `evidence/verify/VERIFY_REPORT.json` を生成。

## 3. ランナー要件（run_checks.sh）
- 先頭で厳格モードとロケール固定。合格時は無出力で0終了。
- 代表手順：polyglot_lint → render_smoke(HTML-LS) → verify_report。
- すべてのログ/OKファイルは `evidence/**` 配下に保存。

## 4. パッケージング連携（pack.sh など）
- ZIP 生成は **すべての必須ゲートが PASS**、かつ `COMPLIANCE.json` の必須キーが true のときのみ許可。
- 手詰めZIPや `tools/` 非経由の証跡生成は禁止（pack provenance をロック）。
