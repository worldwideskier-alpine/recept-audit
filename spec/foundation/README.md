# spec / foundation（生成仕様：プラットフォーム非依存）

このディレクトリは、**生成**そのものに必要な共通ルールだけを収めます。Chat/対話に依存せず、
CI/CD（ジョブ、アーティファクト、リリース等）で完結する前提です。

- `policies.md` …… 生成規約・フロー・AHR・安全規約（Chat 前提の記述は排除）
- `contracts.md` …… 生成 I/O の取り決め（成果物は `dist/*.zip` と証跡、合否は Exit Code とファイル）
- `file-globs.yml` …… 生成で書き換え可能な領域・必須ディレクトリ・雛形

> 環境・機能の固有要件は `spec/env/**`, `spec/features/**` など別紙に置き、foundation には混在させない方針です。
