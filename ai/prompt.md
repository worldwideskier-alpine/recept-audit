# Codex generation prompt

## LANGUAGE (MUST)

language: php

## ALLOWED_PATHS (only modify/create under these)

- .blade.php
- .jsx/.tsx
- /app.php
- /app/Locale/ja.json
- /config.php
- /index.php
- /path/to.json
- AUTO_REGEN_REPORT.json
- COMPLIANCE.json
- COMPLIANCE.json.html
- Log.php
- MANIFEST.json
- Node.js
- ROOT/COMPLIANCE.json
- ROOT/evidence/verify/VERIFY_REPORT.json
- ROOT/spec/tests/tools/langmap.json
- STATE.json
- Schema.php
- TMPDIR/out.html
- app.js
- app.php
- app/SOT/schema.required.json
- app/Support/DB.php
- app/Support/Res.php
- app/Support/Schema.php
- app/config.php
- assets/theme.css
- base.yml
- blade.php
- cli/build_rules.php
- cli/import_db.php
- cli/sync.php
- config.php
- dist/.keep
- env-lite.php
- env_matrix.json
- evidence/gen/AUTO_REGEN_PLAN.json
- evidence/gen/GEN_PROVENANCE.json
- evidence/pkg/GATE_SIG.json
- evidence/runtime/.keep
- evidence/static/.keep
- evidence/static/PHPLINT_HASHES.json
- evidence/verify/VERIFY_REPORT.json
- file-globs.yml
- gate-matrix.yml
- health-lite.php
- lang_profile.json
- langmap.json
- main.py
- out/rules_packs.json
- profiles/base.yml
- profiles/dev.yml
- profiles/prod.yml
- rtm.yml
- schema.required.json
- schema.sql
- spec/foundation/file-globs.yml
- src/.keep
- src/App/Controllers/HealthController.php
- sync.php
- tests/.keep
- tools/.keep
- tools/lang_profile.json
- tools/langmap.json

### spec/env/README.md

# spec / env（環境プロファイル：プラットフォーム非依存）

本ディレクトリは **実行・配備環境の実値と方言ロック**を記述します。生成（foundation）や検査（tests）とは
役割を分離し、**ここを唯一の実値SOT（Single Source of Truth）**とします。実値（パスワード等）は
Git に直接書かず、`secrets/` のサンプルと CI/CD のシークレット管理を用います。

- `policies.md` …… 主要方針（Apache/BNORM、no-store、DB方言、HTTPクライアント等）
- `profiles/*.yml` …… 環境値のスキーマ化されたプロファイル（base/dev/prod）
- `apache/htaccess.bnorm.conf` …… ディレクトリ相対BNORMの正本（サブディレクトリ安全）
- `sql/policies.md` …… MySQL 8.0 方言ロック（DDL/Upsert/照合フォールバック等）
- `cron/runner.md` …… 定期実行ランナの要件と例
- `secrets/.env.example` …… 実値の置き場（**サンプルのみ**。実値はCI等で注入）

> 本構成は、アップロードいただいた環境仕様（v1.0.6）の要求を抽象化し、値はプレースホルダ化しています。
> 実値は CI のシークレットやサーバの `.env` で注入してください。

### spec/env/cron/runner.md

# cron / runner（例：単一cron + EmptyDB Auto-Fetch）

# Asia/Tokyo（10分間隔）の例：
*/10 * * * * cd {BASE} && php cli/sync.php --cycle >> storage/logs/cron.sync.log 2>&1

## --cycle の疑似フロー（要約）
- 排他取得 → 空判定（コア表の未存在、provider_rules=0 など）
- 空なら --force で取得 → 変化があれば build_rules → import_db
- 新着 0 が3サイクル継続で指数バックオフ（最大6h）

### spec/env/policies.md

# env / policies（方針・拘束事項）

## 1) ランタイム前提（MUST）
- PHP 8.3（CLI/CGI同一想定）。フレームワーク前提なし。PSR 準拠は任意。
- Web: **Apache + mod_rewrite**。**AllowOverride FileInfo 以上**を付与。
- HTTP クライアントは **cURL** を使用（`file_get_contents` 等のHTTP用途は不可）。

