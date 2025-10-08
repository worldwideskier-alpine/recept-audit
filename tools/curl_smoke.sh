#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'
export LC_ALL=C LANG=C

BASE_URL="${1:-http://127.0.0.1:8000}"

check_endpoint() {
  local method="$1"
  local path="$2"
  local code
  local body
  local headers
  body=$(mktemp)
  headers=$(mktemp)
  if [ "$method" = "HEAD" ]; then
    code=$(curl -s -o /dev/null -D "$headers" -w "%{http_code}" -X HEAD "$BASE_URL$path")
  else
    code=$(curl -s -o "$body" -D "$headers" -w "%{http_code}" -X "$method" "$BASE_URL$path")
  fi
  if [ "$code" != "200" ]; then
    echo "NG: $method $path expected 200 got $code" >&2
    cat "$headers" >&2
    rm -f "$body" "$headers"
    exit 1
  fi
  if ! grep -iq "Cache-Control: no-store" "$headers"; then
    echo "NG: Cache-Control missing for $method $path" >&2
    rm -f "$body" "$headers"
    exit 1
  fi
  rm -f "$body" "$headers"
}

check_endpoint HEAD /health
check_endpoint HEAD /env-lite
check_endpoint GET /health-lite
check_endpoint GET /env

exit 0
