#!/bin/bash
# Backup DevFlow's own database
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
BACKUP_DIR="${PROJECT_DIR}/storage/backups"

mkdir -p "$BACKUP_DIR"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Check if SQLite or PostgreSQL
if grep -q "DB_CONNECTION=sqlite" "${PROJECT_DIR}/.env" 2>/dev/null; then
    DB_FILE="${PROJECT_DIR}/database/database.sqlite"
    if [ -f "$DB_FILE" ]; then
        cp "$DB_FILE" "$BACKUP_DIR/devflow_$TIMESTAMP.sqlite"
        echo "Backup saved: $BACKUP_DIR/devflow_$TIMESTAMP.sqlite"
    else
        echo "SQLite database file not found: $DB_FILE" >&2
        exit 1
    fi
elif grep -q "DB_CONNECTION=pgsql" "${PROJECT_DIR}/.env" 2>/dev/null; then
    DB_NAME=$(grep "^DB_DATABASE=" "${PROJECT_DIR}/.env" | cut -d= -f2)
    pg_dump "$DB_NAME" > "$BACKUP_DIR/devflow_$TIMESTAMP.sql"
    echo "Backup saved: $BACKUP_DIR/devflow_$TIMESTAMP.sql"
else
    echo "Could not determine DB_CONNECTION from .env" >&2
    exit 1
fi

# Keep last 30 backups
ls -t "$BACKUP_DIR"/devflow_* 2>/dev/null | tail -n +31 | xargs -r rm -f