## 2) Apache / BNORM（MUST）
- `.htaccess` は **ディレクトリ相対で `app.php` へ集約**（BNORM）。**`RewriteBase /` の常時固定は不可**。
- **`Options -MultiViews -Indexes` を必須**（内容ネゴシエーションを無効化）。
- 正本は `apache/htaccess.bnorm.conf` を参照し、**そのまま配置**する。

## 3) HTTP キャッシュ（MUST）
- すべての応答に **`Cache-Control: no-store`** を付与（3xx を含む。301 は運用上禁止）。

## 4) DB 方言ロック（MUST）
- エンジン: **MySQL 8.0.x**（MariaDB 非対象）。
- 文字コード: **utf8mb4**。照合: **utf8mb4_0900_ai_ci** を第一候補。不可時 **utf8mb4_unicode_ci** へ自動フォールバックし、**L2ログ**を記録。

## 5) /health（最小ブート）
- 役割: **スキーマ自己確保のみ**を行う最小ブート。データ投入（seed/import）は禁止。

## 6) ログ
- `/health` の成功/失敗を **1行JSON(L2)** で `storage/logs/app.log` に記録（`health_min_boot_pass|fail`）。

## 7) コード実装スタイル・最低拘束
- DB アクセスは **`App\Support\DB` の静的ファサード**を提供（`pdo()` / `q()` / `ensure_index()`）。
- Support/** は **先頭〜80行以内**で自前依存を `require_once` し、読み込み順に依存しない。
- リダイレクトは **常に BNORM 経由**（`href()` 等）。`header('Location: /...')` の直書きは禁止。

### spec/env/profiles/base.yml

# profiles/base.yml — 既定（全環境共通の最小セット）
runtime:
  php: "8.3"
  web: "apache-mod_rewrite"
  front_controller: "app.php"
  http_client: "curl"
  cache_control_no_store: true

db:
  engine: "mysql8"
  host: "${DB_HOST}"
  port: "${DB_PORT:-3306}"
  name: "${DB_NAME}"
  user: "${DB_USER}"
  pass: "${DB_PASS}"
  charset: "utf8mb4"
  collation_primary: "utf8mb4_0900_ai_ci"
  collation_fallback: "utf8mb4_unicode_ci"

http:
  timeout_seconds: 30
  retry: 3

paths:
  storage: "storage"
  logs: "storage/logs"

rules:
  apache_allow_override: "FileInfo+"
  apache_disable_multiviews: true
  apache_disable_indexes: true
  forbid_rewritebase_root: true  # 必要時のみ実配備パスを明記

# -- env_matrix 参照キー（placeholder。実値は .env / CI secrets で注入）
# DB_HOST=…
# DB_NAME=…
# DB_PASS=…
# DB_PORT=…
# DB_USER=…
# SMTP_HOST=…
# SMTP_PASS=…
# SMTP_PORT=…
# SMTP_USER=…
# STORAGE_BUCKET=…
# STORAGE_DRIVER=…
# STORAGE_REGION=…
# TENANT_SALT=…

### spec/env/profiles/dev.yml

# profiles/dev.yml — 開発向け（本番相当でテスト）
extends: "base.yml"
env:
  APP_ENV: "development"
  LOG_LEVEL: "debug"
  SYNC_MAX_PER_CYCLE: 1000
  SYNC_DB_MIN_ROWS: 100
  HTTP_TIMEOUT: 30
  HTTP_RETRY: 3
  STORAGE_BASE: "storage"

# -- env_matrix 参照キー（placeholder。実値は .env / CI secrets で注入）
# DB_HOST=…
# DB_NAME=…
# DB_PASS=…
# DB_PORT=…
# DB_USER=…
# SMTP_HOST=…
# SMTP_PASS=…
# SMTP_PORT=…
# SMTP_USER=…
# STORAGE_BUCKET=…
# STORAGE_DRIVER=…
# STORAGE_REGION=…
# TENANT_SALT=…

### spec/env/profiles/prod.yml

# profiles/prod.yml — 本番向け（セキュア設定を強化）
extends: "base.yml"
env:
  APP_ENV: "production"
  LOG_LEVEL: "info"
  SYNC_MAX_PER_CYCLE: 1000
  SYNC_DB_MIN_ROWS: 100
  HTTP_TIMEOUT: 30
  HTTP_RETRY: 3
  STORAGE_BASE: "storage"

# -- env_matrix 参照キー（placeholder。実値は .env / CI secrets で注入）
# DB_HOST=…
# DB_NAME=…
# DB_PASS=…
# DB_PORT=…
# DB_USER=…
# SMTP_HOST=…
# SMTP_PASS=…
# SMTP_PORT=…
# SMTP_USER=…
# STORAGE_BUCKET=…
# STORAGE_DRIVER=…
# STORAGE_REGION=…
# TENANT_SALT=…

### spec/env/rtm.yml

# env / rtm.yml — 要件トレーサビリティ（tests への橋渡し）
env_db_dialect_001:
  desc: "db_engine=mysql8 を COMPLIANCE.json で確認"
  evidence: "COMPLIANCE.json"
env_db_ddl_policy_mysql_001:
  desc: "IF NOT EXISTS 付き INDEX 禁止（grep）＋ E2E で index 存在確認"
  evidence: "schema.sql; app/Support/Schema.php"
env_db_upsert_mysql_001:
  desc: "VALUES() を Upsert で使用しない（静的）"
  evidence: "app/Support"
env_db_charset_001:
  desc: "照合フォールバック時に charset_fallback を L2ログへ"
  evidence: "app/Support/DB.php; storage/logs/app.log"
env_health_minboot_mysql_001:
  desc: "/health=最小ブート（スキーマのみ）"
  evidence: "app.php; schema.sql"
env_health_log_mysql_001:
  desc: "/health 成否ログ（health_min_boot_pass/fail）"
  evidence: "storage/logs/app.log"
env_schema_log_mysql_001:
  desc: "schema_exec_failed / schema_bootstrap_ok / schema_index_* を記録"
  evidence: "app/Support/Schema.php; storage/logs/app.log"
env_nostore_all_001:
  desc: "全3xx含め no-store・301禁止"
  evidence: ".htaccess; app.php"
env_apache_multiviews_lock_001:
  desc: "Options -MultiViews を .htaccess へ明示"
  evidence: ".htaccess"
env_apache_htaccess_bnorm_001:
  desc: "RewriteRule ^ app.php / RewriteEngine On / RewriteBase 固定禁止"
  evidence: ".htaccess"
env_apache_allowoverride_001:
  desc: "AllowOverride FileInfo 以上を vhost で設定"
  evidence: "httpd.conf (vhost) 設定"
env_code_db_api_style_lock_001:
  desc: "`App::Support::DB` 静的ファサードを必ず提供"
  evidence: "app/Support/DB.php; tools/run_checks.sh; php -r 検査証跡"
env_code_msc_self_lock_001:
  desc: "Support自己完結（先頭〜80行で require_once）"
  evidence: "app/Support/**; tools/run_checks.sh; evidence/static/MSC_LOG_DEP_OK.txt"
env_code_health_no_classerr_001:
  desc: "/health 実行で Class not found を出さない"
  evidence: "app.php; storage/logs/app.log; evidence/runtime/HEALTH_NO_CLASSERR_OK.txt"
env_code_redir_bnorm_lock_002:
  desc: "Location 直書き禁止・href()必須"
  evidence: "app/Support/Res.php; evidence/static/REDIR_ABS_PATH_OK.txt; REDIR_BNORM_USE_OK.txt"

### spec/env/sql/policies.md

# sql / policies（MySQL 8.0.x 方言ロック）

- **DDL: `CREATE INDEX IF NOT EXISTS` を禁止。**
  - 実装: `information_schema.STATISTICS` による存在確認 → `CREATE INDEX` 実行の二段階で確保。
- **Upsert: `INSERT … ON DUPLICATE KEY UPDATE` を使用し、`VALUES()` 参照は禁止。**
- **文字コード/照合: `utf8mb4` / `utf8mb4_0900_ai_ci`。不可時 `utf8mb4_unicode_ci` にフォールバックし、L2ログへ `charset_fallback` を出力。**

### spec/features/README.md

# spec / features（機能仕様：プラットフォーム非依存）

本ディレクトリは **機能要件**（URL動作、RBAC、データモデル、取込、UI、ログ、E2E受入）を言語やFWに依存せずに定義します。
生成（foundation）・検査（tests）・環境（env）とは役割を分離し、ここでは**「何を実現するか」**のみを規範化します。

- `policies.md` …… 前提・BNORM・no-store・HTTP/DB・禁止事項（MUST）
- `endpoints.md` …… URL/早期ハンドラ/BNORM・各応答の規範
- `provider_setup.md` …… `/provider/setup` の厳格ポリシー（GET無副作用/POST作成のみ/二回目以降非公開）
- `data_model.md` …… DDL規範（列/固定行/インデックス/予約語回避/UPSERT方式）
- `schema.required.json` …… **Schema SOT（唯一の機械可読正本）**
- `env_matrix.json` …… 機能トグルと必須ENVキー（機械可読）
- `imports.md` …… `/provider/db` と CLI 相当の取込要件・件数下限・冪等規範
- `cli.md` …… `cli/sync.php` / `cli/build_rules.php` / `cli/import_db.php` の要件
- `cron.md` …… 単一cron + EmptyDB Auto-Fetch 方針
- `ui_rbac.md` …… 画面要件（Provider Dashboard/Rules、Tenants 新規、Admin Clerk 新規）とRBAC
- `logs.md` …… L2ログ（1行JSON）イベント規範
- `acceptance_e2e.md` …… 受入試験（H/A/B/C/D/E/F/G/I 等）

> 値（DB接続等）は `spec/env/**` を唯一のSOTとし、本書は**機能の振る舞い**のみを規範化します。

### spec/features/acceptance_e2e.md

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

### spec/features/cli.md

# features / cli（CLI 要件）

- `cli/sync.php`：cURL ベースの実ネット取得。`--cycle` は EmptyDB Auto-Fetch を実装。`SYNC_SEED_URLS` 未設定は exit=3。
- `cli/build_rules.php`：テンプレ到達性検査。`--strict` 到達0件は exit=2。
- `cli/import_db.php`：/provider/db と等価の取込。診療科 upsert も実施。

### spec/features/cron.md

# features / cron（単一cron + EmptyDB Auto-Fetch）

- 例（Asia/Tokyo）: `*/10 * * * * cd {BASE} && php cli/sync.php --cycle >> storage/logs/cron.sync.log 2>&1`
- 空判定：コア表未存在／`provider_rules=0` または ENV 最小値未満（min 25）／`departments<35`。
- 空なら **force 取得 → build_rules → import（/provider/db 同等：ルール＋診療科）** を行う。

### spec/features/data_model.md

# features / data_model（DDL規範・UPSERT・固定行）

## 列・制約（抜粋 / MUST）
- `users` に `tenant_id INT NULL`（FK: tenants.id）。Provider は NULL、Admin/Clerk は作成テナントの ID を必須設定。
- `users.email` は **全体ユニーク**。`users.username` は **廃止**（参照禁止）。
- `provider_rules` は `rule_condition` / `rule_action`（入力JSONの `condition` / `action` をマッピング）。
- `sync_commands` に `ix_sc_status_requested(status, requested_at, id)`（IF NOT EXISTS 禁止→存在確認後に作成）。
- **固定行**：`rules_sync_state(id=1)` / `sync_runner_state(id=1)` を保証。

## UPSERT 方針（MUST）
- **採用**：`INSERT ... VALUES (...) AS new ON DUPLICATE KEY UPDATE col = new.col`
- **禁止**：`... UPDATE col = VALUES(col)`（`/* FORBIDDEN: VALUES() */`）
- 適用：`provider_rules`, `departments`, `users`（/provider/setup 作成時）ほか全UPSERT。

## 予約語回避
- DB の識別子（テーブル/列/インデックス/制約名）に予約語を使用しない。入力データのキー名が予約語でも DB 列名へ転用しない。

## 代表テーブル（規範）
- `tenants`, `users`, `departments`, `provider_rules`, `patients`, `claims`, `claim_items`, `audit_rules`, `tenant_rule_overrides`, `sync_commands`, `rules_sync_state`, `sync_runner_state`, `import_runs`, `job_runs`。

### spec/features/endpoints.md

# features / endpoints（URL動作・早期ハンドラ・BNORM）

## 早見表（主要）
| Path | 要旨 | 応答 | 備考 |
|---|---|---|---|
| `/env` | FC 最上流で JSON 直返し | 200 JSON + no-store | require/include/autoload/session_start/ob_start 禁止 |
| `/env/` | `/env` と等価 | 200 JSON + no-store | 末尾スラ差で意味が変わらない |
| `/env-lite` | 物理ファイル直返し | 200 | `.htaccess` で -f 素通し |
| `/health-lite` | 物理ファイル直返し | 200 | 同上 |
| `/health` | **最小ブート（スキーマのみ）** | 200 JSON + no-store | seed/import 禁止 |
| `/` | ログインへ誘導 | 302 → `/login` + no-store | 301 禁止 |
| `/provider` | Provider 入口 | 302 → `/provider/login` + no-store | 末尾スラ任意 |
| `/provider/login` | Provider ログイン | 200 | |
| `/provider/setup` | 初期ユーザー作成（厳格ポリシー） | GET: 初回のみ200（無副作用） / POST: 作成→302 login | 二回目以降は GET/POST とも 302 |

BNORM（Directory‑relative Front Controller）により、サブディレクトリ配備でも同一動作を保証します。

### spec/features/env_matrix.json

{
  "FEATURE_MAIL": [
    "SMTP_HOST",
    "SMTP_PORT",
    "SMTP_USER",
    "SMTP_PASS"
  ],
  "FEATURE_STORAGE": [
    "STORAGE_DRIVER",
    "STORAGE_BUCKET",
    "STORAGE_REGION"
  ],
  "FEATURE_DB": [
    "DB_HOST",
    "DB_NAME",
    "DB_USER",
    "DB_PASS",
    "DB_PORT"
  ],
  "FEATURE_PROVIDER_MULTI_TENANT": [
    "TENANT_SALT"
  ]
}
### spec/features/imports.md

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

### spec/features/logs.md

# features / logs（L2：1行JSON）

- 代表イベント：
  - `db_connect_failed`, `schema_bootstrap_ok/failed`, `seed_upsert_fallback`
  - `rules_packs_apply_start/ok/failed`, `departments_seed_apply_start/ok/failed`
  - `auth_login/logout/guard_blocked`
  - `setup_allowed/setup_created/setup_redirected`
  - `tenants_create_start/ok/failed`, `tenant_admin_created`, `tenant_clerk_created`
- フォーマット：`{timestamp, level, event, file, line, ...}` を**1行JSON**で。

### spec/features/policies.md

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

### spec/features/provider_setup.md

# features / provider_setup（/provider/setup の厳格ポリシー）

## 要件（MUST）
- **GET（初回のみ公開）**：`users=0` の**初回のみ** 200。**無副作用の HTML フォーム**（email, password）表示。HEAD も 200 + no-store。301 禁止。
- **POST（作成のみ）**：初期ユーザー作成時に `role='provider'`, `force_reset=1` を**必須**付与。成功後 **302 → /provider/login（no-store）**。
- **2回目以降（users>0）**：**GET/POST とも** 302 → `/provider/login`（no-store）。フォームは非公開。
- **UI**：公開画面から `/provider/setup` へのリンクは**アンカー禁止**（文言のみ）。`X-Robots-Tag: noindex, nofollow, noarchive` 推奨。

## 受入判定（E2E）
- A) `users=0` 環境で GET を連続2回：**200 + 無副作用**（`users` 件数不変）。
- B) POST 後、**1 行作成**・`force_reset=1`・**302 → /provider/login（no-store）**。
- C) `users>0` 環境で **GET/POST とも 302**（**301 が 0 件**）。

### spec/features/schema.required.json

{
  "version": "1.1",
  "tables": [
    "tenants",
    "users",
    "departments",
    "provider_rules",
    "patients",
    "claims",
    "claim_items",
    "audit_rules",
    "tenant_rule_overrides",
    "sync_commands",
    "rules_sync_state",
    "sync_runner_state",
    "import_runs",
    "job_runs"
  ],
  "fixed_rows": [
    {
      "table": "rules_sync_state",
      "pk": "id",
      "value": 1
    },
    {
      "table": "sync_runner_state",
      "pk": "id",
      "value": 1
    }
  ],
  "indexes": [
    {
      "table": "sync_commands",
      "name": "ix_sc_status_requested",
      "columns": [
        "status",
        "requested_at",
        "id"
      ]
    }
  ]
}
### spec/features/ui_rbac.md

# features / ui_rbac（UI と RBAC の要件）

## Provider Dashboard（最小）
- `/provider/dashboard`：ログイン後ランディング。見出し「Provider Dashboard」＋クイックリンク（Tenants, ルール適用 `/provider/db`, グローバルルール `/provider/rules`, ジョブ, ログアウト）。
- **機能が 0 件でも 200**。

## Provider Global Rules
- `/provider/rules`：一覧/検索/詳細を提供（編集は MUST）。表示：title, version, source_date, created_at、詳細は `rule_condition` / `rule_action`。

## Tenants（新規）
- **GET `/provider/tenants/new`**：200（no-store）。フォーム：`tenant_name`, `admin_email`, `admin_password`, `csrf_token`。
- **POST `/provider/tenants/new`**：CSRF 必須。**1 トランザクション**で `tenants` 作成 → `users(admin)` 作成（`tenant_id` 紐付け、`force_reset=1`）。成功は **201** または **302→/provider/tenants**、失敗は **422/400**（部分作成禁止）。

## Admin Clerk 新規作成
- **GET `/admin/clerk/new`**：200（no-store）。フォーム：`clerk_email`, `clerk_password`, `csrf_token`。
- **POST `/admin/clerk/new`**：`users(role='clerk', tenant_id=<adminのtenant_id>, force_reset=1)` を作成。成功は **201** または **302→/admin/users`、失敗は **422/400**。

