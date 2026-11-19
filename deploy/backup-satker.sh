#!/usr/bin/env bash
# Pencadangan mingguan kode + database + unggahan (SK 2722/2026 Bab II huruf G.1).
# Jadwalkan via cron:  0 1 * * 0  /var/www/website-satker/deploy/backup-satker.sh
# Lalu salin folder BACKUP_DIR ke media/server terpisah (offsite), mis. dengan rclone/rsync.
set -euo pipefail
APP_DIR=/var/www/website-satker
BACKUP_DIR=/backup/website-satker
DB_NAME=website_satker
STAMP=$(date +%Y%m%d)
mkdir -p "$BACKUP_DIR"
mysqldump --single-transaction --defaults-extra-file=/root/.my-satker.cnf "$DB_NAME" | gzip > "$BACKUP_DIR/db-$STAMP.sql.gz"
tar -czf "$BACKUP_DIR/files-$STAMP.tar.gz" -C "$APP_DIR" --exclude=vendor --exclude=writable/cache --exclude=writable/session .
find "$BACKUP_DIR" -type f -mtime +60 -delete
# Contoh offsite: rclone copy "$BACKUP_DIR" remote-offsite:website-satker
