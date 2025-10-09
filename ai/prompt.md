# Codex generation prompt


--- spec/generation.txt ---
生成仕様書
版数: v3.7.2
P25-10-02 00:00:00 JST+0900
投入順: 生成仕様書 → 検査仕様書 → 環境仕様書 → 機能仕様書

# 0) 目的（MUST）
本書は、ユーザーが添付・指示する仕様書群（生成／検査／環境／機能）に対し、チャット入出力・生成プロセス・納品形態を“機械可読な規約”として統一する唯一の生成手順書である。
本版（v3.7.1）は v3.6.1 の AHR 方針と ERR-GUARD（行単位 try/catch + L2ログ）を継承しつつ、**ポリグロット（多言語）対応**を新設・強化する。具体的には **LANG-PROFILE レジストリ**と **POLYGLOT-AHR-CORE** を導入し、言語固有のLint/補正/ガードを**宣言駆動**で適用可能にする。/provider/login と /provider/setup の「公開導線と秘匿導線」の関係は従来どおりセキュリティ観点で明確化する。
環境依存の実値（ベースURL等）は引き続き「環境仕様書」を唯一の権威とし、本書ではパス表記のみを用いる。

## 0.1) ポリグロット方針（MUST／新設）
- 本書の規定は**言語非依存**を原則とし、言語固有のルールは **tools/lang_profile.json**（以下「LANG-PROFILE」）に**機械可読に宣言**する。
- 生成・検査・AHR は **LANG-PROFILE** を参照して自動的に適用/スキップを切替える（対象拡張子が存在しない言語は SKIP）。
- 既存記述に含まれる言語例（PHPの擬似コード等）は**例示**であり、**拘束力は LANG-PROFILE に委譲**する。

# 1) 投入順（Authoritative Order）
標準投入順は **生成 → 検査 → 環境 → 機能** とする（本ヘッダにも明記）。
- 以後の状態判定・NEXTガイダンス・自動生成着手はこの順序を基準に行う。

# 2) チャットI/Oハンドシェイク（D / S / R）
## 2.1 D（議論）
- 回答は簡潔に。**最後の行に「NEXT: 次に投入すべき仕様書名」**を必ず1行で出力。

## 2.2 S（仕様投入）
- **S-Gen（生成仕様書受領時）**
  - 受領・整合が成功した場合、応答は**2行のみ**：
    1) `MISS: <未投入の仕様書を半角カンマ区切りで列挙>`
    2) `NEXT: 検査仕様書`
  - 例）`MISS: 検査仕様書, 環境仕様書, 機能仕様書` / `NEXT: 検査仕様書`
  - ※**READYは出さない。**
- **S-Inspec（検査仕様書受領時）／S-Env（環境仕様書受領時）**
  - 受領・整合が成功した場合、まだ未投入が残る限り **`MISS:` + `NEXT:` の2行のみ**。
- **S-Func（機能仕様書受領時＝最後の仕様書）**
  - 受領・整合が成功した瞬間に、**自動でR（生成/AHR）へ遷移し即時着手**する。
  - 生成完了時のチャット出力は **1行のみ**：`納品物: [Download ZIP](...)`
  - 仕様書類（.txt 納品要求時）は **`納品物: [Download TXT](...)`** を1行のみ出力。

## 2.3 R（生成・納品：**三段セルフヒール（AHR）**）
- **STEP表記は廃止**。進捗行や DONE 行は出さない。
- v3.4.0 に続き **Fail-Closed（NG時に即停止・ZIP禁止）** は**廃止**する。
- 本版は **Gate-0〜2** を維持しつつ、**NG検知→AHR（自動修復）→再検査**の**クローズドループ**で合格まで自動再試行する。
- AHR は**最小差分の自動修復**のみを対象とし、**仕様逸脱・実値欠落**（環境仕様書未定義など）は自動修復の対象外（その場合は D 体裁で不足項目を提示）。
- **ERR-GUARD 追補**：AHR は **ERR-GUARD の欠落**（try/catch 未設定・log_line 未呼出）を検出した場合、**build/working/** のスナップショットに対し**最小差分パッチ**を適用し、再検査を行う。

### 2.3.a POLYGLOT-AHR-CORE（MUST／新設）
AHR は LANG-PROFILE を参照し、以下の**言語非依存オペレーター**を適用する：
- **LITERAL-NORMALIZE**：`lang_profile.json.languages[*].literal_normalize[]` に列挙された正規表現置換を**最小差分**で適用（例：他言語系 8進リテラル `0o[0-7]+` を各言語の正規表現に正規化）。
- **SYM-IMPORT-GUARD**：`support_api` に登録された**プロジェクト標準API**の呼出しが未修飾/未インポートの場合、言語別の**import/using/完全修飾**を自動注入。
- **ERR-GUARD-MAP**：重要経路（/health 相当、DB接続/DDL相当など）に、各言語の構文による **try/catch（try/except 等）＋L2ログ**を**粒度小さく**挿入。
- **RUN-CHECKS-POLYGLOT**：AHR 後は **tools/run_checks.*** を再実行し、言語別リンタ（`lint_cmd`）での再検証を必須とする。

  **Gate-0: PACK-PROVENANCE-LOCK（MUST／継続＋汎用強化）**  
  - **FULL-ONLY-ACCEPTANCE（MUST）**: 受入対象は FULL のみ。差分／hotfix／手動ZIPは不可。 `COMPLIANCE.json.full_only_acceptance=true` を設定し、`tools/run_checks.*` は**差分納品痕跡**（欠落 `tools/` / `evidence/` / `MANIFEST.json` 不整合）を検出した場合は**非ゼロ終了**とする。

  - ZIP は **`tools/pack.*`** が実行し、同スクリプトが **`tools/run_checks.*` を先行実行**してからのみ生成可。  
  - `evidence/pkg/BUILD_LOG.txt` に `PACK_BY=<pack_script>` と実行時刻（UTC±）、および **`source="spec-only"`** を**必須記録**（テンプレ流用禁止）。  
  - **禁止**: `tools/` 以外から `evidence/**` や `COMPLIANCE.json` を生成・改変。  
  - `tools/run_checks.*` は Gate-1/2 の自己検証に成功した場合のみ、
    `evidence/pkg/ZIP_READY_OK.txt` を生成し、`COMPLIANCE.json` に  
    `.build_gate_zip_ready=true` / `.pack_provenance_ok=true` / `.zip_ready_ok=true` / **`.polyglot_lint_ok=true`** を**書き込む**。

  **Gate-1: run_checks + AHR 自動修復**  
  - `tools/run_checks.*` が失敗または必須証跡の欠落を検出した場合、**AHR を自動起動**する（詳細は #3）。
  - AHR が適用可能な NG の例：  
    TOKENS-FIRST / EVIDENCE-FRESHNESS / ABS-HREF-BAN / HTML LS / TOOLS-PRESENCE / STORAGE-PRESEED /
    BNORM/EARLY-ENV の軽微不整合 / LINT-SIG 再生成 / **HTML-LS-INJECT（全プロジェクト）** / **REL-HREF-BNORM-AUTO** / **LITERAL-NORMALIZE（ポリグロット）** / **SYM-IMPORT-GUARD（ポリグロット）** /
    SOT スキーマ宣言の不足（schema／DDL宣言追加） / **ERR-GUARD 欠落**（try/catch 未設置／log_line 未呼出の補完）
  - **AHR の適用→再Lint/再検査**を **最大3ラウンド**繰り返す（`AHR_MAX_ROUNDS=3`）。いずれかのラウンドで PASS すれば Gate-1 合格。

  **Gate-2: エビデンス在庫/完全性（AHR 連動）**  
  - `evidence/**/_OK*.txt` の最小集合が揃う（static/runtime/db/pkg 各カテゴリ≥1）。
  - **`evidence/static/*` の mtime が全**ソースファイル**の最新 mtime 以上（±120s）**。不足時は AHR が**証跡の再生成**を実施。  
    （※ 従来の「`*.php` 基準」は **LANG-PROFILE** に基づく**対象拡張子集合**へ一般化）
  - `MANIFEST.json.files[]` に evidence/** が**全列挙**され、物理実体と**完全一致**（不一致時は AHR で MANIFEST を再構築）。

- **納品条件**：Gate-0/1/2 が **いずれかの AHR ラウンドで PASS** した時点で `tools/pack.*` が ZIP を生成し、チャットは **「納品物: [Download ZIP](...)」の1行のみ**を出力。
- **手動ZIP（pack.* 非経由）を禁止**。

# 3) AHR（Auto-Rollback/Auto-Regen）実行モデル（MUST）
- **ワークツリー分離**：AHR は `build/working/`（可搬サブツリー）に**一時スナップショット**を作成し、修復は**スナップショット側のみ**で行う。原本は保持。
- **ロールバック原則**：修復が不要または有害と判定された差分は**元に戻す**。AHR は**最小差分**を保証し、関係ないファイルを変更しない。
- **実装要件（tools/auto_regen.*）**：
  1. `evidence/gen/AUTO_REGEN_PLAN.json` を入力に、対象 NG を **ルールベース**で fix。  
  2. 代表フィクサ（`tools/fixers/*`）の最小要件（**言語非依存の中核＋例示**）：  
     - **POLYGLOT:LITERAL-NORMALIZE（新設）**：`lang_profile.json.languages[*].literal_normalize[]` を**順序通り**適用（最小差分）。  
     - **POLYGLOT:SYM-IMPORT-GUARD（新設）**：`support_api` に列挙された関数/シンボル呼出しに対し、**import/using/完全修飾**を**先頭〜80行内**に自動注入（既に満たす場合は無変更）。  
     - **HTML-LS-INJECT（MUST／全プロジェクト）**：テンプレート/静的HTML/SSR出力の雛形に対し、`<!doctype html>`（小文字）／`<meta charset="utf-8">`／`<html lang="ja">` を**最小差分**で注入。既に満たす場合は無変更。対象拡張子は LANG-PROFILE の `ext[]` に基づく（例：`.php`, `.html`, `.blade.php`, `.ejs`, `.tsx` 等）。
     - **REL-HREF-BNORM-AUTO（MUST／AHR自動変換）**：内部リンクの `<a href="/...">` 直書きを禁止し、**href()/to()** 等（LANG-PROFILE の `support_api.href_fn|to_fn`）経由に**機械変換**。http(s) 外部リンクは除外。変換不能な場合は相対パスに正規化し、後段の BNORM 検査に委譲。既に規約準拠の場合は無変更。
     - **ERR-GUARD 注入（維持）**：`try{...}catch(E){ log_line(...); }` を不足箇所へ**行単位**で挿入（/health 相当、DB接続/charset適用、DDL適用、固定行 upsert 等）。  
     - **TOOLS-PRESENCE/STORAGE-PRESEED**：`tools/*` の在庫/実行権限、`storage/.keep` を自動生成。  
     - **EVIDENCE-FRESHNESS/LINT-SIG**：言語別リンタ（`lint_cmd`）を再走し、ハッシュ群と署名を再生成（出力先は `evidence/static/`）。  
     - **BNORM/EARLY-ENV**：FC（フロントコントローラ）における **/env 早期直返し**と**サブディレクトリ BNORM**の静的パッチ。  
     - **SETUP-LINK-BAN／REDIR-BNORM**：公開画面の秘匿導線リンク除去と、リダイレクトの no-store／正規化。  
     - **CONFIG-PATH-ROOT-LOCK**：設定ファイルのルート固定読込へ機械修正し、MANIFEST へ強制列挙。
  3. 修復後に **`tools/run_checks.*` を再実行**し、ラウンド結果を `evidence/gen/AHR_ROUND_*.json` に記録。
  4. 最終的に **全 Gate PASS** で `AHR_RESULT=pass`、未達なら `AHR_RESULT=defer` とし、どの NG が**自動修復対象外**だったかを `AUTO_REGEN_REPORT.json` に列挙。
- **ソースロック**：証跡（Lintログ/ハッシュ/署名 等）は**AHR から直接生成せず**、**必ず `tools/run_checks.*` を介して作成**（EVIDENCE-SOURCE-LOCK 維持）。
- **最大試行**：`AHR_MAX_ROUNDS=3`。3回で未達の場合は **D 体裁で不足項目を提示**し、ユーザーの指示を待つ（納品は保留）。

# 4) 入力（Input Contracts）
- 4仕様書の**最新版のみ**を唯一の参照元とする（旧版・他スレ参照禁止）。
- 競合時の優先順位: **仕様書 ＞ 既存生成物**。整合しない場合は仕様書で上書き。
- 環境依存の実値はすべて「環境仕様書」を参照し、本書・検査・機能からは重複削除。

# 5) 生成タスク（標準）

## 直返しエンドポイント（/env-lite, /health-lite）— MUST
- **位置と名称**  
  - リポジトリ直下に **実体ファイル**を同梱。例：`env-lite.*`、`health-lite.*`（拡張子は言語/実装方式に依存）。
  - **URL パス固定**：`/env-lite`、`/health-lite`。`.htaccess` 等で **`-f/-d` 優先の素通し**を設定し、フロントコントローラ（FC）を経由しない。
- **レスポンス仕様**  
  - 200 / `Content-Type: application/json; charset=utf-8`。
  - **最小内容**：`{"ok":true,"kind":"env-lite"}`、`{"ok":true,"kind":"health-lite"}`（追加フィールド任意）。
  - **ヘッダ**：プロジェクト既定の **`Cache-Control: no-store`** は **.htaccess 等の全体付与**で満たす（FC特有ヘッダは付けない＝*直返し*）。
- **目的／使い分け**  
  - /env-lite：監視・L7ヘルスチェッカ向けの **“静的疎通”**（アプリ初期化を伴わない）。
  - /health-lite：CDN/ロードバランサ向けの **“軽量可用性”**（DB到達やスキーマ初期化は行わない）。
  - /health（別物）：**最小ブート**（スキーマ自己確保）を実行。*データ投入は禁止*（機能仕様書準拠）。
- **納品・検査連携**  
  - `MANIFEST.json.files[]` に **`env-lite.*` / `health-lite.*`** を列挙（必須）。
  - `curl_smoke.sh` に **HEAD/GET の到達性テスト**を実装（200 + `no-store` を確認）。
  - `tools/run_checks.*` は `evidence/runtime/ENV_LITE_OK.txt` を出力可能とする（検査仕様書の対応項目と連携）。

## /health（最小ブート + HEAD 強化 + ERR-GUARD）— MUST
- **役割**：**最小ブート**（スキーマ自己確保のみ）を行い、**データ投入はしない**（機能仕様書準拠）。
- **HEAD 対応（強化）**
  - **/health への HEAD** は **GET と同等の最小ブート処理**（ERR-GUARD 付き）を実行した上で、**本文は出力せず**に **`200`** を返し、ヘッダに **`Cache-Control: no-store`** と **`Content-Type: application/json`** を**必ず**付与する。
  - **致命的エラーをヘッダ直後で発生させない**こと（検査仕様書の HEAD-LOCK 項目に整合）。
  - （注）本節に現れるコード断片は**言語例（擬似）**であり、**拘束力は LANG-PROFILE** に委譲する。

- **ERR-GUARD 詳細**：スキーマ適用ロジック（Schema::ensure 相当）は **各DDL/各固定行 upsert/各索引確保**を**行ごと**に try/catch し、**成功/失敗を L2 へ 1行JSON**で出力（`schema_table_created|failed`, `schema_index_created|exists|failed`, `fixed_row_upsert_ok|failed` 等）。**失敗しても後続DDLを極力継続**。最終的な合否は /health 側の JSON で判定。

- **納品・検査連携**
  - `curl_smoke.sh` に **HEAD /health** の到達性検証（200 + no-store + application/json）を追加。
  - `tools/run_checks.*` は **`evidence/runtime/HEALTH_BOOT_HEAD_OK.txt`** を生成できること。        
  - `tools/run_checks.*` は **ERR-GUARD 静的検査**を実施し、`evidence/static/ERR_GUARD_OK.txt` を生成、`COMPLIANCE.json.err_guard_ok=true` を設定。

- **ENV 検査補助**：`storage/logs/app.log` に `health_min_boot_pass|fail` を**必ず**記録（環境仕様書の対応要件と整合）。

# 6) 生成ガード（A〜V；本版維持）＋ **W〜Z（ポリグロット追補／新設）**
（**ポリグロット注**）A〜V は**PHPプロファイルの例示規範**として維持する。他言語プロファイルでは **LANG-PROFILE** により**等価ルール**を適用し、該当しないものは SKIP とする。