### spec/foundation/README.md

# spec / foundation（生成仕様：プラットフォーム非依存）

このディレクトリは、**生成**そのものに必要な共通ルールだけを収めます。Chat/対話に依存せず、
CI/CD（ジョブ、アーティファクト、リリース等）で完結する前提です。

- `policies.md` …… 生成規約・フロー・AHR・安全規約（Chat 前提の記述は排除）
- `contracts.md` …… 生成 I/O の取り決め（成果物は `dist/*.zip` と証跡、合否は Exit Code とファイル）
- `file-globs.yml` …… 生成で書き換え可能な領域・必須ディレクトリ・雛形

> 環境・機能の固有要件は `spec/env/**`, `spec/features/**` など別紙に置き、foundation には混在させない方針です。

### spec/foundation/contracts.md

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

### spec/foundation/file-globs.yml

required_dirs:
- src
- tests
- tools
- storage
- evidence
- dist
allowed_write:
- src/**
- tests/**
- tools/**
- storage/**
- evidence/**
- dist/**
- .htaccess
- app.php
- env-lite.php
- health-lite.php
- COMPLIANCE.json
forbidden_write:
- spec/**
- .github/**
- ai/**
- /*
scaffold:
- path: src/.keep
- path: tests/.keep
- path: tools/.keep
- path: evidence/static/.keep
- path: evidence/runtime/.keep
- path: dist/.keep

### spec/foundation/policies.md

# foundation / policies（プラットフォーム非依存・生成専用）

> 本書は *生成* に関わる共通規約・フローのみを定義します。**対話（（対話UIに非依存））前提の文言は一切使用しません。**
> 生成結果の通知・配布は CI/CD のアーティファクト、リリース、あるいは任意の成果物ストアを用います。

## 0. スコープと原則（MUST）
- 本書は「生成」のみを対象とする。**環境固有値**や**機能固有の要件**は別紙（env/features）に置く。
- 生成は **再現可能・差分最小** を原則とし、同じ入力に対して同じ成果物が得られる。
- 出力は **ワークツリー**上に作成し、その後 **CI/CD が成果物として収集**・配布する。対話的な「アーティファクト取得」提示は行わない。

## 1. 実行モード
- `full` : 既存生成物を更地化し、**必要ファイルを一式再生成**する。
- `incremental` : 変更影響範囲のみを更新し、未変更部分は保持する。

## 2. AHR（Auto‑Heal & Retry：自動修復）
- Gate 失敗・欠落が検出された場合、**最小差分の修復**を最大 3 ラウンドまで実施し、各ラウンドごとに検査をリラン。
- AHR 対象例：LITERAL 正規化、import/using の注入、HTML LS 必須タグ注入、相対リンクの標準化、ERR‑GUARD の補完 等。
- 仕様逸脱や環境値欠落は AHR 対象外とし、**検査側で不備を明示**する。

## 3. 生成フロー（概略）
1. **前処理**：必要ディレクトリの確保、`spec/foundation/file-globs.yml` の読込、ワークスペース整備。
2. **合成**：生成に必要な仕様（foundation + 当該機能・環境）を結合し、プロンプト/計画を作成。
3. **生成**：コード・設定・スクリプトを出力。`allowed_write` に適合しないパスは書き込み禁止。
4. **検査**：`tools/run_checks.*` を実行し、証跡と `COMPLIANCE.json` を生成。
5. **AHR**：不合格時は最小差分修復→検査を最大 3 ラウンド。
6. **成果物化**：`tools/pack.*` で ZIP/TAR を `dist/` へ出力。**CI/CD がアーティファクトとして公開**する。

## 4. 納品の取り扱い（（対話UIに非依存） 非依存）
- 納品物は **CI/CD のアーティファクト**・**パッケージレジストリ**・**リリース資産**などに発行する。
- ログは CI ジョブログと `evidence/**` に保存し、**人手の “CIアーティファクトのURL提示” は要求しない**。
- 受け入れ判断は `COMPLIANCE.json` と Gate 証跡に基づき **自動**で行う。

## 5. 安全規約
- `spec/**`, `.github/**`, `ai/**` は生成対象外（補助スクリプトの追加は別途合意時に限る）。
- 生成ファイルは **UTF‑8 LF**。外部依存導入は方針に明記された場合のみ可。
- `file-globs.yml` の `forbidden_write` に抵触する変更は破棄し、エラーとする。

### spec/tests/README.md

# spec / tests（検査仕様：プラットフォーム非依存）

本ディレクトリは **検査（tests）** の共通規約とゲートを定義します。言語やフレームワークに依存しない
**Polyglot 前提**で記述し、必要に応じて各言語アダプタ（リンタ・テンプレ検出など）で具体化します。

- `policies.md` …… 検査の原則・必須ゲート・証跡の取り扱い
- `gate-matrix.yml` …… Phase S/B/D のゲート定義（言語非依存名）
- `runner/run_checks.sh` …… 安定ランナー（静粛合格・厳格モード・証跡書き出し）
- `tools/langmap.json` / `tools/polyglot_lint.sh` / `tools/render_smoke.sh` / `tools/verify_report.sh` …… 代表実装
- `checks/*.md` …… 代表チェック（HTML-LS、ERR-GUARD、storage、manifest等）

> 成果物の配布や受入判定は CI/CD 側のルールに従い、**対話的なアーティファクト取得や手詰めZIP**は想定しません。

### spec/tests/gate-matrix.yml

phase_s:
- key: polyglot_lint_ok
  must: true
  evidence: evidence/static/POLYGLOT_LINT_OK.txt
- key: html_ls_global_ok
  must: true
  evidence: evidence/static/HTML_LS_GLOBAL_OK.txt
- key: tools_presence_ok
  must: true
  evidence: evidence/static/TOOLS_OK.txt
- key: storage_writable_ok
  must: true
  evidence: evidence/static/STORAGE_BASE_OK.txt
phase_b:
- key: health_boot_head_ok
  must: true
  evidence: evidence/runtime/HEALTH_BOOT_HEAD_OK.txt
phase_d:
- key: sot_schema_dynamic_ok
  must: false
  evidence: evidence/runtime/SOT_SCHEMA_DYNAMIC_OK.txt
pack:
  must_all_true:
  - verify_report_ok
  - zip_ready_ok
  - pack_provenance_ok

### spec/tests/policies.md

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

### spec/tests/tools/langmap.json

{
  "php":    {"ext": ["php"],        "templates": ["app/Pages","templates","resources/views","public"]},
  "html":   {"ext": ["html","htm"], "templates": ["public","templates"]},
  "twig":   {"ext": ["twig"]},
  "blade":  {"ext": ["blade.php"]},
  "ejs":    {"ext": ["ejs"]},
  "hbs":    {"ext": ["hbs","handlebars"]},
  "vue":    {"ext": ["vue"]},
  "jsx":    {"ext": ["jsx","tsx"]}
}

## STRICT OUTPUT RULES

- Return ONE unified diff (UTF-8), repository-rooted; no prose, no code fences.
- Respect LANGUAGE above. Implement in that primary language unless the spec explicitly says otherwise for a file.
- When ALLOWED_PATHS is non-empty, modify/create files **only** under those paths. Otherwise, use the exact paths named in the spec; do NOT invent filenames.
- If the spec requests deletions/renames/moves, encode them in the diff.
