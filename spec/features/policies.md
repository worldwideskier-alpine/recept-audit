# features / policies（前提・規範・禁止事項）

## 対象・前提（MUST）
- PHP 8.3（CLI/CGI同一）。フレームワーク不使用、PSR 任意。
- Web: Apache + mod_rewrite（.htaccess 有効、RewriteBase 任意、END 禁止）。
- DB: MySQL 8.0.19+、utf8mb4、照合 utf8mb4_0900_ai_ci（不可時 utf8mb4_unicode_ci に自動フォールバックし L2 ログ）。
- フロントコントローラ（FC）: `{BASE}/app.php`（サブディレクトリ配備に非依存／BNORM）。
- HTTP クライアントは cURL のみ（`file_get_contents` 等でのネット取得は禁止）。
- ログ（L2）必須、AUTH-REALM-SPLIT（/provider と一般の分離）必須。

## URL / BNORM / キャッシュ（MUST）
- `/env`：FC最上流で JSON 直返し（no-store）。`/env/` も 200。同フェーズでの require/include/autoload/session_start/ob_start 禁止。
- `/env-lite` `/health-lite`：物理ファイル直返し（FC 特有ヘッダ無し）。Rewrite は `-f/-d` 優先素通し。
- `/health`：**最小ブート（スキーマのみ）**。seed/import 禁止。
- `/`：302 → `/login`（no-store、301 禁止）。
- **全応答**に `Cache-Control: no-store` を付与（3xx 含む、301 禁止）。
- BNORM: `dirname($_SERVER['SCRIPT_NAME'])` を用い、末尾スラ有無で経路解釈を変えない（`/env` と `/env/` は 200）。

## AUTH-REALM-SPLIT（MUST）
- Provider Realm：`/provider` → 302 `/provider/login`、`/provider/setup`（初回 GET のみ公開/無副作用・POST 作成のみ・二回目以降は非公開）。
- General Realm：`/login` を入口。`/admin/*` `/clerk/*` は認証必須（未ログインは 302→/login）。
- 禁止：/health 等でのユーザー/テナント自動作成。

## 禁止・その他
- 301 全禁止。ログは 1 行 JSON（L2）。HTTP は no-store。DB は MySQL 8.0 方言固定。