A) Url::to / Url::href 一元化（直URL記述禁止）  
B) base_url 契約の遵守（/index.php を含めないクリーンURL用）  
C) PATH_INFO 優先のルーティング（QUERY_STRING依存禁止）  
D) .htaccess 最小固定（/assets のみ直配信、他はフロントコントローラへRewrite）  
E) /setup はGETで公開（初期ブート用—機微情報の露出禁止）  
F) スモーク強化（curl_smoke.sh に /health, /setup, /health-lite, /env-lite, 代表ルートの到達性テストを実装）  
G) **エビデンス在庫ロック** — evidence/static・runtime・db・pkg 各カテゴリに **OK 証跡 ≥1**。`MANIFEST.json.files[]` に evidence/** を**全列挙**。  
H) **Pages 相対 require の禁止（Support 直接参照禁止）** — FCで一元化。  
I) **BASE_DIR 基準のパス解決を必須** — ベースパスヘルパを提供し、相対 `../..` を禁止。  
**J) EARLY-ENV-BNORM-SUBDIR（MUST）** — FC における **/env** の早期直返しは BNORM 前提。  
**K) RUN-CHECKS-STRICT-BNORM（MUST）** — BNORM 失敗は即 AHR 起動。  
**L) TOKENS-FIRST（MUST）** — （PHPプロファイル例）`*.php` は**先頭 `<?php`**。他言語は **LANG-PROFILE** に準拠。  
**M) EVIDENCE-FRESHNESS（MUST）** — `evidence/static/*` の mtime は全**ソース**の最新 mtime 以上（±120s）。  
**N) REL-HREF-BNORM-LOCK（MUST）** — リンク生成は **href()/to()** 経由。直書き禁止。  
**O) PACK-PROVENANCE-LOCK（MUST）** — **`tools/pack.*` 内に `tools/run_checks.*` 呼出が存在**。  
**P) EVIDENCE-SOURCE-LOCK（MUST）** — 証跡生成元は**`tools/run_checks.*` のみ**。AHR は直接生成しない。  
**Q) SETUP-LINK-BAN（MUST）** — 公開画面で **/provider/setup** へのアンカーリンク禁止（文言のみ可）。  
**R) SETUP-ACCESS-POLICY（MUST）** — `/provider/setup` は秘匿URL。初回GETのみ公開、POST成功後は 302→login、以降は常に 302。  
**S) SETUP-ROBOTS-NOINDEX（SHOULD）** — `/provider/setup` 全応答に `X-Robots-Tag: noindex, nofollow, noarchive`。  
**T) HEALTH-HEAD-BOOT-LOCK（MUST）** — `/health` は **HEAD** に対しても最小ブートを実施し、**200 + no-store + application/json**。  
**U) CONFIG-PATH-ROOT-LOCK（MUST）** — 設定読み込みはルート固定。`MANIFEST.json.files[]` に `config.*` を列挙。  
**V) ERR-GUARD（MUST）** — 行単位 try/catch + L2ログ。重要経路で未捕捉例外を根絶。

**W) LANG-PROFILE-REGISTRY（MUST／新設）**  
- `tools/lang_profile.json` を**必須同梱**。各言語について `ext[]`（拡張子）、`lint_cmd[]`（公式リンタ/コンパイラ）、`literal_normalize[]`（from/to 正規表現）、`support_api`（シンボル名→正規参照形）を宣言。
- `MANIFEST.json.files[]` に `tools/lang_profile.json` を**必ず列挙**。`COMPLIANCE.json.lang_profile=true` を設定。

**X) POLYGLOT-LINT-ENFORCE（MUST／新設）**  
- `tools/run_checks.*` は LANG-PROFILE を読み、**存在する拡張子**に対して **各言語の公式リンタ/コンパイラ**（`lint_cmd`）を**全ファイル**へ実行。失敗は非ゼロ終了。  
- 代表証跡：`evidence/static/LINT_<lang>.log`、`evidence/static/LINT_SIG_<lang>.txt`（順序付きハッシュ署名）。`COMPLIANCE.json.polyglot_lint_ok=true`。

**Y) SUPPORT-API-IMPORT-GUARD（MUST／新設）**  
- `support_api` に登録された**標準API**（例：`no_store_headers` 等）の呼出しは、**正しい import/using/完全修飾**でなければならない。AHR は不足時に**先頭〜80行内**へ最小差分で補完。  
- 代表証跡：`evidence/static/SUPPORT_IMPORT_OK.txt`。`COMPLIANCE.json.support_import_ok=true`。

**Y2) HTML-LS-GLOBAL-LOCK（MUST／新設）**  
- すべての出力HTML/テンプレートが **HTML LS 基礎3点**を満たすことを**言語非依存でロック**する。 `tools/run_checks.*` は言語別テンプレート拡張子に対して検査を行い、`evidence/static/HTML_LS_BASE_OK.txt` を生成。 AHR は **HTML-LS-INJECT** を適用して不足箇所を最小差分で補正する。

**Z) POLYGLOT-SMOKE-HEAD（SHOULD／新設）**  
- `curl_smoke.sh` は **HEAD/GET** の疎通検証を代表URL群に対して行い、**200 + no-store** を確認（レスポンス種別は問わない）。  
- 代表証跡：`evidence/runtime/POLYGLOT_SMOKE_OK.txt`。

# 7) データ表現ポリシー（JSON Only）—（維持）
- app/Data/ は **JSON/JSONL限定**。CSV/YAML/言語配列等は禁止。
- 金額は**1円単位**で保持・提示（丸め禁止）。
- COMPLIANCE.json に `.data_repr_json_only: true` を**必須**。

# 8) UI/言語ポリシー（維持）
- 既定言語は日本語固定。静的文言は /app/Locale/ja.json で集中管理。
- 検査互換のため、H1「Provider Dashboard」を暫定許容（その他は日本語）。
- COMPLIANCE.json に `.ui_language: "ja"` と `.no_stub_pages: true` を**必須**。
- **HTML Living Standard 準拠（MUST）**：`<!doctype html>` / `<meta charset="utf-8">` / `<html lang="ja">` を**必須**。
- （全プロジェクト共通の**必須**要件。AHR は **HTML-LS-INJECT** により不足箇所を**自動補正**し、`tools/run_checks.*` が `evidence/static/HTML_LS_BASE_OK.txt` を生成し `COMPLIANCE.json.html_ls_ok=true` を設定する。）
- **公開導線の明記**：/provider/login では **/provider/setup へのリンクは出さない**（#6 Q に準拠）。文言のみ可。

# 9) 納品（最終出力）
## 9.1) 受入条件（MUST／新設）
- **FULLのみ**を受入対象とする。**差分（diff）／パッチ（patch）／hotfix単体**での納品は**禁止**。  
  - 本書における **FULL** とは、リポジトリの**実行に必要な一式**（`app/**`, `public/**`, `config/**`, `tools/**`, `evidence/**`, `storage/.keep` などの**必須在庫**、および `MANIFEST.json`・`COMPLIANCE.json` を含む）を**完全同梱**したアーカイブを指す。
- **手動ZIP**は**禁止**。ZIP 生成は **`tools/pack.*`** を唯一の経路とし、同スクリプト内で **`tools/run_checks.*` を先行実行**して **Gate-0/1/2** の合格を確認した場合にのみ ZIP 化を許可する（**PACK-PROVENANCE-LOCK** に従う）。
- 受入可否は `COMPLIANCE.json` の下記キーで機械判定する：  
  - `.full_only_acceptance=true`  
  - `.pack_provenance_ok=true` / `.zip_ready_ok=true` / `.polyglot_lint_ok=true` / `.html_ls_ok=true` / `.err_guard_ok=true`
- **部分納品・分割納品**は**不可**（`tools/` や `evidence/` 欠落を含む）。`tools/run_checks.*` は **TOOL/PRESENCE** と **EVIDENCE/STOCK** の両検査に**不合格**となるべきで、AHR による補完が適用された場合は再検査で PASS になるまで ZIP 化を禁止する。
- **名称規約**：`<project>_full_<semver>.zip` を標準とする（例：`recept_audit_full_3.7.2.zip`）。


- 原則 **ZIPのみ**。チャットでは **「納品物: [Download ZIP](...)」** の1行のみ提示。
- 仕様書類（.txt 納品要求時）は **「納品物: [Download TXT](...)」** の1行のみ提示。
- **MANIFEST ハッシュ整合**: `evidence/gen/GEN_PROVENANCE.json.outputs.artifact_manifest_sha256` と `sha256sum MANIFEST.json` を一致確認。
- **AHR により Gate-0/1/2 が PASS** した場合のみ ZIP 提示可。**pack.* 非経由ZIPの提示は禁止**。

# 10) 検査仕様書との連携（最低充足条件＋ERR-GUARD追補＋ポリグロット追補）
- 本版追補として **HEAD 応答ロック**（/health HEAD：200 + no-store + application/json）および **CONFIG ルート固定** に準拠する。
- **A-BOOST**：/provider/setup の **HEAD/GET 無副作用**・**POST 作成→302**・**3xx no-store（301禁止）**・**Class not found 0件** に整合。
- **G-PATH**：相対 require 包括禁止 / ベースパス解決 / Support 自己完結 / HTTPクライアント cURL 統一 に整合。
- **ERR-GUARD 追加**：`tools/run_checks.*` は `catch|except` と `log_line(` の**対応関係**を静的検査し、`evidence/static/ERR_GUARD_OK.txt` を生成。`COMPLIANCE.json.err_guard_ok=true`。
- **POLYGLOT 追加**：検査仕様書の **LITERAL-MISMATCH**／**SUPPORT-IMPORT-LOCK**／**POLYGLOT-LINT**／**SMOKE-HEAD** の各項目に整合し、対応する **COMPLIANCE キー**（`literal_normalize_ok` / `support_import_ok` / `polyglot_lint_ok` 等）を **run_checks** から設定。

# 11) パーサー互換のためのヘッダ規約（維持）
- 本ファイル内で **「版数:」「更新:」「投入順:」は先頭の一度のみ**出現させる。
- **旧版の全文収載は行わない**。必要な引用はコードブロック内に収め、ヘッダ書式は含めない。
- 補助識別子として **Doc-ID: generation_spec** を先頭ブロックに含める。

# 12) エラー時の動作（AHR + ERR-GUARD）
- 仕様の欠落/競合を検知した場合は、**AHR を起動**しつつ、**ERR-GUARD により必ず L2ログを残す**（環境実値の未定義などは D 体裁で不足項目を提示）。
- **Gate-0/1/2 いずれかの NG** でも、**即停止せず** AHR が **最大3ラウンド**まで自動修復と再検査を実施。AHR 不在箇所は `AUTO_REGEN_REPORT.json` に列挙。
- **PACK-PROVENANCE-LOCK**、**EVIDENCE-SOURCE-LOCK** は従前どおり厳守（自動修復は**コード**と**宣言**に限定）。


--- spec/testing.txt ---
検査仕様書
版数: v4.8.2
P25-10-01 00:00:00 JST+0900

0) 本版について
0.2) 汎用（ポリグロット）適用について（新設）
- 本書は**汎用の“検査仕様書（FULL）”**として、言語やフレームワークに依存しないコア検査を定義する。
- 既存の PHP 依存の判定は **「PHPプロジェクトの標準アダプタ（例）」**として残し、非PHPのプロジェクトは **Polyglotアダプタ**で同等の合格条件を満たす。
- 以降の H-* で「-G（Global/Polyglot）」が付く項目は**全言語共通**の検査であり、必要に応じて `tools/langmap.json`（言語→拡張子/テンプレートパスの対応表）を参照して適用する。
- **HTML Living Standard は全プロジェクトの必須要件**である（#6 H-23G および 6.x 参照）。

- 本版は v4.7.2_FULL の章番号ゆらぎ（例: 「6) 配下に 
0.1) …」など）を整理し、**見出しを再配置・再番号付け**した単一の “FULL” 版です。
- H-項目内の誤った小見出し表記（`n.m)` 形式）は **箇条（—）表記**へ正規化しています（本文の要件・手順は不変）。
- 依存コマンドは **PHP のみ**（php -l / hash() / json_* / cURL / ZipArchive）。DB が未到達の検査は **SKIP（非REJECT）** 方針を維持します。

1) 目的
1.2) Polyglot 方針（新設）
- すべての検査は「**言語非依存の合格条件**」→「**各言語アダプタの実装例**」の順で記述する。
- `tools/run_checks.sh` は **Polyglot ランナー**を必ず呼び出す（H-20G）。
- 生成仕様書（v3.7.x）との整合により、**ERR-GUARD** と **HTML-LS-GLOBAL** の結果は `COMPLIANCE.json` にも反映する（`.err_guard_ok = true`, `.html_ls_global_ok = true`）。

1.1) 方針
**H-25 PACK-PROVENANCE-LOCK**（pack.sh 経由以外の ZIP を遮断）と
  **H-26 LINT-REPLAY-VERIFY**（受入側での `php -l` 再実行）を新設する。
- 目的：**手動ZIPや手詰め証跡**を Fail-Closed で遮断し、Lint 実行の**再現性**を保証する。
**H-24 SOT-SCHEMA-COVERAGE**（SOT に列挙されたスキーマの**静的＋動的カバレッジ**）を新設する。
- 目的：**/health＝最小ブート**の解釈による部分実装を防ぎ、**付録S（SOT）**のテーブル/インデックスを
  **14/14 で確保**していない生成物を**Fail-Closed**にする。依存コマンドは PHP のみ。
- **H‑21 ENV-200-LOCK-001（新設 / Boot）** … **/env および /env/ の実働検証**をゲート化（200 + `Cache-Control: no-store` を**強制**）。
- 目的：**名前空間関数の未インポート等により FC 直後で Fatal する不具合**を、静的検査をすり抜ける形で発生しても**Boot段で確実に検出**する。
- **H-32 SETUP-LINK-BAN-001（新設 / Static）** … 公開導線（特に **/provider/login**）から **/provider/setup** への**アンカーリンク**を**禁止**し、文言のみを許容する。

- **H‑21H HEALTH-BOOT-HEAD-LOCK-001（新設 / Boot）** … **/health への HEAD リクエスト**で **200 + `Cache-Control: no-store` + `Content-Type: application/json`** を**強制**し、最小ブート時の**ヘッダ出力直後の致命的エラー**を確実に検出する。
- **H‑31 CONFIG-PATH-ROOT-LOCK-001（新設 / Static）** … **`config.php` の参照をリポジトリルート固定**とし、**`app/config.php` の誤参照**を**Fail‑Closed**で遮断する（MANIFEST 列挙 & 静的検査）。
2) 適用範囲
2.5) Polyglot 実行基盤（新設）
— 安定ランナー要件と最小スケルトン（新設）
```bash
#!/usr/bin/env bash
# tools/run_checks.sh (安定版スケルトン)
# 要件: /dev/fd やプロセス置換を使わず、証跡は evidence/** にのみ出力する
set -Eeuo pipefail
IFS=$'\n\t'
export LC_ALL=C LANG=C

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
mkdir -p "$ROOT/evidence/static" "$ROOT/evidence/runtime" "$ROOT/evidence/pkg" "$ROOT/evidence/verify"

TMPDIR="$(mktemp -d)"; trap 'rm -rf "$TMPDIR"' EXIT

# 1) Polyglot Lint（H-20G）
bash "$ROOT/tools/polyglot_lint.sh" 1>"$ROOT/evidence/static/POLYGLOT_LINT.log" 2>"$ROOT/evidence/static/POLYGLOT_LINT.err" || {
  echo "NG: polyglot lint failed"; exit 1; }

# 2) HTML-LS（H-23G / H-50）
bash "$ROOT/tools/render_smoke.sh" >"$TMPDIR/out.html" 2>"$ROOT/evidence/static/RENDER_SMOKE.err" || {
  echo "NG: render smoke failed"; exit 1; }
grep -Eiq '^<!doctype html>' "$TMPDIR/out.html" || { echo "NG: HTML LS baseline not satisfied for <!doctype html>"; exit 1; }
grep -Eiq '<meta\s+charset="utf-8">' "$TMPDIR/out.html" || { echo "NG: HTML LS baseline not satisfied for <meta charset>"; exit 1; }
grep -Eiq '<html\s+[^>]*lang=' "$TMPDIR/out.html" || { echo "NG: HTML LS baseline not satisfied for <html lang=>"; exit 1; }
: > "$ROOT/evidence/static/HTML_LS_GLOBAL_OK.txt"

# 3) VERIFY_REPORT（新設）
bash "$ROOT/tools/verify_report.sh" || { echo "NG: verify_report failed"; exit 1; }

# すべて合格した場合は**何も出力しない**で0終了
exit 0
```

- 代表ファイル: `tools/polyglot_lint.sh`, `tools/langmap.json`, `tools/render_smoke.sh`
- **langmap.json（例）**
```json
{
  "php":    {"ext": ["php"],        "templates": ["app/Pages","templates","resources/views"]},
  "html":   {"ext": ["html","htm"], "templates": ["public","templates"]},
  "twig":   {"ext": ["twig"]},
  "blade":  {"ext": ["blade.php"]},
  "ejs":    {"ext": ["ejs"]},
  "hbs":    {"ext": ["hbs","handlebars"]},
  "vue":    {"ext": ["vue"]},
  "jsx":    {"ext": ["jsx","tsx"]}
}
```
- `polyglot_lint.sh` は `langmap.json` を読み、検出された拡張子ごとに **実リンタ/構文検証** を実行する。実リンタが無い言語は**最小構文チェック**または**スキップ（SKIP: 非REJECT）**を記録。
- すべての結果は `evidence/static/lint/<lang>_*.log` と `evidence/static/POLYGLOT_LINT_OK.txt` に要約し、`COMPLIANCE.json.polyglot_lint_ok=true` を設定する。


