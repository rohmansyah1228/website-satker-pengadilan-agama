#!/usr/bin/env bash
# Bantu rilis: naikkan build di version.json + catat berkas yang berubah sejak rilis terakhir.
# Pakai SEBELUM commit rilis:  bash deploy/rilis.sh "Catatan perubahan singkat" [nomor-versi]
#   nomor-versi mis. 1.9.0; bila dikosongkan angka terakhir dinaikkan (1.8.0 -> 1.8.1).
# Lalu:  git add -A && git commit && git push
set -e
cd "$(dirname "$0")/.."
NOTE="${1:?Beri catatan: bash deploy/rilis.sh \"Catatan perubahan\"}"
command -v jq >/dev/null || { echo "Butuh 'jq'. Pasang: apt install jq"; exit 1; }
BUILD=$(jq '.build' version.json); NEW=$((BUILD+1)); DATE=$(date +%F)
OLDNAME=$(jq -r '.name // "1.0.0"' version.json)
NAME="${2:-$(echo "$OLDNAME" | awk -F. '{printf "%d.%d.%d", $1, $2, $3+1}')}"
[[ "$NAME" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || { echo "Nomor versi harus berbentuk 1.2.3"; exit 1; }
# berkas berubah sejak commit terakhir (yang akan masuk rilis ini), abaikan yg tak relevan utk hosting
git add -A   # agar berkas baru tercatat lengkap (bukan nama folder) & penggantian nama terdeteksi
SKIP='^(\.github/|\.gitignore$|version\.json$|writable/|public/uploads/)'
mapfile -t FILES   < <(git diff --cached --name-only --diff-filter=ACMRT | grep -vE "$SKIP" || true)
mapfile -t REMOVED < <(git diff --cached --name-only --diff-filter=D | grep -vE "$SKIP" || true)
# berkas yang berganti nama: nama lama dihapus di server
mapfile -t -O "${#REMOVED[@]}" REMOVED < <(git diff --cached --name-status --diff-filter=R | awk '{print $2}' | grep -vE "$SKIP" || true)
printf 'Versi %s -> %s (build %s -> %s) | %s berkas berubah, %s dihapus\n' "$OLDNAME" "$NAME" "$BUILD" "$NEW" "${#FILES[@]}" "${#REMOVED[@]}"
FJSON=$( [ ${#FILES[@]} -gt 0 ] && printf '%s\n' "${FILES[@]}" | jq -R . | jq -s . || echo '[]')
RJSON=$( [ ${#REMOVED[@]} -gt 0 ] && printf '%s\n' "${REMOVED[@]}" | jq -R . | jq -s . || echo '[]')
jq --argjson b "$NEW" --arg nm "$NAME" --arg v "$DATE" --arg n "$NOTE" --argjson f "$FJSON" --argjson r "$RJSON" \
  '.build=$b | .name=$nm | .version=$v | .releases = ([{build:$b,name:$nm,version:$v,notes:$n,files:$f} + (if ($r|length)>0 then {removed:$r} else {} end)] + .releases)' \
  version.json > version.json.tmp && mv version.json.tmp version.json
echo "version.json diperbarui. Periksa, lalu commit & push."
