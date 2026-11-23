#!/usr/bin/env bash
# Bantu rilis: naikkan build di version.json + catat berkas yang berubah sejak rilis terakhir.
# Pakai SEBELUM commit rilis:  bash deploy/rilis.sh "Catatan perubahan singkat"
# Lalu:  git add -A && git commit && git push
set -e
cd "$(dirname "$0")/.."
NOTE="${1:?Beri catatan: bash deploy/rilis.sh \"Catatan perubahan\"}"
command -v jq >/dev/null || { echo "Butuh 'jq'. Pasang: apt install jq"; exit 1; }
BUILD=$(jq '.build' version.json); NEW=$((BUILD+1)); DATE=$(date +%F)
# berkas berubah sejak commit terakhir (yang akan masuk rilis ini), abaikan yg tak relevan utk hosting
mapfile -t FILES < <(git status --porcelain | awk '{print $2}' | grep -vE '^(deploy/|\.github/|README\.md$|\.gitignore$|version\.json$)' || true)
printf 'Build %s -> %s | %s berkas\n' "$BUILD" "$NEW" "${#FILES[@]}"
FJSON=$(printf '%s\n' "${FILES[@]}" | jq -R . | jq -s .)
jq --argjson b "$NEW" --arg v "$DATE" --arg n "$NOTE" --argjson f "$FJSON" \
  '.build=$b | .version=$v | .releases = ([{build:$b,version:$v,notes:$n,files:$f}] + .releases)' \
  version.json > version.json.tmp && mv version.json.tmp version.json
echo "version.json diperbarui. Periksa, lalu commit & push."