- **VERIFY_REPORT v1（新設 / 集約サマリ）**
  - 代表ファイル: `tools/verify_report.sh`（新設）, 出力: `evidence/verify/VERIFY_REPORT.json`, 成否: `evidence/verify/VERIFY_REPORT_OK.txt`
  - 役割: 検査証跡（evidence/**, COMPLIANCE.json, MANIFEST.json）を集約し、**VERIFY_REPORT v1** を生成する。
  - 実行位置: `tools/run_checks.sh` の **最終行**で必ず呼び出す（DONE/ZIP ゲート前）。
  - 実行結果: `COMPLIANCE.json.verify_report_ok=true` を設定する。
  - 参照実装（PHP 依存のみ / 例）
  ```bash
  # tools/verify_report.sh（抜粋）
  php -r '
  function read_json($p){return file_exists($p)?json_decode(file_get_contents($p),true):null;}
  $comp = read_json("COMPLIANCE.json") ?: [];
  $mani = read_json("MANIFEST.json")  ?: [];
  $proof = [
    "phplint" => [file_exists("evidence/static/PHPLINT.log"), file_exists("evidence/static/PHPLINT_FINAL.log")],
    "html_ls" => [file_exists("evidence/static/HTML_LS_GLOBAL_OK.txt") || file_exists("evidence/static/HTML_LS_BASE_OK.txt")],
    "err_guard" => [file_exists("evidence/static/ERR_GUARD_OK.txt")],
    "polyglot_lint" => [file_exists("evidence/static/POLYGLOT_LINT_OK.txt")],
    "pack_provenance" => [file_exists("evidence/static/PACK_PROVENANCE_OK.txt")],
  ];
  $out = [
    "version"=>"VERIFY_REPORT/v1",
    "timestamp"=>date("c"),
    "summary"=>[
      "ok" => (bool)($comp["zip_ready_ok"] ?? false),
      "phase_S_ok" => true,
      "phase_B_ok" => (bool)($comp["health_boot_head_ok"] ?? false),
      "phase_D_ok" => (bool)($comp["health_minboot_data_ok"] ?? false) || (($comp["phase_d_skip"] ?? false) === true)
    ],
    "compliance"=>$comp,
    "manifest"=>$mani,
    "evidence_present"=>$proof
  ];
  file_put_contents("evidence/verify/VERIFY_REPORT.json", json_encode($out, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));
  ' && echo "OK:" > evidence/verify/VERIFY_REPORT_OK.txt
  php -r '$_=json_decode(file_get_contents("COMPLIANCE.json"),true); $_["verify_report_ok"]=true; file_put_contents("COMPLIANCE.json",json_encode($_,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));'
  ```

2.1) 終了
kill $SERVER_PID >/dev/null 2>&1 || true
```

**【既知不具合と本検査の意義】**
- 事例：`app.php` 先頭で `no_store_headers()` を **未インポートのまま**呼出 → **Fatal error: undefined function** → **/env が 500** ＆ **アプリログ不出力**。
- H‑21 は**実アクセスで 200/no-store を強制**するため、上記のような**静的検査すり抜け**の不具合を Boot 段で**Fail‑Closed**にできる。
H-22 ABS-HREF-BAN-001（**新設 / 静的：絶対パスhrefの禁止**）
**要件:** `app/Pages/**` における **アンカー `<a>` の `href="/..."` 直書きを禁止**（外部 `http(s)://` を除く）。
**受入判定（例 / 依存は PHP のみ）**
```bash
直書き絶対パスhrefを検出（外部は対象外）
if grep -RIn --include="*.php" -E '<a\s+[^>]*href="/' app/Pages >/dev/null 2>&1; then
  echo "NG: absolute href found"; exit 1
fi
echo "OK:" > evidence/static/ABS_HREF_BAN_OK.txt
php -r '$_=json_decode(file_get_contents("COMPLIANCE.json"),true); $_["abs_href_ban_ok"]=true; file_put_contents("COMPLIANCE.json",json_encode($_,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));'
```
H-23 HTML-LS-BASELINE-001（**新設 / 静的：HTML Living Standard ベースライン**）
**要件:** 出力HTMLは **HTML Living Standard** の基礎要件に適合し、少なくとも以下を満たす。
- 文書先頭に **`<!doctype html>`（小文字）**。
- `<meta charset="utf-8">` が含まれる。
- ルート要素に **`<html lang="ja">`** を含む。

**受入判定（例 / 依存は PHP のみ）**
```bash
ベースライン3点を満たすテンプレートが存在（レイアウト/共通テンプレ推奨）
grep -RIni --include="*.php" "<!doctype html>" app/Pages >/dev/null 2>&1 || exit 1
grep -RIni --include="*.php" "<meta\s\+charset=\"utf-8\"" app/Pages >/dev/null 2>&1 || exit 1
grep -RIni --include="*.php" "<html\s[^>]*lang=\"ja" app/Pages >/dev/null 2>&1 || exit 1
echo "OK:" > evidence/static/HTML_LS_BASE_OK.txt
php -r '$_=json_decode(file_get_contents("COMPLIANCE.json"),true); $_["html_ls_ok"]=true; file_put_contents("COMPLIANCE.json",json_encode($_,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));'
```
2.2) 方針
— 安定検査の指針（新設）
- **禁止:** `/dev/fd/*` 参照、プロセス置換 `<( )`、Here-String（`<<<`）など **実行環境に依存するI/O**。すべて**明示ファイル**／**標準的なパイプ**で代替する（例: `mktemp` で一時ファイルを確保し `trap` で確実に削除）。
- **シェル要件:** ランナー（`tools/run_checks.sh` 等）は **POSIX シェル互換**で動くこと。`bash` を前提にする場合は**先頭で** `set -Eeuo pipefail`、POSIX `/bin/sh` の場合は `set -eu` ＋ **パイプ末尾の明示判定**で代替。
- **ロケール固定:** `export LC_ALL=C LANG=C` を**先頭で強制**し、`grep/sort/awk` の結果を**決定的**にする。
- **ファイル名安全:** 走査は `find ... -print0 | xargs -0` または `while IFS= read -r -d '' f; do ...; done < <(find ... -print0)` を用い、**空白/改行/非ASCII** を含むパスでも安定に処理。
- **grep要件:** `-P` など拡張依存は原則禁止。**POSIX ERE** で記述し、**検索対象を明示**（`--include`）して誤検出を防止。
- **標準出力の静粛性:** すべての検査が合格した場合、**標準出力には何も出さない**（ゼロ終端）。合格の証跡は `evidence/**` の **OKファイル**／ログの **ファイル出力のみ**で示す。失敗時のみ `NG:` を標準出力に1行出し**非0終了**。
- **ログ分離:** 説明的なメッセージやデバッグは**標準エラー**へ。機械判定に関わる出力と**混在禁止**。
- **エビデンスのディレクトリ:** `evidence/static`, `evidence/runtime`, `evidence/pkg`, `evidence/verify` を**冒頭で必ず作成**（`mkdir -p`）。ディレクトリ欠落自体を**検査NG**とする。
- **一時ファイル:** `TMPDIR="$(mktemp -d)"` を用い、`trap 'rm -rf "$TMPDIR"' EXIT` で**確実に回収**。
- **終了コード契約:** Gate通過 = 0、失敗 = 非0。**途中の `|| true` で握りつぶさない**。

- さらに、現場で顕在化した欠陥（`tools/` 欠落、`storage/` 欠落、`<?php` 二重、疑似Lint）を**事前に遮断**するため、以下の Add‑ons（H‑17〜H‑20, H‑13a）を導入・明確化する。
- **H‑17b STORAGE-PRESEED-PACKAGE-001（新設）**（storage/.keep 同梱と MANIFEST 列挙をロック）
- **H‑17 STORAGE-BASE-WRITABLE-001**（storage/logs の作成と書込検証）
- **H‑18 PHP-OPENING-TAG-ONCE-001**（冒頭1KB内の `<?php` 重複検出）
- **H‑19 TOOLS-PRESENCE-LOCK-001**（`tools/run_checks.sh`/`tools/pack.sh` の存在＋実行権限）
- **H‑20 PHPLINT-REAL-ENFORCE-LOCK-001（新設）** … `tools/run_checks.sh` に **実際の `php -l` 実行が含まれることをロック**（疑似Lintを禁止）。
- **H‑13a PHPLINT-COVERAGE-COUNT-001（新設）** … **Lint 対象件数＝ログ行数**を検証（“全 *.php に `php -l` を回した”ことの数量的証跡）。
- 依存コマンドは **PHP のみ**で再現可能。Fail‑Closed 原則は維持。
2.3) 新規・強化検査
H-12 TOKENS-FIRST-001（新設 / 静的：先頭トークンロック）
**要件:** 全 `*.php` の**先頭5バイト**が `<?php`。BOM/空白/余計な文字を禁止。
**受入判定（例）**
```bash
php -r '
$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator("."));
foreach($it as $f){ if(substr($f,-4)!==".php") continue;
  $h=file_get_contents($f,false,null,0,5); if($h!=="<?php") die("NG:$f\n");
}
echo "OK";
' | grep -q OK && echo "OK:" > evidence/static/PHP_OPENING_TAG_OK.txt
php -r '$_=json_decode(file_get_contents("COMPLIANCE.json"),true); $_["tokens_first_ok"]=true; file_put_contents("COMPLIANCE.json",json_encode($_,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));'
```
H-13 LINT-PROOF-OF-WORK-001（新設 / 静的：実行証跡の署名）
**要件:** `php -l` の**実実行**を署名で証明する。
`LINT_SIG = sha256( PHP_VERSION + "\n" + sorted_join( each: "path:sha256\n" for all *.php ) )`
**受入判定（例）**
```bash
php -r '
$h=json_decode(file_get_contents("evidence/static/PHPLINT_HASHES.json"),true);
usort($h, fn($a,$b)=>strcmp($a["file"],$b["file"]));
$s=PHP_VERSION."\n"; foreach($h as $r){ $s.=$r["file"].":".$r["sha256"]."\n"; }
$l=hash("sha256",$s); file_put_contents("evidence/static/LINT_SIG.txt",$l);
$g=json_decode(file_get_contents("evidence/pkg/GATE_SIG.json"),true);
$g["lint_sig"]=$l; file_put_contents("evidence/pkg/GATE_SIG.json",json_encode($g,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));
' && echo "OK:" > evidence/static/LINT_SIG_OK.txt
php -r '$_=json_decode(file_get_contents("COMPLIANCE.json"),true); $_["lint_sig_ok"]=true; file_put_contents("COMPLIANCE.json",json_encode($_,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));'
```
H-13a PHPLINT-COVERAGE-COUNT-001（**新設 / 静的：カバレッジ数ロック**）
**要件:** **Lint 対象の `*.php` 件数 = Lint ログ行数（`php -l` 実行回数）**であることを検証し、数量的証跡を残す。
**受入判定（例）**
```bash
H-1/H-2 の実行で PHPLINT.log / PHPLINT_FINAL.log を生成している前提
2.4) 走査対象件数
N=$(find . -type f -name "*.php" | wc -l | awk '{print $1}')

3) 用語・定義

- （該当項目なし）

4) 参照文書
4.8) MANIFEST 追記（Polyglot 追加）
- `MANIFEST.json.files[]` に **以下を必ず列挙**：
  - `tools/polyglot_lint.sh`
  - `tools/langmap.json`
  - `tools/render_smoke.sh`
  - `evidence/static/POLYGLOT_LINT_OK.txt`
  - `evidence/static/HTML_LS_GLOBAL_OK.txt`
  - `evidence/static/ERR_GUARD_OK.txt`
  - `tools/verify_report.sh`
  - `evidence/verify/VERIFY_REPORT.json`
- 受入判定（例 / POSIX シェル）
  - `tools/lang_profile.json`（新設 / 言語判定の補助辞書）
  - `evidence/static/` 内の OK ファイル群（`*_OK.txt`）の**代表**を少なくとも1つ以上
  - `evidence/verify/VERIFY_REPORT.json`
```bash
php -r '
$m=json_decode(file_get_contents("MANIFEST.json"),true);
$need=[ "tools/polyglot_lint.sh","tools/langmap.json","tools/render_smoke.sh", "evidence/static/POLYGLOT_LINT_OK.txt","evidence/static/HTML_LS_GLOBAL_OK.txt","evidence/static/ERR_GUARD_OK.txt","tools/verify_report.sh","evidence/verify/VERIFY_REPORT.json" ];
$miss=array_values(array_diff($need,$m["files"]??[])); if($miss){fwrite(STDERR,"NG:".implode(",",$miss)."\n"); exit(1);} echo "OK";
' | grep -q OK && echo "OK:" > evidence/static/POLYGLOT_MANIFEST_OK.txt
php -r '$_=json_decode(file_get_contents("COMPLIANCE.json"),true); $_["polyglot_manifest_ok"]=true; file_put_contents("COMPLIANCE.json",json_encode($_,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));'
```

4.1) `MANIFEST.json.files[]` に
`tools/run_checks.sh` / `tools/pack.sh` / `evidence/pkg/ZIP_READY_OK.txt` / `evidence/pkg/BUILD_LOG.txt` を **全列挙**。

**受入判定（例 / PHPのみ）**
```bash
test -x tools/pack.sh -a -x tools/run_checks.sh || exit 1
grep -q "tools/run_checks.sh" tools/pack.sh      || exit 1
grep -q "PACK_BY=pack.sh" evidence/pkg/BUILD_LOG.txt || exit 1
php -r '
$m=json_decode(file_get_contents("MANIFEST.json"),true);
$need=["tools/run_checks.sh","tools/pack.sh","evidence/pkg/ZIP_READY_OK.txt","evidence/pkg/BUILD_LOG.txt"];
$miss=array_values(array_diff($need,$m["files"])); if($miss) exit(1); echo "OK";
' | grep -q OK && echo "OK:" > evidence/static/PACK_PROVENANCE_OK.txt
php -r '$_=json_decode(file_get_contents("COMPLIANCE.json"),true); $_["pack_provenance_ok"]=true; file_put_contents("COMPLIANCE.json",json_encode($_,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));'
```
H-26 LINT-REPLAY-VERIFY-001（**新設 / 静的＋実行：受入側で再Lint**）
**要件:** 受入側（検収環境）で `evidence/static/PHPLINT_HASHES.json` に列挙された**全 `*.php`** へ
**`php -l` を再実行**し、**失敗 0** を確認する（DB不要／PHPのみ依存）。

**受入判定（例 / PHPのみ）**
```bash
php -r '
$h=json_decode(file_get_contents("evidence/static/PHPLINT_HASHES.json"),true);
$fail=0; foreach($h as $r){ $f=$r["file"]; $o=[]; exec("php -l ".escapeshellarg($f),$o,$rc); if($rc!==0){ $fail=1; break; } }
if($fail) exit(1); echo "OK";
' | grep -q OK && echo "OK:" > evidence/static/LINT_REPLAY_OK.txt
```
4.2) MANIFEST 契約
- `evidence/static/SETUP_LINK_BAN_OK.txt`
- `evidence/runtime/HEALTH_BOOT_HEAD_OK.txt`
- `evidence/static/CONFIG_PATH_ROOT_OK.txt`

`MANIFEST.json.files[]` に、下記**も必ず列挙**：
- `evidence/pkg/ZIP_READY_OK.txt`
- `evidence/pkg/BUILD_LOG.txt`
- （参考）`evidence/static/PACK_PROVENANCE_OK.txt` / `evidence/static/LINT_REPLAY_OK.txt`
4.3) 新規・強化検査
H-24a SOT-SCHEMA-STATIC-COVERAGE-001（**新設 / 静的：SOT→ソース網羅**）
**要件:** `app/SOT/schema.required.json`（**SOT**）に列挙された
**tables[]/indexes[]** が、少なくとも **`app/Support/Schema.php` または `schema.sql`** のいずれかに
**宣言（CREATE/ensure_index 記述）**として**全件出現**していること。

**受入判定（例 / 依存は PHP のみ）**
```bash
php -r '
$s=json_decode(file_get_contents("app/SOT/schema.required.json"),true);
$src=@file_get_contents("app/Support/Schema.php");
$src .= "\n".(@file_exists("schema.sql")?file_get_contents("schema.sql"):"");
$missT=[]; foreach($s["tables"] as $t){
  $pat="/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+`?".$t."`?/i";
  if(!preg_match($pat,$src)) $missT[]=$t;
}
$missI=[]; foreach($s["indexes"] as $ix){
  $name=$ix["name"];
  if(stripos($src,$name)===false) $missI[]=$name;
}
if($missT||$missI){
  if($missT) fwrite(STDERR,"NG:tables=".implode(",",$missT)."\n");
  if($missI) fwrite(STDERR,"NG:indexes=".implode(",",$missI)."\n");
  exit(1);
}
echo "OK";
' | grep -q OK && echo "OK:" > evidence/static/SOT_SCHEMA_COVERAGE_OK.txt
php -r '$_=json_decode(file_get_contents("COMPLIANCE.json"),true); $_["sot_schema_coverage_ok"]=true; file_put_contents("COMPLIANCE.json",json_encode($_,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));'
```

**備考:** インデックスは **SOT の name 一致**で検出。MySQL の「`CREATE INDEX IF NOT EXISTS` 禁止」ポリシーは
実装側で **`information_schema.STATISTICS` による存在確認 → `CREATE INDEX`** の二段を徹底する（環境仕様書準拠）。
H-24b SOT-SCHEMA-DYNAMIC-COVERAGE-001（**新設 / Dynamic：SOT→実体網羅**）
**要件:** `/health` 実行（または `Schema::ensure()` 呼出）**後**、
**information_schema** で **SOT の tables[]/indexes[] が全件存在**する。DB 非到達時は **SKIP（非REJECT）**。

**受入判定（例 / 依存は PHP のみ）**
```bash
DB 到達可能な場合に実施（不可なら SKIP）
php -r '
function main(){
  require "app/Support/DB.php";
  require "app/Support/Schema.php";
  $s=json_decode(file_get_contents("app/SOT/schema.required.json"),true);
  \App\Support\Schema::ensure();  # 最小ブート
  $pdo=\App\Support\DB::pdo();
  $missT=[]; foreach($s["tables"] as $t){
    $st=$pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?");
    $st->execute([$t]); if(!$st->fetchColumn()) $missT[]=$t;
  }
  $missI=[]; foreach($s["indexes"] as $ix){
    $st=$pdo->prepare("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name=? AND index_name=?");
    $st->execute([$ix["table"],$ix["name"]]); if(!$st->fetchColumn()) $missI[]=$ix["name"];
  }
  if($missT||$missI){
    if($missT) fwrite(STDERR,"NG:tables=".implode(",",$missT)."\n");
    if($missI) fwrite(STDERR,"NG:indexes=".implode(",",$missI)."\n");
    exit(2);
  }
  echo "OK";
}
try{ main(); }catch(Throwable $e){ fwrite(STDOUT,"SKIP:DYNAMIC\n"); exit(0); }
' | grep -q OK && echo "OK:" > evidence/runtime/SOT_SCHEMA_DYNAMIC_OK.txt || true
```
4.4) 新規・強化検査
H-21 ENV-200-LOCK-001（**新設 / Boot：/env 早期直返しの実働検証**）

H-21H HEALTH-BOOT-HEAD-LOCK-001（新設 / Boot：/health の HEAD 応答ロック）
**要件:** **/health** が **HEAD** リクエストに対して **200** を返し、**`Cache-Control: no-store`** と **`Content-Type: application/json`** を**必ず含む**。  
**意義:** 初期ヘッダ付与後に発生する**致命的エラー**や**FC読み込み順の不整合**を、/env だけでなく **/health** でも**Fail‑Closed**に検知する。

**受入判定（例 / PHPのみ）**
```bash
PHP=$(command -v php); PORT=9083
($PHP -S 127.0.0.1:$PORT -t . > /dev/null 2>&1) &
SERVER_PID=$!; sleep 0.5
php -r '
function head_req($u){
  $ch=curl_init($u);
  curl_setopt_array($ch,[CURLOPT_NOBODY=>true,CURLOPT_HEADER=>true,CURLOPT_RETURNTRANSFER=>true]);
  $h=curl_exec($ch); $code=curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
  if($code!==200) exit(1);
  if(stripos($h,"Cache-Control: no-store")===false) exit(1);
  if(stripos($h,"Content-Type: application/json")===false) exit(1);
  echo "OK";
}
head_req("http://127.0.0.1:$PORT/health");
' | grep -q OK && echo "OK:" > evidence/runtime/HEALTH_BOOT_HEAD_OK.txt || true
kill $SERVER_PID >/dev/null 2>&1 || true
php -r '$_=json_decode(file_get_contents("COMPLIANCE.json"),true); $_["health_boot_head_ok"]=true; file_put_contents("COMPLIANCE.json",json_encode($_,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));' || true
```
**要件:** **/env および /env/** が **200** で **JSON** を返し、**`Cache-Control: no-store`** を必ず含む。
**備考:** 初期ヘッダ付与直後の**致命的エラー（例：`no_store_headers()` の未インポート呼び出し）**を確実に検出するための**実行時ゲート**。

**受入判定（例 / 依存は PHP のみ）**
```bash
4.5) php 内蔵サーバで起動
PHP=$(command -v php)
PORT=9080
($PHP -S 127.0.0.1:$PORT -t . > /dev/null 2>&1) &
SERVER_PID=$!
sleep 0.5
4.6) MANIFEST との一致
php -r '
$m=json_decode(file_get_contents("MANIFEST.json"),true);
$f=$m["files"]; $need=["storage/.keep","storage/logs/.keep"];
$miss=array_values(array_diff($need,$f));
if(!empty($miss)) exit(1); echo "OK";
' && echo "OK:" > evidence/static/STORAGE_PRESEED_OK.txt
```
H-18 PHP-OPENING-TAG-ONCE-001（新設 / 静的：冒頭1KBの `<?php` 重複検出）
**要件:** 各 `*.php` の**冒頭1KB以内**に 2回目以降の `<?php` が存在しないこと。
**受入判定（例）**
```bash
php -r '
$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator("."));
foreach($it as $f){ $p="".$f; if(substr($p,-4)!==".php") continue;
  $h=file_get_contents($p,false,null,0,1024); if(substr_count($h,"<?php")>1) die("NG:$p\n");
}
echo "OK";
' | grep -q OK && echo "OK:" > evidence/static/PHP_OPENING_TAG_ONCE_OK.txt
```
H-19 TOOLS-PRESENCE-LOCK-001（新設 / 静的：tools 在庫＋実行権限）
**要件:** `tools/run_checks.sh` と `tools/pack.sh` が**存在し、実行権限**を持つ。`pack.sh` は内部で **`run_checks.sh` を必ず実行**してから ZIP を生成する。
**受入判定（例）**
```bash
test -x tools/run_checks.sh -a -x tools/pack.sh || exit 1
grep -q "tools/run_checks.sh" tools/pack.sh || exit 1
echo "OK:" > evidence/static/TOOLS_OK.txt
php -r '$_=json_decode(file_get_contents("COMPLIANCE.json"),true); $_["tools_presence_ok"]=true; file_put_contents("COMPLIANCE.json",json_encode($_,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));'
```
H-20 PHPLINT-REAL-ENFORCE-LOCK-001（**新設 / 静的：疑似Lint禁止のロック**）
**要件:** `tools/run_checks.sh` が **実際の `php -l`** を全 `*.php` に対して実行していることを**ソース上でロック**する（`grep -q 'php -l'` 等）。疑似Lint（先頭トークン確認や括弧数カウント等）での代替を**禁止**。
**受入判定（例）**
```bash
grep -RIn "php -l" tools/run_checks.sh | wc -l | php -r '$n=(int)trim(stream_get_contents(STDIN)); if($n===0) exit(1); echo "OK";' && echo "OK:" > evidence/static/PHPLINT_REAL_ENFORCE_OK.txt
```

H-31 CONFIG-PATH-ROOT-LOCK-001（新設 / 静的：config.php 参照パスをルート固定）
**要件:** `config.php` は **{BASE}/config.php** に配置し、**`app/config.php` の参照を禁止**する。アプリからの読み込みは **`base_path('config.php')`** または **`BASE_DIR.'/config.php'`** のみを許可とする。さらに **MANIFEST.json.files[]** に `config.php` を**必ず列挙**する。

**受入判定（例 / PHPのみ）**
```bash
# 1) MANIFEST に config.php が列挙されている
# 2) ソースに "app/config.php" の誤参照が 0 件
# 3) 読み込みは base_path('config.php') または BASE_DIR.'/config.php' を使用（少なくとも 1 箇所で検出）
php -r '
$m=json_decode(file_get_contents("MANIFEST.json"),true);
if(!in_array("config.php",$m["files"]??[])) exit(1);
$bad=0;$allow=0;
$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator("."));
foreach($it as $f){
  $p="".$f; if(substr($p,-4)!==".php") continue;
  $src=file_get_contents($p);
  if(strpos($src,"app/config.php")!==false){ $bad=1; break; }
  if(preg_match("/require(_once)?\s*\(\s*base_path\(\s*[\"\']config\.php[\"\']\s*\)\s*\)\s*;/",$src) ||
     preg_match("/require(_once)?\s*\(\s*BASE_DIR\s*\.\s*[\"\']\/config\.php[\"\']\s*\)\s*;/",$src)){
     $allow=1;
  }
}
if($bad) exit(2);
if(!$allow) exit(3);
echo "OK";
' | grep -q OK && echo "OK:" > evidence/static/CONFIG_PATH_ROOT_OK.txt
php -r '$_=json_decode(file_get_contents("COMPLIANCE.json"),true); $_["config_path_root_ok"]=true; file_put_contents("COMPLIANCE.json",json_encode($_,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));'
```

（参考）H-1/H-2：Lint 実行の正準手順（再掲・強調）
**H-1 PHPLINT-ALL-001（初回）**
```bash
mkdir -p evidence/static
: > evidence/static/PHPLINT.log
FAIL=0
while IFS= read -r -d "" f; do php -l "$f" >> evidence/static/PHPLINT.log 2>&1 || FAIL=1; done < <(find . -type f -name "*.php" -print0)
[ $FAIL -eq 0 ] || { echo "NG: PHPLINT FAIL"; exit 1; }
echo "OK:" > evidence/static/PHPLINT_OK.txt
```
**H-2 PHPLINT-FINAL-LOCK-002（ZIP直前）**
```bash
: > evidence/static/PHPLINT_FINAL.log
echo "[]" > evidence/static/PHPLINT_HASHES.json
FAIL=0
while IFS= read -r -d "" f; do
  php -l "$f" >> evidence/static/PHPLINT_FINAL.log 2>&1 || FAIL=1
  php -r '[$f]=[$argv[1]]; $h=hash("sha256",file_get_contents($f)); $j=json_decode(file_get_contents("evidence/static/PHPLINT_HASHES.json"),true); $j[]=["file"=>$f,"sha256"=>$h]; file_put_contents("evidence/static/PHPLINT_HASHES.json",json_encode($j,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));' "$f"
