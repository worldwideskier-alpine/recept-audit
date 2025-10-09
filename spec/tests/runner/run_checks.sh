#!/usr/bin/env bash
# spec/tests/runner/run_checks.sh — 安定ランナー（Polyglot）
set -Eeuo pipefail
IFS=$'\n\t'
export LC_ALL=C LANG=C

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
mkdir -p "$ROOT/evidence/static" "$ROOT/evidence/runtime" "$ROOT/evidence/pkg" "$ROOT/evidence/verify"

TMPDIR="$(mktemp -d)"; trap 'rm -rf "$TMPDIR"' EXIT

# 1) POLYGLOT LINT
bash "$ROOT/spec/tests/tools/polyglot_lint.sh"   1>"$ROOT/evidence/static/POLYGLOT_LINT.log"   2>"$ROOT/evidence/static/POLYGLOT_LINT.err" || { echo "NG: polyglot lint failed"; exit 1; }
: > "$ROOT/evidence/static/POLYGLOT_LINT_OK.txt"

# 2) HTML-LS baseline（代表画面をレンダして静的検証）
bash "$ROOT/spec/tests/tools/render_smoke.sh" >"$TMPDIR/out.html" 2>"$ROOT/evidence/static/RENDER_SMOKE.err" || { echo "NG: render smoke failed"; exit 1; }
grep -Eiq '^<!doctype html>' "$TMPDIR/out.html" || { echo "NG: HTML LS baseline not satisfied for <!doctype html>"; exit 1; }
grep -Eiq '<meta\s+charset="utf-8">' "$TMPDIR/out.html" || { echo "NG: HTML LS baseline not satisfied for <meta charset>; exit 1; }
grep -Eiq '<html\s+[^>]*lang=' "$TMPDIR/out.html" || { echo "NG: HTML LS baseline not satisfied for <html lang=>"; exit 1; }
: > "$ROOT/evidence/static/HTML_LS_GLOBAL_OK.txt"

# 3) VERIFY-REPORT（証跡集約）
bash "$ROOT/spec/tests/tools/verify_report.sh" || { echo "NG: verify_report failed"; exit 1; }

# 合格時は無出力で0終了
exit 0
