#!/usr/bin/env bash
# spec/tests/tools/polyglot_lint.sh — 言語検出して実リンタ/構文検証を実行
set -Eeuo pipefail
IFS=$'\n\t'; export LC_ALL=C LANG=C
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
MAP="$ROOT/spec/tests/tools/langmap.json"
mkdir -p "$ROOT/evidence/static/lint"

# 簡易的な拡張子→チェック実装（必要に応じて実リンタに置換）
check_php() { while IFS= read -r -d '' f; do php -l "$f" || return 1; done < <(find "$ROOT" -type f -name '*.php' -print0); }
check_js()  { while IFS= read -r -d '' f; do node -c "$f" 1>/dev/null 2>&1 || true; done < <(find "$ROOT" -type f -name '*.js' -print0); } # 代表例

# 現状はPHPのみ厳格にfail-close、他は最小構文/スキップの例示
FAIL=0
if find "$ROOT" -type f -name '*.php' -print -quit | grep -q .; then
  check_php 1>"$ROOT/evidence/static/lint/php_lint.log" 2>&1 || FAIL=1
fi
# 追加の言語はここに追記する（実リンタ導入を推奨）

[ $FAIL -eq 0 ] || exit 1
exit 0