done < <(find . -type f -name "*.php" -print0)
[ $FAIL -eq 0 ] || { echo "NG: PHPLINT FINAL FAIL"; exit 1; }
echo "OK:" > evidence/static/PHPLINT_FINAL_OK.txt
```
4.7) 方針
- 本書は v4.3.0_HARDENED を**完全包含**し、その上に **BNORM 相対化の検査強化**と **/env の早期直返しロック**等の統合（v4.3.1）を追加したものです。
- 依存コマンドは **PHP のみ**で再現可能です。Fail-Closed 原則は維持します。


— `MANIFEST.json.files[]` に
H-12 TOKENS-FIRST-001（新設 / 静的：先頭トークンロック）
**要件:** 全 `*.php` の**先頭5バイト**が `<?php`。BOM/空白/余計な文字を禁止。
foreach($it as $f){ if(substr($f,-4)!==".php") continue;
  $h=file_get_contents($f,false,null,0,5); if($h!=="<?php") die("NG:$f\n");
' | grep -q OK && echo "OK:" > evidence/static/PHP_OPENING_TAG_OK.txt
php -r '$_=json_decode(file_get_contents("COMPLIANCE.json"),true); $_["tokens_first_ok"]=true; file_put_contents("COMPLIANCE.json",json_encode($_,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));'
H-13 LINT-PROOF-OF-WORK-001（新設 / 静的：実行証跡の署名）
**要件:** `php -l` の**実実行**を署名で証明する。
`LINT_SIG = sha256( PHP_VERSION + "\n" + sorted_join( each: "path:sha256\n" for all *.php ) )`
usort($h, fn($a,$b)=>strcmp($a["file"],$b["file"]));
$s=PHP_VERSION."\n"; foreach($h as $r){ $s.=$r["file"].":".$r["sha256"]."\n"; }
$l=hash("sha256",$s); file_put_contents("evidence/static/LINT_SIG.txt",$l);
$g=json_decode(file_get_contents("evidence/pkg/GATE_SIG.json"),true);
$g["lint_sig"]=$l; file_put_contents("evidence/pkg/GATE_SIG.json",json_encode($g,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));
' && echo "OK:" > evidence/static/LINT_SIG_OK.txt
php -r '$_=json_decode(file_get_contents("COMPLIANCE.json"),true); $_["lint_sig_ok"]=true; file_put_contents("COMPLIANCE.json",json_encode($_,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));'
H-13a PHPLINT-COVERAGE-COUNT-001（**新設 / 静的：カバレッジ数ロック**）
**要件:** **Lint 対象の `*.php` 件数 = Lint ログ行数（`php -l` 実行回数）**であることを検証し、数量的証跡を残す。
H-14 EVIDENCE-FRESHNESS-001（新設 / 静的：証跡鮮度ロック）
**要件:** `evidence/static/*` の **mtime ≥ 全 `*.php` の最新 mtime（±120s 許容）**。
$latest=0;$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator("."));
foreach($it as $f){ if(substr($f,-4)==".php"){ $t=filemtime($f); if($t>$latest)$latest=$t; } }
$ok=true; foreach(glob("evidence/static/*") as $e){ if(filemtime($e)+120 < $latest) {$ok=false; break;} }
if(!$ok) exit(1); echo "OK";
' | grep -q OK && echo "OK:" > evidence/static/EVIDENCE_FRESH_OK.txt
php -r '$_=json_decode(file_get_contents("COMPLIANCE.json"),true); $_["evidence_fresh_ok"]=true; file_put_contents("COMPLIANCE.json",json_encode($_,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));'
H-15 BUILD-GATE-ZIP-READY-SRC-LOCK-001（新設 / 静的：フラグ設定源の一意化）
**要件:** `COMPLIANCE.json.build_gate_zip_ready=true` は **`tools/run_checks.sh` 内**でのみ設定可。
grep -RIn "build_gate_zip_ready" -- * | grep -v -E "tools/run_checks.sh" | wc -l  | php -r '$n=(int)trim(stream_get_contents(STDIN)); if($n!==0) exit(1); echo "OK";'  && echo "OK:" > evidence/static/BUILD_GATE_SRC_LOCK_OK.txt
H-16 DEPLOY-BEFORE-GO-LIVE-001（新設 / 運用ゲート）
**要件:** 本番切替（Go-Live）は **H-5（配備後：PHPLINT/MANIFEST/HASH 一致）**が **PASS** するまで禁止。
**受入判定:** `evidence/deploy/DEPLOY_PHPLINT_OK.txt` / `DEPLOY_MANIFEST_MATCH_OK.txt` / `DEPLOY_HASH_MATCH_OK.txt` が存在。
H-17 STORAGE-BASE-WRITABLE-001（新設 / 静的＋実行：storage 作成・書込検証）
**要件:** `storage/logs` を作成可能で、実際に書込ができること。
mkdir -p storage/logs
php -r '@file_put_contents("storage/logs/.w","ok")!==false || exit(1); echo "OK";' | grep -q OK && echo "OK:" > evidence/static/STORAGE_BASE_OK.txt
php -r '$_=json_decode(file_get_contents("COMPLIANCE.json"),true); $_["storage_writable_ok"]=true; file_put_contents("COMPLIANCE.json",json_encode($_,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));'
H-17b STORAGE-PRESEED-PACKAGE-001（新設 / 静的：storage ディレクトリの同梱ロック）
**要件:** パッケージ（ZIP）に **`storage/` と `storage/logs/`** を必ず同梱する。空ディレクトリの場合は
**プレースホルダ（例: `.keep`）** を含め、**`MANIFEST.json.files[]` に列挙**すること。
— DONE/ZIP ゲート
- ZIP 生成は **H-1〜H-4 + H‑12〜H‑16 + H‑13a + H‑20 が PASS** し、`COMPLIANCE.json` に
  `tokens_first_ok=true` / `evidence_fresh_ok=true` / `lint_sig_ok=true` / `storage_writable_ok=true` / `tools_presence_ok=true`
  がセットされている場合のみ許可（手詰め true 禁止）。
- `tools/pack.sh` は **必ず `tools/run_checks.sh` を実行**してから ZIP を生成する（H‑19）。
— RTM
— php 内蔵サーバで起動
— MANIFEST との一致
H-22 ABS-HREF-BAN-001（**新設 / 静的：絶対パスhrefの禁止**）
**要件:** `app/Pages/**` における **アンカー `<a>` の `href="/..."` 直書きを禁止**（外部 `http(s)://` を除く）。
直書き絶対パスhrefを検出（外部は対象外）
if grep -RIn --include="*.php" -E '<a\s+[^>]*href="/' app/Pages >/dev/null 2>&1; then
  echo "NG: absolute href found"; exit 1
fi
echo "OK:" > evidence/static/ABS_HREF_BAN_OK.txt
php -r '$_=json_decode(file_get_contents("COMPLIANCE.json"),true); $_["abs_href_ban_ok"]=true; file_put_contents("COMPLIANCE.json",json_encode($_,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));'
H-23 HTML-LS-BASELINE-001（**新設 / 静的：HTML Living Standard ベースライン**）
**要件:** 出力HTMLは **HTML Living Standard** の基礎要件に適合し、少なくとも以下を満たす。
- 文書先頭に **`<!doctype html>`（小文字）**。
- `<meta charset="utf-8">` が含まれる。
- ルート要素に **`<html lang="ja">`** を含む。
ベースライン3点を満たすテンプレートが存在（レイアウト/共通テンプレ推奨）
grep -RIni --include="*.php" "<!doctype html>" app/Pages >/dev/null 2>&1 || exit 1
grep -RIni --include="*.php" "<meta\s\+charset=\"utf-8\"" app/Pages >/dev/null 2>&1 || exit 1
grep -RIni --include="*.php" "<html\s[^>]*lang=\"ja" app/Pages >/dev/null 2>&1 || exit 1
echo "OK:" > evidence/static/HTML_LS_BASE_OK.txt
php -r '$_=json_decode(file_get_contents("COMPLIANCE.json"),true); $_["html_ls_ok"]=true; file_put_contents("COMPLIANCE.json",json_encode($_,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));'
— 方針
- さらに、現場で顕在化した欠陥（`tools/` 欠落、`storage/` 欠落、`<?php` 二重、疑似Lint）を**事前に遮断**するため、以下の Add‑ons（H‑17〜H‑20, H‑13a）を導入・明確化する。
- **H‑17b STORAGE-PRESEED-PACKAGE-001（新設）**（storage/.keep 同梱と MANIFEST 列挙をロック）
- **H‑17 STORAGE-BASE-WRITABLE-001**（storage/logs の作成と書込検証）
- **H‑18 PHP-OPENING-TAG-ONCE-001**（冒頭1KB内の `<?php` 重複検出）
- **H‑19 TOOLS-PRESENCE-LOCK-001**（`tools/run_checks.sh`/`tools/pack.sh` の存在＋実行権限）
- **H‑20 PHPLINT-REAL-ENFORCE-LOCK-001（新設）** … `tools/run_checks.sh` に **実際の `php -l` 実行が含まれることをロック**（疑似Lintを禁止）。
- **H‑13a PHPLINT-COVERAGE-COUNT-001（新設）** … **Lint 対象件数＝ログ行数**を検証（“全 *.php に `php -l` を回した”ことの数量的証跡）。
- 依存コマンドは **PHP のみ**で再現可能。Fail‑Closed 原則は維持。
— 新規・強化検査
— 新規・強化検査
— MANIFEST 契約
— 新規・強化検査
— 方針
5) Gate Matrix
- （追加）Phase S 完了時に `tools/verify_report.sh` を実行し、`COMPLIANCE.json.verify_report_ok=true` をセットする（ZIP前の最終フック）。
- **Phase S (Static)**：H-1〜H-4, H-12〜H-16, H-13a, H-18, H-19, H-20, H-22, H-23, H-24a, H-25, H-31, **H-41b, H-42, H-43, H-44, H-45, H-46**。
- **Phase B (Boot)**：H-21, **H-21H**, H-33, H-36, **H-47**。
- **Phase D (Dynamic)**：H-24b, H-34, H-35, H-37, H-38, H-39, H-40（**DB未到達は SKIP**）。

6) 検査項目一覧
- **H-51 STDIO-FD-BAN-LOCK-001（新設 / 静的：/dev/fd・プロセス置換の禁止）**
  【要件】`tools/**.sh` に `/dev/fd/`、プロセス置換 `<( )`、Here-String `<<<` を含まない。
  【受入判定（例）】
  ```bash
  if grep -RInE '/dev/fd/|<\(|<<<' tools >/dev/null 2>&1; then
    echo "NG: unstable FD operations detected"; exit 1
  fi
  echo "OK:" > evidence/static/STDIO_FD_BAN_OK.txt
  ```

- **H-52 POSIX-SHELL-STRICT-MODE-LOCK-001（新設 / 静的：シェル厳格モード）**
  【要件】`tools/run_checks.sh` は `#!/usr/bin/env bash` か `#!/bin/sh` を先頭に持ち、`set -Eeuo pipefail`（bash）または `set -eu`（POSIX sh）を設定。
  【受入判定（例）】
  ```bash
  head -n 5 tools/run_checks.sh | grep -Eq '#!/.*(bash|sh)' || exit 1
  grep -Eq 'set -Eeuo pipefail|set -eu' tools/run_checks.sh || exit 1
  echo "OK:" > evidence/static/SHELL_STRICT_MODE_OK.txt
  ```

- **H-53 LOCALE-COLLATION-LOCK-001（新設 / 静的：ロケール固定）**
  【要件】`tools/run_checks.sh` 冒頭で `export LC_ALL=C LANG=C` を設定。
  【受入判定（例）】`grep -q 'LC_ALL=C' tools/run_checks.sh`

- **H-54 FIND-XARGS-NULL-LOCK-001（新設 / 静的：ファイル名安全な走査）**
  【要件】ファイル走査に `-print0` / `-0` を用いるか、同等の**NUL区切り**処理で空白・改行を含むパスを安全に取り扱う。
  【受入判定（例）】`grep -RInE '--?print0|xargs -0|read -r -d' tools/*.sh`

- **H-55 QUIET-PASS-LOCK-001（新設 / 実行：合格時は無出力）**
  【要件】`tools/run_checks.sh` は**合格時に標準出力へ一切出力しない**。失敗時のみ `NG:` を1行出力し非0終了。
  【受入判定（例）】
  ```bash
  if bash tools/run_checks.sh | grep -q .; then
    echo "NG: runner should be quiet on pass"; exit 1
  fi
  echo "OK:" > evidence/static/QUIET_PASS_OK.txt
  ```

- **H-56 EVIDENCE-DIRS-PRESENCE-LOCK-001（新設 / 静的：証跡ディレクトリの存在）**
  【要件】`evidence/static`, `evidence/runtime`, `evidence/pkg`, `evidence/verify` が ZIP 内に存在し、必要に応じ**OKファイル**を格納。
  【受入判定（例 / PHP）】
  ```bash
  php -r '
  $need=["evidence/static","evidence/runtime","evidence/pkg","evidence/verify"];
  foreach($need as $d){ if(!is_dir($d)) die("NG:$d missing\n"); }
  echo "OK";
  ' | grep -q OK && : > evidence/static/EVIDENCE_DIRS_OK.txt
  ```

- **H-20G POLYGLOT-LINT-LOCK-001（新設 / 静的：全言語リンタの実実行ロック）**  
  【要件】言語検出（`tools/langmap.json`）に基づき、各言語に対して**実リンタ/構文検証**を実行していること。疑似Lint禁止。  
  【受入判定（例）】
  ```bash
  test -x tools/polyglot_lint.sh || exit 1
  grep -q "polyglot_lint.sh" tools/run_checks.sh || exit 1
  bash tools/polyglot_lint.sh || exit 1
  echo "OK:" > evidence/static/POLYGLOT_LINT_OK.txt
  php -r '$_=json_decode(file_get_contents("COMPLIANCE.json"),true); $_["polyglot_lint_ok"]=true; file_put_contents("COMPLIANCE.json",json_encode($_,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));'
  ```

- **H-23G HTML-LS-GLOBAL-LOCK-001（新設 / 静的：HTML Living Standard を全プロジェクトで必須化）**  
  【要件】テンプレート/レイアウト/生成HTMLが **`<!doctype html>`（小文字）**, `<meta charset="utf-8">`, `<html lang="..">` を満たす。  
  【適用範囲】`.php`, `.html`, `.htm`, `.twig`, `.blade.php`, `.ejs`, `.hbs`, `.handlebars`, `.vue`（SSR）, `.jsx/.tsx`（SSR 出力） など。  
  【受入判定（例 / 静的パス検査）】
  ```bash
  # langmap.json の templates パスを優先。見つからない場合は app/Pages, templates, resources/views, public を走査。
  FOUND=0
  for pat in "<!doctype html>" "<meta\s\+charset=\"utf-8\">" "<html\s[^>]*lang="; do
    if ! grep -RIni --include="*.php" --include="*.html" --include="*.htm"        --include="*.twig" --include="*.blade.php" --include="*.ejs"        --include="*.hbs" --include="*.handlebars" --include="*.vue"        -e "$pat" app/Pages templates resources/views public >/dev/null 2>&1; then
      echo "NG: HTML LS baseline not satisfied for $pat"; exit 1;
    fi
  done
  echo "OK:" > evidence/static/HTML_LS_GLOBAL_OK.txt
  php -r '$_=json_decode(file_get_contents("COMPLIANCE.json"),true); $_["html_ls_global_ok"]=true; file_put_contents("COMPLIANCE.json",json_encode($_,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));'
  ```
  【備考】フルSSRでテンプレ直検出が困難な場合は、`tools/render_smoke.sh` で代表画面を**静的にレンダ**し、出力HTMLに対して同検査を行う。

- **H-48 LANGMAP-DECL-AND-COVERAGE-001（新設 / 静的：langmap 宣言とカバレッジの一致）**  
  【要件】リポジトリ内の拡張子検出結果と `tools/langmap.json` の宣言が**包含関係**になっている（=未宣言言語が無い）。  
  【受入判定（例）】
  ```bash
  php -r '
  $m=json_decode(file_get_contents("tools/langmap.json"),true);
  $decl=[]; foreach($m as $k=>$v){ foreach(($v["ext"]??[]) as $e){ $decl[$e]=1; } }
  $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator("."));
  $miss=[];
  foreach($it as $f){ if(!$f->isFile()) continue; $p="".$f; $ext=strtolower(pathinfo($p,PATHINFO_EXTENSION));
    if($ext && !isset($decl[$ext]) && !preg_match("/^(log|lock|md|txt|json|yml|yaml|xml|ini|sh|bat|sql)$/",$ext)){
      $miss[$ext]=1;
    }
  }
  if($miss){ fwrite(STDERR,"NG: undeclared_ext=".implode(",",array_keys($miss))."\n"); exit(1); }
  echo "OK";
  ' | grep -q OK && echo "OK:" > evidence/static/LANGMAP_COVERAGE_OK.txt
  php -r '$_=json_decode(file_get_contents("COMPLIANCE.json"),true); $_["langmap_coverage_ok"]=true; file_put_contents("COMPLIANCE.json",json_encode($_,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));'
  ```

- **H-49 ERR-GUARD-GLOBAL-STATIC-001（新設 / 静的：重要経路の例外ガード確認）**  
  【要件】/env・/health・/provider/setup の最小ブート/公開経路に**言語相応のエラーハンドリング**が存在する。  
  【例：検出パターン】
    - PHP: `catch\s*\(Throwable\b` または `set_exception_handler\(`  
    - Node.js: `process\.on\(\s*['\"]uncaughtException['\"]` / Express の `app\.use\(.*error`  
    - Python(Flask/FastAPI): `@app\.errorhandler` / `add_exception_handler`  
  【受入判定（例）】
  ```bash
  # 代表ファイルの例：app.php / app.js / main.py 等を走査
  if ! grep -RInE "catch\s*\(Throwable|set_exception_handler\(|process\.on\(\s*['\"]uncaughtException|errorhandler\(|add_exception_handler" . >/dev/null 2>&1; then
    echo "NG: ERR-GUARD not detected"; exit 1;
  fi
  echo "OK:" > evidence/static/ERR_GUARD_OK.txt
  php -r '$_=json_decode(file_get_contents("COMPLIANCE.json"),true); $_["err_guard_ok"]=true; file_put_contents("COMPLIANCE.json",json_encode($_,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));'
  ```

- **H-50 TEMPLATE-EXT-HTML-LS-COVERAGE-001（新設 / 静的：テンプレ拡張子の網羅検査）**  
  【要件】`langmap.json.templates` に記された**全テンプレートパス**に対し、H-23G と同等の HTML-LS 検査を適用する。  
  【受入判定】`tools/render_smoke.sh` が少なくとも 1 画面（index/login 等）を描画し、出力HTMLが H-23G を満たす。

6.1) 新規検査（H-41b〜H-47：“ダメ押し”ゲート対応）
- **H-41b-ZIP-CONTENT-LOCK-001**  
  【要件】配布ZIP内に想定外の実行ファイル/バイナリ/隠しファイルが混入しない。  
  【受入判定】ZIP内のファイル拡張子をホワイトリスト照合し、逸脱0件で合格。  
  【証跡】`evidence/static/ZIP_CONTENT_OK.txt`  
  【COMPLIANCE】`.zip_content_ok = true`
- **H-42-PRESEED-KEEP-IN-PACK-LOCK-001**  
  【要件】`storage/preseed/**` の初期データは ZIP に必ず同梱する。  
  【受入判定】ZIPに `storage/preseed/` が存在し、必須ファイルのSHA256一致。  
  【証跡】`evidence/static/STORAGE_KEEP_PRESEED_IN_PACK_OK.txt`  
  【COMPLIANCE】`.storage_keep_preseed_in_pack_ok = true`
- **H-43-SPEC-ONLY-SOURCE-LOCK-001**  
  【要件】仕様書/スクリプトは**ソースのみ**同梱（バイナリ生成物は不可）。  
  【受入判定】ZIP内で `.o/.so/.dll/.exe` 等バイナリの検出0件。  
  【証跡】`evidence/static/SPEC_ONLY_SOURCE_OK.txt`  
  【COMPLIANCE】`.spec_only_source_ok = true`
- **H-44-REDIR-BNORM-LOCK-001**  
  【要件】リダイレクト系はBNORM準拠（/login 等、相対→絶対／no-store付与）。  
  【受入判定】リダイレクト応答ヘッダの `Cache-Control: no-store` および正規化URL確認。  
  【証跡】`evidence/runtime/REDIR_BNORM_LOCK_OK.txt`  
  【COMPLIANCE】`.redir_bnorm_lock_ok = true`
- **H-45-UPSERT-VALUES-BAN-LOCK-001**  
  【要件】UPSERT句での `VALUES(...)` 直書きを禁止（SQLインジェクション温床回避）。  
  【受入判定】静的解析で `INSERT ... ON CONFLICT ... DO UPDATE SET ... VALUES(` の検出0件。  
  【証跡】`evidence/static/UPSERT_VALUES_BAN_OK.txt`  
  【COMPLIANCE】`.upsert_values_ban_ok = true`
- **H-46-DDL-INDEX-IFNE-BAN-LOCK-001**  
  【要件】`CREATE INDEX IF NOT EXISTS` の乱用を禁止（移行手順で明示管理）。  
  【受入判定】DDLスクリプトから `IF NOT EXISTS` を禁止語として検出0件。  
  【証跡】`evidence/static/DDL_INDEX_IFNE_BAN_OK.txt`  
  【COMPLIANCE】`.ddl_index_ifne_ban_ok = true`
- **H-47-HEALTH-MINBOOT-DATA-LOCK-001**  
  【要件】最小ブートデータ適用後の `/health` が 200/JSON/no-store を返す。  
  【受入判定】`/health` への HEAD/GET が 200、`Content-Type: application/json`、`Cache-Control: no-store`。  
  【証跡】`evidence/runtime/HEALTH_MINBOOT_DATA_OK.txt`  
  【COMPLIANCE】`.health_minboot_data_ok = true`
（補足）既存の `setup_link_ban_ok` / `health_boot_head_ok` / `config_path_root_ok` は COMPLIANCE 必須一覧に明記（8.2参照）。
検査項目（H-1〜H-47 / 一意版）
H-1/H-2 の実行で PHPLINT.log / PHPLINT_FINAL.log を生成している前提
— 走査対象件数
N=$(find . -type f -name "*.php" | wc -l | awk '{print $1}')
7) DONE/ZIP ゲート
- （追加）ZIP 生成には `COMPLIANCE.json.verify_report_ok=true` を **必須** とする（VERIFY_REPORT v1 同梱の保証）。
- ZIP 生成は **Phase S/B の必須検査がすべて PASS** し、かつ `COMPLIANCE.json` に以下の**必須キー**が **true/規定値** である場合にのみ許可（手詰め true 禁止）。
- 追加強化（v4.7 系）により、下記の **“ダメ押し”キー** を AND で要求：
  - `.zip_content_ok`
  - `.storage_keep_preseed_in_pack_ok`
  - `.spec_only_source_ok`
  - `.redir_bnorm_lock_ok`
  - `.upsert_values_ban_ok`
  - `.ddl_index_ifne_ban_ok`
  - `.health_minboot_data_ok`（DB未到達時は SKIP 可）

— COMPLIANCE.json（統合・必須キー）
  - `verify_report_ok`
- 代表必須キー（抜粋）
  - `phplint_pre_pass`, `phplint_final_pass`, `manifest_sha256`, `gate_sig_ok`, `pkg_files_hashed`,
  - `deploy_lint_pass`, `deploy_manifest_match`, `deploy_hash_match`,
  - `pages_rel_require_ban_pass`, `base_path_helpers_pass`,
  - `tokens_first_ok`, `evidence_fresh_ok`, `lint_sig_ok`,
  - `storage_writable_ok`, `tools_presence_ok`, `boot_env_ok`,
  - `abs_href_ban_ok`, `html_ls_ok`, `sot_schema_coverage_ok`,
  - `pack_provenance_ok`, `zip_ready_ok`,
  - **追加（v4.7 系）**: `zip_content_ok`, `storage_keep_preseed_in_pack_ok`, `spec_only_source_ok`,
    `redir_bnorm_lock_ok`, `upsert_values_ban_ok`, `ddl_index_ifne_ban_ok`, `health_minboot_data_ok`,
    `db_engine="mysql8"`, `http_client="curl"`。
- **追加（v4.8 系）**: `html_ls_global_ok`, `polyglot_lint_ok`, `langmap_coverage_ok`, `err_guard_ok`, `polyglot_manifest_ok`

— RTM（統合）
- 各 H-* の証跡ファイル／COMPLIANCE キーの対応は本文該当節の末尾に併記。
7.1) COMPLIANCE.json（統合・必須キー）
- `.setup_link_ban_ok` = true
- `.health_boot_head_ok` = true
- `.config_path_root_ok` = true
7.2) RTM（統合）
- `.setup_link_ban_ok` = true
- `.health_boot_head_ok` = true
- `.config_path_root_ok` = true
H-41b-ZIP-CONTENT-LOCK-001,"ZIP内コンテンツ検査（バイナリ/隠しファイル排除）","evidence/static/ZIP_CONTENT_OK.txt; COMPLIANCE.json.zip_content_ok"
H-42-PRESEED-KEEP-IN-PACK-LOCK-001,"preseed データのZIP同梱確認","evidence/static/STORAGE_KEEP_PRESEED_IN_PACK_OK.txt; COMPLIANCE.json.storage_keep_preseed_in_pack_ok"
H-43-SPEC-ONLY-SOURCE-LOCK-001,"仕様/スクリプトはソースのみ同梱","evidence/static/SPEC_ONLY_SOURCE_OK.txt; COMPLIANCE.json.spec_only_source_ok"
H-44-REDIR-BNORM-LOCK-001,"リダイレクトBNORM（no-store/URL正規化）","evidence/runtime/REDIR_BNORM_LOCK_OK.txt; COMPLIANCE.json.redir_bnorm_lock_ok"
H-45-UPSERT-VALUES-BAN-LOCK-001,"UPSERT VALUES 直書きの禁止","evidence/static/UPSERT_VALUES_BAN_OK.txt; COMPLIANCE.json.upsert_values_ban_ok"
H-46-DDL-INDEX-IFNE-BAN-LOCK-001,"DDL: CREATE INDEX IF NOT EXISTS の禁止","evidence/static/DDL_INDEX_IFNE_BAN_OK.txt; COMPLIANCE.json.ddl_index_ifne_ban_ok"
H-47-HEALTH-MINBOOT-DATA-LOCK-001,"最小ブートデータ後の/health 200+JSON+no-store","evidence/runtime/HEALTH_MINBOOT_DATA_OK.txt; COMPLIANCE.json.health_minboot_data_ok"9)
  - `.html_ls_global_ok`
  - `.polyglot_lint_ok`
  - `.langmap_coverage_ok`
  - `.err_guard_ok` 変更管理
- v4.7.2_HARDENED_RECOMPOSED_FULL: v4.7.1_FULL 末尾の v4.3.0_HARDENED “全文収載”を撤去し、本文へ完全統合。重複を全削除し、章立てを再設計。

8) 成果物
- 証跡ファイル、COMPLIANCE.json、MANIFEST.json などを含む。


--- spec/environment.txt ---
環境仕様書
版数: v1.0.6
更新: 2025-09-28 10:28:55 JST

# 0) 目的・適用範囲（MUST）
- 本書は「実行/配備環境の実値・方言ルール」を唯一の権威として定義し、他仕様書からの重複記載は不可（省略）。
- 生成仕様書は環境に依存しない共通規範を定める。DB 方言等の可否は本書が拘束力を持つ。

# 2) プラットフォーム/言語（移管）— MUST
- PHP 8.3（CLI/CGI同一）、フレームワーク不使用、PSR非必須。
- DB: **MySQL 8.0.19 以上（MariaDB 非対象）**。文字コードは **utf8mb4**、照合は **utf8mb4_0900_ai_ci**。不可時は **utf8mb4_unicode_ci** に自動フォールバックし、**ログ記録**。

# 3) ランタイム前提（移管: 機能仕様書 §0 抜粋）— MUST
- ランタイム: PHP 8.3、**Apache + mod_rewrite**、有効な cURL 拡張。
- **Apache ディレクティブ要件（サブディレクトリ配備を含む）**
  - VirtualHost/Directory 設定で **`AllowOverride FileInfo`（または `All`）** を付与。
  - `.htaccess` によって **URL書き換え（BNORM）** を行う。
  - **`Options -MultiViews -Indexes` を必須**（MultiViews による内容ネゴシエーションを無効化）。
  - **`RewriteBase` は原則禁止**。やむを得ず使用する場合のみ、**実際の配備パス**を明記（例: `/medical/recept_audit/`）。
- フロントコントローラ: **{BASE}/app.php**（サブディレクトリ配備に非依存／BNORM）。
- **HTTP クライアントは cURL のみを使用（file_get_contents 等は使用禁止）**。
- DB: **MySQL 8.0.19 以上**を要求（MariaDB 非対象）。

### 3.x) .htaccess 正本（BNORM / サブディレクトリ安全版 / MUST）
以下を **そのまま貼付**して使用すること（**{BASE}/.htaccess**）。

```apache
# BNORM-safe rewrite (subdirectory proof)
Options -Indexes -MultiViews
RewriteEngine On

# Pass through existing files/dirs
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^env-lite$ env-lite.php [L]
RewriteRule ^health-lite$ health-lite.php [L]

# (Optional) static pass-through
RewriteRule ^assets/ - [L]

# Everything else to this directory's app.php
RewriteRule ^ app.php [L,QSA]

# NOTE: Do not set "RewriteBase /".
# If you must: RewriteBase /<your-subdir>/   (and keep "app.php" relative)
```

# 4) DB 接続（実値、移管: 機能仕様書 付録A）— MUST
付録A：DB接続情報ビルドシート（SOT）
- host: mysql320.phy.lolipop.lan / port: 3306 / name: LAA1577731-recept
- user: LAA1577731 / pass: tP2H3ibSBegLQnDs
- charset: utf8mb4 / collation: utf8mb4_0900_ai_ci（不可時は unicode_ci にフォールバックしログ記録）
※ config.php に **文字列そのまま**でベタ書き。

# 5) ENV 設定（移管: 機能仕様書 §14）— MUST
=== 14. 設定（ENV） ===
- SYNC_MAX_PER_CYCLE=1000 / SYNC_DB_MIN_ROWS=100（実閾値は max(この値,25)）。
- SYNC_SEED_URLS: 種ページURL（複数可）。SYNC_ALLOWED_HOSTS: 許可ホスト（カンマ区切り）。
- HTTP_TIMEOUT=30 / HTTP_RETRY=3。STORAGE_BASE=storage。LOG_LEVEL=info。

# 6) CRON/Runner（移管: 機能仕様書 §7）— MUST
=== 7. cron 設計（単一cron + EmptyDB Auto-Fetch） ===
- crontab 例（Asia/Tokyo）: */10 * * * * **cd {BASE}** && php cli/sync.php --cycle >> storage/logs/cron.sync.log 2>&1
- `--cycle` の挙動（疑似）:
  - 取得排他成功 → 空判定
  - 空判定の条件:
    * コア表（tenants/users/departments/provider_rules）のいずれかが存在しない
    * provider_rules が 0 または ENV最小値未満（min 25）
    * departments が 35 未満
  - 空なら強制取得（--force）→ 変化があれば build_rules → import_db を順に実行。
  - 新着 0 が 3サイクル継続したら backoff を指数的に最大 6h まで延長。

