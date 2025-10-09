# spec / tests（検査仕様：プラットフォーム非依存）

本ディレクトリは **検査（tests）** の共通規約とゲートを定義します。言語やフレームワークに依存しない
**Polyglot 前提**で記述し、必要に応じて各言語アダプタ（リンタ・テンプレ検出など）で具体化します。

- `policies.md` …… 検査の原則・必須ゲート・証跡の取り扱い
- `gate-matrix.yml` …… Phase S/B/D のゲート定義（言語非依存名）
- `runner/run_checks.sh` …… 安定ランナー（静粛合格・厳格モード・証跡書き出し）
- `tools/langmap.json` / `tools/polyglot_lint.sh` / `tools/render_smoke.sh` / `tools/verify_report.sh` …… 代表実装
- `checks/*.md` …… 代表チェック（HTML-LS、ERR-GUARD、storage、manifest等）

> 成果物の配布や受入判定は CI/CD 側のルールに従い、**対話的なアーティファクト取得や手詰めZIP**は想定しません。
