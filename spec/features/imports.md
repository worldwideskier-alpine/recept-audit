[GEN-TARGET]
id: provider-db
outputs: [src/App/Controllers/ProviderDbController.php]
acceptance: [evidence/runtime/IMPORT_DB_OK.txt]
[/GEN-TARGET]

# features / imports（/provider/db と CLI の要件）

## /provider/db（唯一のアプリ内取込エンドポイント）
- 役割：**(1) ルール適用** と **(2) 診療科の冪等投入** を**明示操作**で実行。
- 認可：Provider ログイン必須（未ログインは 302→/provider/login）。
- 入力：
  - `GET /provider/db`（引数なし＝**内包ルール**を適用）＋ **診療科を upsert**。
  - `GET /provider/db?packs=/path/to.jsonl`（**外部ルールパック**）＋ **診療科を upsert**。
- トランザクション：**ファイル単位**で 1 トランザクション。診療科 upsert は別トランザクション。
- 記録：`import_runs` に `pack_name/sha256/inserted/updated/failed/lines_read`。
- L2 ログ：`rules_packs_apply_start/ok/failed`, `departments_seed_apply_start/ok/failed`。
- 成功応答：`{ ok:true, applied:true, counts:{provider_rules:N, departments:M}, pack:{name,sha256} }`（200 + no-store）。

## 件数下限（受入）
- ルール：**最終 N ≥ 208**（Base25 + A〜D。内包追加バッチがあれば加算）。
- 診療科：**最終 M ≥ 35**。

## 冪等性
- 同一データで再実行しても N/M は増え続けない（更新または無変更）。

## CLI 互換
- `cli/sync.php`：実ネット取得。`SYNC_SEED_URLS` 未設定は exit=3（seed_missing）。`--cycle` は EmptyDB Auto-Fetch。
- `cli/build_rules.php`：テンプレ到達性検査。`--strict` 到達0件は exit=2。
- `cli/import_db.php`：**/provider/db 相当**。引数なし＝内包、`--packs` 指定＝外部。診療科 upsert も実施。