# 7) 追補：DB 方言ロック & DDL/Upsert/ログ・HTTP・Apache（MUST）
- **ENV-DB-DIALECT-001**
  **要件:** 本環境の DB 方言は **MySQL 8.0.x** とする（MariaDB 非対象）。
  **受入判定:** COMPLIANCE.json に `"db_engine":"mysql8"` が存在。
  **不許可例:** `"mysql"` 以外の値、空欄。

- **ENV-DB-DDL-POLICY-MySQL-001**
  **要件:** **MySQL では `CREATE INDEX IF NOT EXISTS` を禁止。** インデックス確保は
  `information_schema.STATISTICS` による**存在確認 → `CREATE INDEX` 実行**の二段階で行う。
  **受入判定:** スキーマ確保ロジックに `INDEX_NAME='<idx>'` を条件とした存在確認があり、未存在時のみ `CREATE INDEX`。
  **不許可例:** `CREATE INDEX IF NOT EXISTS ...` を DDL/コードに記述。

- **ENV-DB-UPSERT-MySQL-001**
  **要件:** Upsert は **`INSERT … ON DUPLICATE KEY UPDATE`** 方式とし、`UPDATE` 句で `VALUES()` の参照は禁止。
  **受入判定:** 静的検査で `VALUES(` を Upsert 文内に検出しない。
  **不許可例:** `... UPDATE col=VALUES(col)`。

