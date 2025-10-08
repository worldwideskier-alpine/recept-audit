#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'
export LC_ALL=C LANG=C

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
LOG_DIR="$ROOT/evidence/static/lint"
mkdir -p "$LOG_DIR"
LOG_FILE="$LOG_DIR/php_lint.log"
: > "$LOG_FILE"
FAIL=0
while IFS= read -r -d '' file; do
  if ! php -l "$file" >>"$LOG_FILE" 2>&1; then
    FAIL=1
  fi
done < <(find "$ROOT" -type f -name "*.php" -not -path "*/vendor/*" -print0)
if [ "$FAIL" -ne 0 ]; then
  echo "NG: polyglot lint failed" >&2
  exit 1
fi
printf 'OK\n' > "$ROOT/evidence/static/POLYGLOT_LINT_OK.txt"
exit 0
