# foundation / contracts（生成I/O契約：プラットフォーム非依存）

> 生成の**入出力の取り決め**を定義します。（対話UIに非依存） での応答文言や「CIアーティファクトのURL」は扱いません。

## 1. 出力ディレクトリとファイル（MUST）
- 生成物：ソース一式（`src/**`, `public/**`, `app.php` など）。
- 証跡：`evidence/**`（`static/`, `runtime/`, `pkg/` 等のカテゴリを持つ）。
- 準拠情報：`COMPLIANCE.json`（最低限下記キーを含む）。
- 成果物：`dist/package.zip`（`tools/pack.*` 経由で作成）。

### 1.1 COMPLIANCE.json（例）
```json
{
  "full_only_acceptance": true,
  "pack_provenance_ok": true,
  "zip_ready_ok": true,
  "polyglot_lint_ok": true,
  "html_ls_global_ok": true,
  "err_guard_ok": true,
  "verify_report_ok": true
}
```

### 1.2 MANIFEST.json（例：抜粋）
```json
{
  "files": [
    "app.php",
    "env-lite.php",
    "health-lite.php",
    "src/App/Controllers/HealthController.php"
  ]
}
```

## 2. 検査との接続
- 生成直後に `tools/run_checks.*` を実行し、**Exit Code** と **証跡ファイル**で合否を返す。
- 不合格時は AHR を起動し、修復ののち再検査。最終的に合格しない場合は **CI を失敗**で終了する。

## 3. 成果物の公開
- `dist/*.zip` を **CI/CD のアーティファクト**として公開する（もしくはパッケージレジストリ／オブジェクトストレージへアップロード）。
- 公開先 URL は CI が発行・掲示する（人手での “リンク提示” は仕様外）。

## 4. ロギング
- 主要処理の節目で `evidence/static/*.txt` に OK/NG のシグナルを出力し、CI ログにも同一の行を記録する。
- 例：`EVIDENCE:POLYGLOT_LINT_OK` / `EVIDENCE:HTML_LS_BASE_OK` / `EVIDENCE:ERR_GUARD_INJECTED`。