- **ENV-DB-CHARSET-001**
  **要件:** 接続は **utf8mb4 / utf8mb4_0900_ai_ci**。不可時は **utf8mb4_unicode_ci** に自動フォールバックし、**L2ログ `charset_fallback`** を必ず出力。
  **受入判定:** フォールバック発生時にログ行が存在。

- **ENV-HEALTH-MINBOOT-MySQL-001**
  **要件:** `/health` は**最小ブート＝スキーマ自己確保のみ**を行う。データの流し込み（seed/import）は行わない。
  **受入判定:** `/health` 実行後にテーブル/インデックス/固定行が整備されるが、レコード件数は増分しない。

- **ENV-HEALTH-LOG-MySQL-001**
  **要件:** `/health` の結果を **1行JSON(L2)** で必ず記録：成功時 `health_min_boot_pass`、失敗時 `health_min_boot_fail`。
  **受入判定:** `/health` 実行で `storage/logs/app.log` に該当イベントが出力。

- **ENV-SCHEMA-LOG-MySQL-001**
  **要件:** スキーマ適用時、各 DDL 失敗は `schema_exec_failed`、固定行の確保完了で `schema_bootstrap_ok`、
  インデックスは `schema_index_created|exists|failed` を L2ログに出力。
  **受入判定:** それぞれのケースでログ行が残る。

- **ENV-NOSTORE-ALL-001**
  **要件:** 全応答に `Cache-Control: no-store` を付与（**3xx を含む、301 禁止**）。
  **受入判定:** 代表 URL 群（`/`, `/provider`, `/env`, `/env/` ほか）で `no-store` を確認、かつ 301 が使われていない。

- **ENV-APACHE-MULTIVIEWS-LOCK-001（新設 / MUST）**
  **要件:** 配備ディレクトリの `.htaccess` に **`Options -MultiViews` を明示**し、MultiViews を無効化すること（内容ネゴシエーション起因の 404/先行処理を防止）。
  **受入判定:** `.htaccess` に `Options -MultiViews` の記載が存在する。
  **不許可例:** `Options MultiViews` または未記載。

- **ENV-APACHE-HTACCESS-BNORM-001（新設 / MUST）**
  **要件:** `.htaccess` は **ディレクトリ相対で `app.php` へ集約**し、**`RewriteBase /` の固定を禁止**（必要時のみ実パスを明記）。
  **受入判定:** `.htaccess` に `RewriteEngine On`・`RewriteRule ^ app.php` が存在し、`RewriteBase /` 単独は検出 0 件。
  **不許可例:** `RewriteBase /` の固定、`header('Location: /...')` 等の絶対パス固定に依存する構成。

- **ENV-APACHE-ALLOWOVERRIDE-001（新設 / MUST）**
  **要件:** VirtualHost/Directory に **`AllowOverride FileInfo`（以上）** が設定され、`.htaccess` の記述が有効になること。
  **受入判定:** vhost 設定レビューで該当ディレクティブが確認できる。

# 7.x) 追補：コード実装スタイルの環境ロック（MUST）
- **ENV-CODE-DB-API-STYLE-LOCK-001（MUST）**  
  **要件:** DBアクセスは **`App\Support\DB` の静的ファサード**を**必ず提供**する（`pdo()` / `q()` / `ensure_index()` を静的公開）。内部実装が関数でも良いが、外部インターフェースは**常にクラス**。  
  **受入判定:** `app/Support/DB.php` に `class DB` が存在し、`php -r "require 'app/Support/DB.php'; echo class_exists('App\\Support\\DB')?'OK':'NG';"` が `OK`。  
  **不許可例:** `DB::q()` を呼んでいるのに `class App\Support\DB` が存在しない／関数のみ定義。

