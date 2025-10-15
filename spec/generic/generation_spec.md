# 生成仕様（Generic / Generation Spec）

## 目的
- すべての slice が **同一の生成規約**に基づいて成果物（code/tests）を出力する。

## 出力形式（Responses API: `text.format=json_schema`）
- 返却は **JSON オブジェクト**のみ：`{"files":[{"path":string,"content":string},...]}`
- `ai/` 以下は **絶対に書かない**
- 主要コードは `/src` または `/app` 以下に最低 1 ファイル
- 追加でビルドファイル（`package.json` `tsconfig.json` `pyproject.toml` `requirements.txt` `go.mod` 等）を許容

## 作成原則
- **完全実装**：ダミーや未定義は不可。実行可能であること。
- **Single‑writer**：各 slice は**自分の所有ディレクトリ**のみ作成/更新（例：`src/Endpoints/Env/*`）。
- **共通基盤は専任 slice のみが作成**（例：`src/Support/*` は Foundation slice の専有）。他 slice は読み取りのみ。

## 入力の最小化
- タスク側で `includes` を使い、**その slice に必要な SPEC だけ**取り込む。
- 代表 SPEC（現行確認）:
  - `features/policies.md`（運用規範／禁止事項）
  - `features/endpoints.md`（/env, /health, redirects 等）
  - `features/schema.required.json`（最小スキーマ）
  - `env/policies.md`, `env/apache/htaccess.bnorm.conf`（BNORM/Apache）
  - `foundation/policies.md`（基盤の原則）

## トークンガイド
- `max_output_tokens` は **初手から** 8192〜12288 を指定（API 側での 500 軽減目的）。

## 命名・配置
- 名前・拡張子は実行環境（PHP 8.3 / Apache / MySQL 8.0）に整合。
- BNORM（Directory‑relative Front Controller）でサブディレクトリ配備でも同一動作。
