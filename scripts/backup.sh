#!/usr/bin/env bash
set -Eeuo pipefail

: "${DB_DATABASE:?DB_DATABASE is required}"
: "${DB_USERNAME:?DB_USERNAME is required}"
: "${DB_HOST:=db}"
: "${BACKUP_DIR:=./backups}"
: "${BACKUP_RETENTION_DAYS:=14}"

mkdir -p "$BACKUP_DIR"
timestamp="$(date -u +%Y%m%dT%H%M%SZ)"
database_file="$BACKUP_DIR/helpdesk-db-$timestamp.dump"
uploads_file="$BACKUP_DIR/helpdesk-uploads-$timestamp.tar.gz"

export PGPASSWORD="${DB_PASSWORD:?DB_PASSWORD is required}"
pg_dump --host="$DB_HOST" --username="$DB_USERNAME" --dbname="$DB_DATABASE" --format=custom --no-owner --file="$database_file"
unset PGPASSWORD

if [[ -d storage/app/public ]]; then
  tar -czf "$uploads_file" -C storage/app public
else
  echo "Uploads directory storage/app/public does not exist; database backup completed without uploads." >&2
fi

find "$BACKUP_DIR" -type f -mtime +"$BACKUP_RETENTION_DAYS" -name 'helpdesk-*' -delete
if [[ -f "$uploads_file" ]]; then sha256sum "$database_file" "$uploads_file"; else sha256sum "$database_file"; fi
echo "Backup completed: $database_file"