- **ENV-CODE-MSC-SELF-LOCK-001（MUST）**  
  **要件:** Support/** の各ファイルは、**先頭〜80行以内**に自分が直接使う自前シンボル（Config/DB/Log/Res 等）を `require_once` で**自己完結**させる（FCの読み込み順に依存しない）。  
  **受入判定:** `grep -RIl 'App\\Support\\log_line' app/Support | xargs -I{} head -n 80 {} | grep -q 'require_once .*Log.php'` 等の静的検査に合格。  
  **不許可例:** `Schema.php` が `log_line()` を呼ぶのに `Log.php` を先頭〜80行で `require_once` していない。

- **ENV-CODE-HEALTH-NO-CLASSERR-001（MUST）**  
  **要件:** `/health` 実行時、**クラス未定義エラーを出さない**こと（最小ブートの完了が前提）。  
  **受入判定:** `/health` 実行直後の `storage/logs/app.log` に `Class "App\Support\DB" not found` を**含まない**（1件でも出たら FAIL）。  
  **不許可例:** 上記エラーや同等の `Class ... not found` が記録される。

- **ENV-CODE-NO-LEGACY-TEMPLATE-001（MUST）**  
  **要件:** 生成時に**過去テンプレ/外部雛形の持ち込みを禁止**。**本チャットで提示された正本仕様群のみ**を SOT とし、ビルドログに `source="spec-only"` を記録。  
  **受入判定:** `evidence/pkg/FULL_FILE_ONLY_OK.txt` が存在し、`evidence/pkg/BUILD_LOG.txt` に `source=spec-only` が記録されている。  
  **不許可例:** ビルドログが不在／`source=template` 等の記録がある。

- **ENV-CODE-REDIR-BNORM-LOCK-002（MUST/強化）**  
  **要件:** リダイレクトは**必ず** `href()`（BNORM）経由で Location を生成し、**直書き `header('Location: /...')` を禁止**。  
  **受入判定:** `app/Support/Res.php` 以外で `header('Location:` が**0件**、かつ `redirect()` 実装内で `href(` を使用。  
  **不許可例:** 任意のページで `header('Location: /foo')` を直接呼ぶ。

# 8) RTM（要件トレーサビリティ → 検査仕様書フック）
ENV-DB-DIALECT-001,"db_engine=mysql8 を COMPLIANCE.json で確認","COMPLIANCE.json"
ENV-DB-DDL-POLICY-MySQL-001,"IF NOT EXISTS 付き INDEX 禁止（grep）＋ E2E で index 存在確認","schema.sql; app/Support/Schema.php"
ENV-DB-UPSERT-MySQL-001,"VALUES() を Upsert で使用しない（静的）","app/Support"
ENV-DB-CHARSET-001,"照合フォールバック時に charset_fallback を L2ログへ","app/Support/DB.php; storage/logs/app.log"
ENV-HEALTH-MINBOOT-MySQL-001,"/health=最小ブート（スキーマのみ）","app.php; schema.sql"
ENV-HEALTH-LOG-MySQL-001,"/health 成否ログ（health_min_boot_pass/fail）","storage/logs/app.log"
ENV-SCHEMA-LOG-MySQL-001,"schema_exec_failed / schema_bootstrap_ok / schema_index_* を記録","app/Support/Schema.php; storage/logs/app.log"
ENV-NOSTORE-ALL-001,"全3xx含め no-store・301禁止",".htaccess; app.php"
ENV-APACHE-MULTIVIEWS-LOCK-001,"Options -MultiViews を .htaccess へ明示",".htaccess"
ENV-APACHE-HTACCESS-BNORM-001,"RewriteRule ^ app.php / RewriteEngine On / RewriteBase 固定禁止",".htaccess"
ENV-APACHE-ALLOWOVERRIDE-001,"AllowOverride FileInfo 以上を vhost で設定","httpd.conf (vhost) 設定"
ENV-CODE-DB-API-STYLE-LOCK-001,"`App\Support\DB` 静的ファサードを必ず提供","app/Support/DB.php; tools/run_checks.sh; php -r 検査証跡"
ENV-CODE-MSC-SELF-LOCK-001,"Support自己完結（先頭〜80行で require_once）","app/Support/**; tools/run_checks.sh; evidence/static/MSC_LOG_DEP_OK.txt"
ENV-CODE-HEALTH-NO-CLASSERR-001,"/health 実行で Class not found を出さない","app.php; storage/logs/app.log; evidence/runtime/HEALTH_NO_CLASSERR_OK.txt"
ENV-CODE-NO-LEGACY-TEMPLATE-001,"過去テンプレ禁止・spec-only を BUILD_LOG に記録","evidence/pkg/FULL_FILE_ONLY_OK.txt; evidence/pkg/BUILD_LOG.txt"
ENV-CODE-REDIR-BNORM-LOCK-002,"Location 直書き禁止・href()必須","app/Support/Res.php; evidence/static/REDIR_ABS_PATH_OK.txt; REDIR_BNORM_USE_OKtxt"

# 付録A：DB接続情報（SOT）
- 本章の値を **唯一の実値**（Single Source of Truth）とし、他仕様書では再掲しない。
- 値の変更は本書の改版でのみ行う（差分ではなくフル版で改版）。


--- spec/functional.txt ---
機能仕様書
版数: v1.2.4 
P25-09-30 17:27:21 JST

1) 対象・前提（MUST）

- PHP 8.3（CLI/CGI同一）。フレームワーク不使用、PSR 任意。
- Web: Apache + mod_rewrite（.htaccess 有効、RewriteBase 任意、END 禁止）。
- DB: MySQL 8.0.19+（MariaDB 非対象）、文字コード utf8mb4、照合 utf8mb4_0900_ai_ci（不可時は utf8mb4_unicode_ci に自動フォールバックしログ記録）。
- フロントコントローラ（FC）: {BASE}/app.php（サブディレクトリ配備に非依存／BNORM）。
- HTTP クライアントは cURL のみ（file_get_contents 等のネット取得禁止）。
- ログ（L2）必須、AUTH-REALM-SPLIT（/provider 専用ログインと一般 /login の分離）必須。
- 配備における DB 実値は環境仕様書の付録A（SOT）に一致（config.php に文字列そのままでベタ書き）。

2) URL / 早期ハンドラ / BNORM（MUST）

- /env：FC内最上流で JSON を直返し（no-store）。/env/ も 200。判定より前の require/include/autoload/session_start/ob_start 禁止。
- /env-lite /health-lite：物理ファイル直返し（直返し＝FC特有ヘッダ無し）。Rewrite は -f/-d 優先素通し。
- /health：**最小ブート（スキーマのみ）**。schema.sql を冪等に適用し、必須テーブル・インデックス・固定行を整備。**データの流し込み（診療科・ルール含む）は行わない**。
- /：302 → /login（no-store、301 禁止）。
- 全応答 Cache-Control: no-store を付与（3xx 含む、301 禁止）。
- BNORM：dirname($_SERVER['SCRIPT_NAME']) を用い、$path = rtrim(_path(), '/')。末尾スラ有無で経路解釈を変えない（/env と /env/ が 200）。

3) AUTH-REALM-SPLIT（MUST）

- Provider Realm
  - /provider（末尾スラ問わず）→ 302 /provider/login（no-store）。
  - /provider/login：Provider 専用ログイン。
  - **/provider/setup（SetupForm 明確化）**：
    - **公開条件**：`users=0` の**初回のみ** GET を 200 で公開。`users>0` は 302 → /provider/login（no-store）。
    - **GET の動作**：**無副作用**。初期ユーザー作成フォーム（email + password）を**HTMLで表示**するのみ。
    - **POST の動作**：**POST のみ**ユーザー作成を実行。作成時は `role='provider'`、`force_reset=1` を**必ず**付与。成功後は **302 → /provider/login（no-store）**。
    - **ログ（L2）**：GET 公開時に `setup_allowed`、作成成功時に `setup_created`、`users>0` でのアクセスは `setup_redirected` を 1 行 JSON で出力。
  - /provider/*（login/setup 除外）各ブロック先頭〜10行以内に require_provider_login() を必須。
- General Realm
  - 入口は /login。成功後はロール（admin/clerk）を UI に明示。
  - /admin/* /clerk/* は require_login() 必須（未ログインは 302 → /login）。
- 禁止：/health 等でのユーザー/テナント自動作成。

3.1) /provider/setup ポリシー（厳格）
**要件（/provider/setup の GET/POST ポリシーの強調）:**
  - **GET（初回のみ公開）:** `users=0` の**初回のみ** 200 を返し、**無副作用の HTML フォーム**（email, password）を表示する。**DB 書込・セッション変更・Cookie 設定**などの副作用を持たせない。**HEAD** も 200 + `Cache-Control: no-store` を返す。**301 禁止**。
  - **POST（作成のみ）:** 初期ユーザーを**作成のみ**実行する。作成時は `role='provider'`、`force_reset=1` を**必須**で付与。成功後は **302 → /provider/login**（**`Cache-Control: no-store` 必須／**301 禁止**）。
  - **2回目以降（users>0）:** **GET/POST ともに** 302 → /provider/login（**no-store 必須**）。フォームは表示しない（**無公開**）。
  - **実装上の注意:** `/provider/login` を含む **公開画面に /provider/setup のアンカーリンクを出さない**（文言のみ可）。`X-Robots-Tag: noindex, nofollow, noarchive` を推奨。
【受入判定】A) `users=0` 環境で GET を連続2回実行して**200 + 無副作用（`users` 件数不変）**を確認。B) POST 実行後、**1 行作成**・`force_reset=1` を確認し、**302 → /provider/login（no-store）** を検証。C) `users>0` 環境で **GET/POST とも 302** を確認（**301 が 0 件**）。

4) データモデル（DDLの明文化 / MUST）

- `users` に **`tenant_id INT NULL`** を追加（**FK: tenants.id**）。**Provider（role='provider'）は NULL、Admin/Clerk は作成テナントの ID を必須設定**。  
- 既存の **`email UNIQUE`** 制約はテナント横断で一意（全体ユニーク）を維持。  
- **移行DDL（参考）**  
  ```sql
  ALTER TABLE `users` ADD COLUMN `tenant_id` INT NULL AFTER `id`;
  ALTER TABLE `users` ADD CONSTRAINT `fk_users_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE SET NULL;
  ```
- **受入試験（列存在検査の追補）**: `users.tenant_id` 列の存在を静的/動的検査に追加する。
- 予約語回避ポリシー：**DBの識別子（テーブル/列/インデックス/制約名）に予約語を使用しない**。入力データのキー名が予約語であっても **DB列名へ転用しない**（明示マッピング）。
- **必須テーブルと主要列（規範）**
  - `tenants` … `id(PK)`, `name(VARCHAR)`, `created_at(TIMESTAMP DEFAULT CURRENT_TIMESTAMP)`
  - `users` … `id(PK)`,  `email(VARCHAR)`, `password_hash(VARCHAR)`, `role(VARCHAR)`, **`force_reset(TINYINT)`**, `password_reset_token(VARCHAR NULL)`, `password_reset_expires(DATETIME NULL)`, `created_at(TIMESTAMP)`
  - `departments` … `id(PK)`, **`code(VARCHAR UNIQUE)`**, `name(VARCHAR)`
  - `provider_rules` … `id(PK)`, **`title(VARCHAR UNIQUE)`**, **`rule_condition(TEXT)`**, **`rule_action(TEXT)`**, `version(VARCHAR)`, `source_date(DATE)`, `created_at(TIMESTAMP)`
    - **マッピング規則**：入力JSONの `condition` → **`rule_condition`**、`action` → **`rule_action`**。
  - `patients` … `id(PK)`
  - `claims` … `id(PK)`, `patient_id(FK)`
  - `claim_items` … `id(PK)`, `claim_id(FK)`
  - `audit_rules` … `id(PK)`, `keyname(VARCHAR)`
  - `tenant_rule_overrides` … `id(PK)`, `tenant_id(FK)`, `rule_id(FK)`, `enabled(TINYINT DEFAULT 1)`
  - `sync_commands` … `id(PK)`, **`status(VARCHAR)`**, **`requested_at(DATETIME)`**
    - **インデックス**：`ix_sc_status_requested(status, requested_at, id)`（IF NOT EXISTS 禁止 → 存在確認後に作成）
  - 固定行（固定IDの存在を保証）
    - `rules_sync_state(id=1 固定)` … `id(PK)`, `last_sha256(CHAR(64) NULL)`
    - `sync_runner_state(id=1 固定)` … `id(PK)`, `backoff_seconds(INT DEFAULT 0)`
  - `import_runs` … `id(PK)`, `pack_name(VARCHAR)`, `pack_sha256(CHAR(64))`, `inserted(INT)`, `updated(INT)`, `failed(INT)`, `lines_read(INT)`, `created_at(TIMESTAMP)`
  - `job_runs` … `id(PK)`, `name(VARCHAR)`, `status(VARCHAR)`, `created_at(TIMESTAMP)`

- **UPSERT 方式（固定規範 / MUST）**
  - **採用**：`INSERT ... VALUES (...) AS new ON DUPLICATE KEY UPDATE col = new.col`（**AS new 方式**）
  - **禁止**：`... UPDATE col = VALUES(col)`（`/* FORBIDDEN: VALUES() */` 参照の禁止）
  - **適用範囲**：`provider_rules`, `departments`, `users`（/provider/setup 作成時）ほか、全UPSERT。

- **移行指針（参考）**
  - 既存環境の `provider_rules(condition, action)` は
    - `ALTER TABLE provider_rules RENAME COLUMN \`condition\` TO \`rule_condition\`;`
    - `ALTER TABLE provider_rules RENAME COLUMN \`action\`     TO \`rule_action\`;`

4) データモデル（DDLの動作要件 / MUST）

- **変更（本版）**: `users.username` は廃止。`users.email` は **UNIQUE**。ログイン識別子は **email** のみ。
- 必須テーブル：tenants, users, departments, provider_rules（title 一意）, patients, claims, claim_items, audit_rules, tenant_rule_overrides, sync_commands, rules_sync_state（ID=1 固定行）, sync_runner_state（ID=1 固定行）, import_runs, job_runs。
- users 追加列：force_reset, password_reset_token, password_reset_expires（無ければ追加）。
  - **初期作成（/provider/setup の POST）では `force_reset=1` を必須**（初回ログイン時にパスワード変更を強制）。
- インデックス：sync_commands に ix_sc_status_requested(status, requested_at, id)（無ければ作成）。
- 固定行：rules_sync_state(ID=1), sync_runner_state(ID=1) を保証。
- Upsert：MySQL 標準（AS new … ON DUPLICATE KEY UPDATE）。UPDATE 句で /* FORBIDDEN: VALUES() */ 禁止。INSERT…SET は不可。

5) ΔDATA-SOURCES（データ供給の前提 / MUST）

- 本仕様は**データの実値のみ**を対象とする。生成方法（配列埋め込み・ファイル同梱可否など）には言及しない。
- ルールデータ（provider_rules）および診療科（departments）の**実値**は、環境仕様書の SOT と運用に従って提供される。
- アプリは**提供された実データ**を、/provider/db（または等価 CLI）により冪等に upsert する。
- データ到達性・件数下限（rules≥208 / departments≥35）は本仕様の受入試験で検証する。

6) 取込（アプリ内エンドポイント / MUST）

- **/provider/db**
  - 役割：**(1) ルール（provider_rules）の適用** と **(2) 診療科（departments）の冪等投入** を**明示操作**で実行する**唯一のアプリ内エンドポイント**。
  - 要件：Provider ログイン必須（/provider/login）。未ログインは 302→/provider/login（no-store）。
  - 入力：
    - GET /provider/db（引数なし＝内包ルールを適用）＋ **診療科を付録Cで upsert**
    - `GET /provider/db?packs=/path/to.jsonl`（外部ルールパック）＋ **診療科を付録Cで upsert**
  - 挙動：
    - トランザクション境界は**適用単位**で行う。ルール適用は「ファイル（内包/外部）単位」で 1 つのトランザクション。診療科 upsert は別トランザクションで冪等実行。
    - いずれかでエラーが発生した場合は当該トランザクションをロールバックし、`ok:false` を返す（成功分のコミットは維持）。
    - import_runs へ pack_name/sha256/inserted/updated/failed/lines_read を記録（ルール適用時）。
    - ログ（L2）：`rules_packs_apply_start/ok/failed`、**`departments_seed_apply_start/ok/failed`** を 1行JSONで出力。
  - 成功時応答：200 JSON（no-store）
    `{ ok:true, applied:true, counts:{provider_rules: N, departments: M}, pack:{name,sha256} }`
  - 件数要件：
    - ルール：**最終 N ≥ 208**（Base25 + A〜D）。追加バッチを内包する場合は `N ≥ 208 + add_batch_count`。
    - 診療科：**最終 M ≥ 35**（付録Cの codes v1.1 ≒40件を想定。重複は更新）。
  - 冪等性：再実行しても N/M は**増え続けない**（同一データは更新または無変更）。

7) CLI（併存 / MUST）

- cli/sync.php：実ネット取得（cURL）。SYNC_SEED_URLS 未設定で exit=3（seed_missing）。--cycle は EmptyDB Auto-Fetch を実装。
- cli/build_rules.php：テンプレ到達性検査／--strict 到達0件は exit=2。
- cli/import_db.php：**/provider/db と同等ロジック**（互換目的）。引数なし＝内包／--packs 指定＝外部。実行時に**診療科 upsert も併せて実施**。

8) cron（単一cron + EmptyDB Auto-Fetch / MUST）

- 例（Asia/Tokyo）
  */10 * * * * cd {BASE} && php cli/sync.php --cycle >> storage/logs/cron.sync.log 2>&1
- 空判定：コア表欠落／provider_rules=0 または ENV 最小値未満（min 25）／departments<35。空なら force 取得→build_rules→**import（/provider/db 同等：ルール＋診療科）**。

9) UI / RBAC（MUST）

- **GET /provider/tenants/new**：200（no-store）。フォーム項目  
  - `tenant_name`（必須, maxlength≒128）  
  - `admin_email`（必須, email形式, 重複不可）, `admin_password`（必須, 強度基準：最小8文字/英大小/数字の組合せを推奨）  
  - `csrf_token`（hidden）  
- **POST /provider/tenants/new**：CSRF必須。**1トランザクション**で `tenants` 作成 → `users(admin|clerk)` 作成（`tenant_id` 紐付け, `force_reset=1`）を実行。  
  成功時 **201** または **302→/provider/tenants**、失敗時 **422/400**。部分作成は禁止（ロールバック）。
- Provider：/provider/tenants（一覧）／/provider/tenants/new（新規）…ほか。各画面でロール名（provider/admin/clerk）を明示。
- /provider/db：UI から到達する管理メニューには**非掲載（直叩き想定／限定メニュー）**でもよいが、認可は厳格に行う。
- UI-E2E（抜粋）：
  - GET /provider/tenants/new = 200（name required + CSRF hidden）
  - POST /provider/tenants/new（name=テスト医療機関, CSRF OK）= 201 または 302→一覧
  - GET /provider/tenants に新規行が表示

9.1) Provider Dashboard（最小要件）
`/provider/dashboard` はログイン後ランディング。見出し（「Provider Dashboard」）と**クイックリンク**（Tenants `/provider/tenants`、ルール適用 `/provider/db`、グローバルルール `/provider/rules`、ジョブ `/provider/jobs`、ログアウト）を持つ。
【受入判定】ログイン後に当該要素が検出できる。**機能が 0 件でも 200 を返す**。

9.2) Provider Global Rules 画面（一覧/検索/詳細）
`/provider/rules` は provider_rules を**一覧/検索/詳細の閲覧**で提供する（編集は MUST）。表示項目：title, version, source_date, created_at、詳細は rule_condition / rule_action（要約可）。
【受入判定】一覧 200（最低限表示）。

9.3) /provider/tenants/new（admin 単独作成）
`/provider/tenants/new` は **テナント名**と **テナント管理者（admin）** の **email / password** を受け付け、**1トランザクション**で以下を実行する。
  1) `tenants` に新規テナントを作成  
  2) `users` に `role='admin'` のユーザーを**作成**（`tenant_id` に作成テナントのIDを設定、`force_reset=1` を必須付与、`email` は全体ユニーク、`password_hash` は安全なハッシュ）  
  3) いずれかが失敗した場合は**全体をロールバック**  

  **フォーム項目（GET /provider/tenants/new）:**  
  - `tenant_name`（必須）  
  - `admin_email`（必須）, `admin_password`（必須）  
  - `csrf_token`（hidden）  

  **応答:** 成功時は **201** または **302→/provider/tenants**（いずれも `Cache-Control: no-store`）。バリデーションエラー/ユニーク制約違反は **422/400** を返し、**部分作成はしない**。  
【受入判定】A) フォームに上記項目が存在。B) 成功POST後、`tenants` に1行・`users` に**1行（admin）**が作成され、`tenant_id` と `force_reset=1` が正しく付与。C) `admin_email` 重複で**0行追加**（ロールバック）を確認。

9.4) /admin/clerk/new（adminによる clerk 作成）
**Admin ロール**でログイン済みであることを前提に、同一テナント配下の **事務員（clerk）** を作成する。
  - **GET /admin/clerk/new**：200（no-store）。フォーム項目  
    - ` `（必須, email形式, 全体ユニーク）, ` `（必須, 強度基準）  
    - `csrf_token`（hidden）  
  - **POST /admin/clerk/new**：CSRF必須。**1トランザクション**で `users(role='clerk', tenant_id=<adminのtenant_id>, force_reset=1)` を作成。  
    成功時 **201** または **302→/admin/users**（no-store）、失敗時 **422/400**。重複時は**ロールバック**。  

【受入判定】A) GET フォーム項目の存在。B) POST 成功後、`users` に**1行（clerk）**が作成され、`tenant_id` は管理者と同一、`force_reset=1`。C) ` ` 重複時は 0 行追加。
（以下、**v1.1.4 本文を原文のまま全文収載**）

本版は v1.1.3（正本）を**全文継承**し、以下の変更点を**統合**した確定版です（差分ではなく本書のみで完結します）。
- データモデルの**列名を明文化**：`provider_rules` は **`rule_condition` / `rule_action`** 列を採用（予約語 `CONDITION` / `ACTION` の回避）。
- ルール入力(JSON/JSONL)の `condition` / `action` は、**DB列 `rule_condition` / `rule_action` にマッピング**することを明記。
- UPSERT 方式は **AS new … ON DUPLICATE KEY UPDATE** に**固定**（`/* FORBIDDEN: VALUES() */` 参照は禁止）。
- 受入試験（E2E）に **列存在検査**（provider_rules.rule_condition / rule_action）を追加。

