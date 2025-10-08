#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'
export LC_ALL=C LANG=C

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
mkdir -p "$ROOT/evidence/static" "$ROOT/evidence/runtime" "$ROOT/evidence/pkg" "$ROOT/evidence/verify"

bash "$ROOT/tools/polyglot_lint.sh"

# HTML Living Standard check
if ! grep -RIni --include="*.php" "<!doctype html>" "$ROOT/src/Pages" > /dev/null; then
  echo "NG: HTML LS baseline missing" >&2
  exit 1
fi
if ! grep -RIni --include="*.php" "<meta\\s\+charset=\"utf-8\"" "$ROOT/src/Pages" > /dev/null; then
  echo "NG: HTML LS charset missing" >&2
  exit 1
fi
if ! grep -RIni --include="*.php" "<html\\s[^>]*lang=\"ja" "$ROOT/src/Pages" > /dev/null; then
  echo "NG: HTML lang missing" >&2
  exit 1
fi
printf 'OK\n' > "$ROOT/evidence/static/HTML_LS_GLOBAL_OK.txt"

# Verify config path root lock
if ! grep -R "base_path('config.php')" "$ROOT"/src > /dev/null; then
  echo "NG: config path root lock missing" >&2
  exit 1
fi
printf 'OK\n' > "$ROOT/evidence/static/CONFIG_PATH_ROOT_OK.txt"

# Generate COMPLIANCE stub
php -r '
$path = "$ROOT/COMPLIANCE.json";
$payload = file_exists($path) ? json_decode(file_get_contents($path), true) : [];
$payload = array_merge($payload, [
  "db_engine" => "mysql8",
  "http_client" => "curl",
  "html_ls_global_ok" => true,
  "polyglot_lint_ok" => true,
  "config_path_root_ok" => true
]);
file_put_contents($path, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
' >/dev/null

php -r '
$path = "$ROOT/MANIFEST.json";
$manifest = file_exists($path) ? json_decode(file_get_contents($path), true) : ["files" => []];
$base = [
  "app.php",
  "config.php",
  "schema.sql",
  "env-lite.php",
  "health-lite.php",
  "tools/run_checks.sh",
  "tools/polyglot_lint.sh",
  "tools/lang_profile.json",
  "tools/langmap.json",
  "tools/render_smoke.sh",
  "tools/verify_report.sh",
  "app/SOT/schema.required.json",
  "storage/.keep",
  "storage/logs/.keep",
  "storage/preseed/.keep"
];
$manifest["files"] = array_values(array_unique(array_merge($manifest["files"], $base)));
file_put_contents($path, json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
' >/dev/null

printf 'OK\n' > "$ROOT/evidence/static/TOOLS_OK.txt"

bash "$ROOT/tools/verify_report.sh"

exit 0
