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
git add -A   # agar berkas baru tercatat lengkap (bukan nama folder) & penggantian nama terdeteksi
SKIP='^(\.github/|\.gitignore$|version\.json$|writable/|public/uploads/)'
mapfile -t FILES   < <(git diff --cached --name-only --diff-filter=ACMRT | grep -vE "$SKIP" || true)
mapfile -t REMOVED < <(git diff --cached --name-only --diff-filter=D | grep -vE "$SKIP" || true)
# berkas yang berganti nama: nama lama dihapus di server
mapfile -t -O "${#REMOVED[@]}" REMOVED < <(git diff --cached --name-status --diff-filter=R | awk '{print $2}' | grep -vE "$SKIP" || true)
printf 'Build %s -> %s | %s berkas berubah, %s dihapus\n' "$BUILD" "$NEW" "${#FILES[@]}" "${#REMOVED[@]}"
FJSON=$( [ ${#FILES[@]} -gt 0 ] && printf '%s\n' "${FILES[@]}" | jq -R . | jq -s . || echo '[]')
RJSON=$( [ ${#REMOVED[@]} -gt 0 ] && printf '%s\n' "${REMOVED[@]}" | jq -R . | jq -s . || echo '[]')
jq --argjson b "$NEW" --arg v "$DATE" --arg n "$NOTE" --argjson f "$FJSON" --argjson r "$RJSON" \
  '.build=$b | .version=$v | .releases = ([{build:$b,version:$v,notes:$n,files:$f} + (if ($r|length)>0 then {removed:$r} else {} end)] + .releases)' \
  version.json > version.json.tmp && mv version.json.tmp version.json
echo "version.json diperbarui. Periksa, lalu commit & push."