本版の §4（データモデル）は**上位優先**で、旧版本文に同名/近接の記述がある場合でも**本版の記述が正**とする（生成/検査仕様の規範に従う）。

10) ログ / 監査（L2 / MUST）

- `tenants_create_start` / `tenants_create_ok` / `tenants_create_failed`  
- `tenant_admin_created` / `tenant_clerk_created`  
（各イベントは 1行JSON：timestamp, level, event, tenant_id, user_id, email など）
- 1行JSON（timestamp, level, event, file, line 必須）。
- 代表イベント：db_connect_failed, schema_bootstrap_ok/failed, seed_upsert_fallback,
  rules_packs_apply_start/ok/failed, **departments_seed_apply_start/ok/failed**, auth_login/logout/guard_blocked,
  **setup_allowed/setup_created/setup_redirected**。

11) 受入試験（E2E / MUST）

H) **テナント新規（管理者作成）**  
   1. GET /provider/tenants/new = 200。フォームに `tenant_name`, `admin_email/password`, `csrf_token` が存在。
   2. POST /provider/tenants/new（valid）= **201** または **302→/provider/tenants**。受入判定: tenants に 1 行、users に 1 行（admin）。users.tenant_id は作成テナントID、`force_reset=1` が付与されていること。
   3. POST（`admin_email` 既存・重複など）= **422/400**。`tenants`/`users` に**追加がない**（完全ロールバック）こと。
A) 最小ブート：/health 実行後、JSONに `ok:true, db_ok:true, initialized:true` を含む（**データ件数は不問**）。
B) 取込（内包）：/provider/login → /provider/db（引数なし）= 200 かつ
   `counts.provider_rules ≥ 208` **かつ** `counts.departments ≥ 35`。
C) 取込（外部 packs）：/provider/db?packs=out/rules_packs.jsonl = 200 かつ
   ルール件数が増分（または同数で冪等）**かつ** `counts.departments ≥ 35` を維持。
D) CLI 互換：php cli/import_db.php（引数なし）= 0、再実行で件数不変（冪等）。
E) ネガティブ：seed_missing（sync.php exit=3）/ download_failed（exit=2）。
F) UI-E2E（Provider Tenants）3点（前版どおり）。
G) **セットアップ（SetupForm）**：
   1. 事前に `users=0` を確認した環境で、**GET /provider/setup** を 2 回連続実行して**200 + 無副作用（`users` 件数不変）**を確認。
   2. **POST /provider/setup**（email + password 指定）後、**1 行作成**され、**302 → /provider/login（no-store）** を確認。
   3. 作成ユーザーの `force_reset=1` を確認（初回ログインでパスワード変更を要求）。

I) Clerk 新規作成（/admin/clerk/new）
  前提: RBAC=admin のみ、CSRF 必須、no-store、副作用はPOSTのみ。
  1. GET /admin/clerk/new = 200。フォーム項目: clerk_email, clerk_password, csrf_token を表示。
  2. POST /admin/clerk/new（valid）= 201（または 302→/admin/users）。
     受入判定: users に 1 行（role='clerk'）, tenant_id は当該 admin と同一, force_reset=1, email は重複禁止。
  3. 重複メール・invalid は 4xx（422/400）でロールバック（users に挿入なし）。
12) パッケージ / COMPLIANCE（MUST）

- ZIP （必須）：.htaccess, app.php, config.php, schema.sql（任意）, assets/theme.css, /app/Support/*.php, /app/Pages/**, /cli/*, /tools/*, /storage/**, README.txt, INSTALL.txt, MANIFEST.json, STATE.json。
- 追加（）：
  - ルール：,
  - **診療科（任意）：**
- COMPLIANCE.json 必須キー（本機能分）：
  - "http_client":"curl"
  - "rules_": true
  - "provider_db_seeded_min": ">=208"
  - （参考）本版では departments の件数下限は**試験基準**で担保し、COMPLIANCE のキー追加は任意。
- 301 禁止／no-store 準拠。

13) 運用（MUST）

- ログローテーション（推奨：10MB or 7世代）。storage/logs/app.log, php_error.log。
- /health は“常時200”（エラーも JSON で返す／5xx フォールバック禁止）。副作用はスキーマ確保以外に持たせない。
- 取込は /provider/db（または CLI）で**明示操作**。本版では /provider/db 実行で診療科投入も完了する。
- **/provider/setup は GET 無副作用・POST 作成のみ**を厳守。

14) 移行指針（MUST）

- 既存の `users.username` は**廃止**。推奨 DDL: `ALTER TABLE users DROP COLUMN username;`
- 互換上すぐに DROP できない場合でも、**アプリは username を参照しない**こと（未使用）。将来バージョンでの DROP を前提とする。
- ログインは **email + password** のみ。既存の `/provider/login` クライアントは POST パラメータを `email`, `password` に合わせて更新する。

付録C：標準診療科コード（codes v1.1 / 約40件）
GEN, INT, SUR, PED, OBG, ORT, DER, NEU, CAR, RES, GAS, END, NEP, HEM, ONC, PSY, NEC, ENT, OPH, URO, PLA, RAD, ANM, REA, DEN, DIA, EME, CVS, THO, HBP, BRE, DMN, GER, STR, PAL, ALL, RHE, ID, PAT, LAB, PDS, OTH

付録E：共通ルール SEED（Base v1.0 / タイトル一覧）
- 基本:患者IDが未設定
- 基本:性別が未設定
- 基本:生年月日が未設定
- 基本:保険者番号が未設定
- 基本:負担割合が未設定
- 日付:請求日が未来日
- 日付:入院退院日の不整合
- 重複:同一日・同一項目が複数
- 重複:用法/投薬の重複
- 重複:検査パネルと個別検査の重複
- 整合:男性に妊娠関連項目
- 整合:年齢に不適合な小児向け項目
- 整合:年齢に不適合な高齢者向け項目
- 範囲:数量が0または負数
- 範囲:点数が負数
- 範囲:薬剤日数が過大
- 組合せ:同日に初診と再診は不可の可能性
- 組合せ:入院基本料と外来再診の同日算定
- 組合せ:検査前処置と当該検査の算定間隔不足
- 適用:適用外コードの疑い
- 適用:自費と保険の混在
- 論理:患者情報と請求のテナント不一致
- 論理:同一請求内で患者が複数
- 頻度:同一検査の高頻度実施
- 頻度:画像検査の同日複数部位
- 入力:桁誤りの疑い（点数が桁あふれ）
- 入力:日付フォーマット不正
- 必須:入院には病棟/病床情報が必要
- 必須:麻酔には術式の関連付けが必要
- 併用:同時併用が想定されない処方
- 形式:記号番号の形式不正

付録F：拡張ルール（本文内に内包）— タイトル一覧（全172件）
- ER: 破傷風ハイリスク創でトキソイド/HTIG未算定
- ER: 同日CT造影多重算定(24h以内重複)
- ER: 小児患者に成人用用量のアセトアミノフェン
- ER: 外傷でテタノス未接種履歴・未記録
- ER: 同日トリアージと高度処置の時刻順不整合
- ICU: 鎮静持続投与でDAILY AWAKENING未実施
- ICU: ストレス潰瘍予防の適応外投与
- ICU: 重複昇圧薬併用で漸減計画なし
- ICU: 人工呼吸中のVTE予防欠落
- ICU: AKI中の造影検査で腎保護未介入
- 麻酔: ASA分類未記録で全身麻酔算定
- 麻酔: 抗凝固中の脊椎麻酔実施
- 麻酔: 術後鎮痛オピオイドMME過量
- 麻酔: PONV高リスクで予防未実施
- 麻酔: 気道困難予測スコア未記録
- 感染症: 無尿/Cr高値でアミノグリコシド常用量
- 感染症: 肺炎で抗菌薬日数過長(>14d)
- 感染症: C. difficile疑いで広域抗菌薬継続
- 感染症: 血液培養採取前の抗菌薬投与
- 感染症: MRSA肺炎でバンコマイシンTDM未実施
- 輸血: Hb>10g/dLでRBC輸血算定
- 輸血: 1単位投与で再評価記録なし
- 輸血: 交差適合検査未実施で製剤投与
- 輸血: 同種抗体陽性でIgA欠損製剤未選択
- 輸血: 凍結血漿が凝固異常なしで投与
- 横断: 性別と妊娠関連算定の不一致
- 横断: 年齢に不適合な小児用加算の算定
- 横断: 自費と保険の同一請求内混在
- 横断: 同一日・同一項目の多重算定
- 横断: 点数桁あふれの疑い
- 老年: Beers基準不適薬（ベンゾジアゼピン）
- 老年: 抗コリン負荷が高い併用
- 老年: eGFR<30でメトホルミン継続
- 老年: 多剤併用(≧10薬)レビュー未実施
- 老年: 転倒リスク薬×フレイル指標
- リハ: 重複領域(PT/OT/ST)同時算定
- リハ: 低強度患者で高強度プロトコル算定
- リハ: 心不全急性期で過負荷セッション
- リハ: 目標到達後の継続算定
- CKD非透析: 造影検査で腎保護未介入
- CKD非透析: 禁忌薬(メトホルミン)継続
- CKD非透析: 高K血症でACE/ARB増量
- CKD非透析: リン吸着薬併用重複
- CKD非透析: eGFR<45で造影MRIガドリニウム反復
- 産婦: 妊婦に禁忌薬(ACE/ARB)
- 産婦: 帝王切開前抗菌薬予防の未実施
- 産婦: Rh陰性妊婦で抗D未投与
- 産婦: 産褥期以降の分娩加算算定
- 産婦: 妊娠糖尿病で栄養指導未実施
- 皮膚: イソトレチノインで妊娠検査未実施
- 皮膚: 外用ステロイド超高力価の長期連用
- 皮膚: 抗菌薬外用と全身投与の重複
- 皮膚: 生物学的製剤で結核スクリーニング未記録
- 皮膚: 光線療法の照射量漸増なし
- リウマチ: MTXで葉酸補充未実施
- リウマチ: 生物学的製剤と生ワクチン同時
- リウマチ: ステロイド高用量長期(>20mg/3か月)
- リウマチ: DMARD重複(作用機序重複)
- リウマチ: MTX腎機能低下で減量なし
- 歯科: 感染性心内膜炎ハイリスクで予防抗菌薬未投与
- 歯科: 局所麻酔リドカイン最大量超過
- 歯科: 同部位のX線撮影短期重複
- 歯科: 抜歯後鎮痛オピオイド>3日分
- 歯科: 周術期抗菌薬多剤重複
- IVR: 抗凝固中の穿刺手技で中止計画なし
- IVR: 造影超過(累積量>300mL/入院)
- IVR: エンボ化塞栓で抗生剤予防未投与
- IVR: ドレーン留置後の抜去計画未記録
- IVR: ステント留置後の抗血小板二剤期間超過
- 緩和: 急速なオピオイド用量増(>50%/24h)
- 緩和: 便秘予防未併用(オピオイド開始)
- 緩和: 腎不全でモルヒネ持続投与
- 緩和: DNAR/ACP未整備で化学療法継続
- 緩和: breakthrough疼痛でBT処方欠落
- NST: 重度低栄養で栄養計画未作成
- NST: 経管栄養で誤嚥リスク評価なし
- NST: 高リフィーディングリスクで補正なし
- NST: 静脈栄養で血糖モニタ不足
- NST: 蛋白/エネルギー目標未達のまま継続
- 高影響: 高額抗菌薬と培養陰性・炎症低値
- 高影響: 同日2種の高度画像(CT+MRI)
- 高影響: ICUで無益治療の継続疑い
- タイミング: 周術期抗菌薬投与が切皮>60分前
- タイミング: 術後48hを超える予防抗菌薬継続
- タイミング: DVT予防が術後24h以降開始
- タイミング: 透析当日に造影検査未調整
- タイミング: ワクチンと免疫抑制開始が同日
- 整合性: 診断なしの手技算定
- 整合性: 男性に産科処置算定
- 整合性: 小児禁忌薬の算定
- 整合性: 院外処方と院内投与の同日重複
- 整合性: 在宅酸素とSpO2持続正常
- 整合性: 併用禁忌薬の同時処方
- 整合性: 画像検査と造影アレルギー歴
- 整合性: 妊娠可能年齢でX線腹骨盤照射に遮蔽なし
- 整合性: 麻薬処方の二重発行
- 整合性: 禁食指示と経口投薬の同時記録
- 整合性: 透析患者のカリウム製剤投与
- 整合性: DNR下で蘇生関連加算算定
- 循環器: 心不全増悪で退院7日以内再入院
- 循環器: 心房細動で抗凝固未処方(CHA2DS2-VASc≧2)
- 呼吸器: 在宅酸素でSpO2≧96%継続・再評価なし
- 内分泌: 糖尿病でSGLT2/GLP1適応に未導入(合併症あり)
- 腎臓: 造影検査後の透析患者でKモニタ未実施
- 消化器: 上部消化管出血でPPI静注未使用
- 整形外科: 人工関節術後VTE予防未算定
- 整形外科: 骨粗鬆症骨折で骨粗鬆症治療未導入
- 脳神経: 脳梗塞急性期でtPA評価未記録
- 精神科: 高用量抗精神病薬の二剤併用>12週
- 小児: フルオロキノロン全身投与(年齢<12)
- 小児: 体重更新>6か月なしで用量固定
- 産婦人科: 妊娠高血圧でMgSO4未投与(重症所見)
- 泌尿器: 尿路感染で培養感受性と不一致抗菌薬継続
- 眼科: 緑内障でβ遮断薬点眼と喘息併存
- 耳鼻科: 抗ヒスタミン＋抗コリン重複で高齢者
- 皮膚科: 生物学的製剤でB型肝炎再活性化スクリーニング未記録
- 放射線科: 造影CTと腎機能評価48h超過
- 麻酔科: OSA高リスクで術後モニタ不足
- 救急: 外傷CT多部位でIV造影腎機能未確認
- ICU: 抗菌薬デエスカレーション48h遅延
- リハ: 休日連続で未実施(>3日)の算定継続
- NST: TPNと経腸栄養の二重算定
- 緩和: オピオイドとベンゾ同時高用量併用
- 血液: 好中球減少時にG-CSF適応未評価
- 感染症: 抗菌薬培養未採取で長期投与(>7日)
- 感染症: 結核疑いで標準隔離未実施
- 腫瘍: 化学療法前の血算/肝腎機能未チェック
- 放射線治療: 照射中の妊娠判定未確認(妊娠可能年齢)
- 外科: 術式と麻酔種別の不整合
- 一般: 自費と保険の同日別件名請求
- タイミング: 末梢血培養で左右2セット間隔<15分
- 整合性: ICD/手術コードと主診断の矛盾
- 透析: 週3回スケジュールで未実施日数>2（14日内）
- 透析: 体重増加率>5%でドライウェイト未更新
- 透析: カリウム>6.0でも透析前補正なし
- 透析: 抗凝固薬未使用で回路凝固繰り返し
- 透析: カテーテル留置>90日で入路評価なし
- 周産期: GBS陽性で分娩時抗菌薬予防未投与
- 周産期: 妊娠糖尿病で血糖自己測定記録不足
- 周産期: 帝王切開で術後VTE予防未算定
- 周産期: 妊娠高血圧で分娩後MgSO4早期中止(<24h)
- 周産期: 早産既往で17-OHPC/頚管管理なし
- 周術期: 抗菌薬選択が術式推奨と不一致
- 周術期: 体温維持不良(術中<36.0℃)対策なし
- 周術期: 術野剃毛でカミソリ使用
- 周術期: 術後血糖管理未実施(糖尿病/心臓手術)
- 周術期: クロルヘキシジン禁忌患者に同剤使用
- ASP: 広域抗菌薬の適応理由未記録
- ASP: 抗菌薬静注→経口切替遅延(>72h)
- ASP: デエスカレーション未実施で培養陰性
- ASP: 重複抗菌薬同系統併用(>48h)
- ASP: アミノグリコシドTDM未実施
- 精神科: 代謝モニタ不足（抗精神病薬）
- 一般: 禁食指示中の経口造影剤投与
- 一般: 自費再診と保険再診の同日二重算定

付録S：Schema SOT（唯一のSOT／機械可読 JSON）
（本付録の**そのままの内容**を `app/SOT/schema.required.json` としてパッケージに同梱すること）
---- JSON 開始 ----
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
---- JSON 終了 ----

# ENV依存マトリクス
機能トグルと必要ENVキーの関係を機械可読で定義する。生成・検査は本表を参照して動的試験の要否を判断する。

```json
{
  "FEATURE_MAIL": ["SMTP_HOST","SMTP_PORT","SMTP_USER","SMTP_PASS"],
  "FEATURE_STORAGE": ["STORAGE_DRIVER","STORAGE_BUCKET","STORAGE_REGION"],
  "FEATURE_DB": ["DB_HOST","DB_NAME","DB_USER","DB_PASS","DB_PORT"],
  "FEATURE_PROVIDER_MULTI_TENANT": ["TENANT_SALT"]
}
```

- いずれかのトグルが `true` の場合、列挙キーは **必須** とみなす。
- 必須キー不足は **生成のREJECT条件ではなく** `/tools/apply_env` の `missing_keys` に反映され、Phase D を **SKIP** する根拠となる。

