# features / acceptance_e2e（受入試験：抜粋）

- H) Tenants 新規：GET フォーム項目の存在 → POST 成功で tenants/users に1行（admin, force_reset=1）→ 重複でロールバック。
- A) /health 最小ブート：`{ ok:true, db_ok:true, initialized:true }`。
- B) 取込（内包）：/provider/login → /provider/db（引数なし）= 200、`rules ≥ 208` かつ `departments ≥ 35`。
- C) 取込（外部 packs）：/provider/db?packs=... = 200、増分または冪等、`departments ≥ 35` 維持。
- D) CLI 互換：`php cli/import_db.php`（引数なし）= 0。再実行で件数不変。
- E) ネガ：`sync.php exit=3`（seed_missing）、`exit=2`（download_failed）。
- F) UI-E2E（Provider Tenants）3点。
- G) SetupForm：`users=0` で GET 2 回 = 200 + 無副作用、POST 作成後 302→/provider/login（no-store）、`force_reset=1`。
- I) Clerk 新規：`users(role='clerk')` を tenant_id 紐付けで作成。重複は 4xx + ロールバック。
