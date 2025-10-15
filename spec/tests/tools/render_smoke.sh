#!/usr/bin/env bash
# spec/tests/tools/render_smoke.sh — 代表画面をHTMLとして一枚レンダ（実装はプロジェクト毎に調整）
set -Eeuo pipefail
IFS=$'\n\t'; export LC_ALL=C LANG=C
# ここではダミーHTMLを出力（テンプレートが用意できない場合の最小例）
cat <<'HTML'
<!doctype html>
<meta charset="utf-8">
<html lang="ja"><body><h1>smoke</h1></body></html>
HTML
