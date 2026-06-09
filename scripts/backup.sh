#!/bin/bash

# N.HONEST Voucher System - Daily Backup Script
# Add to cron: 0 2 * * * /path/to/backup.sh

BACKUP_DIR="/Applications/MAMP/htdocs/voucher-system/storage/backups"
DATE=$(date +%Y%m%d_%H%M%S)
FILENAME="nhonest_backup_${DATE}.sql"
DB_NAME="nhonest_voucher_system"
DB_USER="root"
DB_PASS="root"
DB_HOST="127.0.0.1"
DB_PORT="8889"
MYSQL_BIN="/Applications/MAMP/Library/bin/mysql80/bin"

# Create backup directory if not exists
mkdir -p "$BACKUP_DIR"

# Create backup
"${MYSQL_BIN}/mysqldump" -h $DB_HOST -P $DB_PORT -u $DB_USER -p$DB_PASS $DB_NAME > "${BACKUP_DIR}/${FILENAME}"

# Compress backup
gzip "${BACKUP_DIR}/${FILENAME}"

# Delete backups older than 30 days
find "$BACKUP_DIR" -name "nhonest_backup_*.sql.gz" -mtime +30 -delete

echo "Backup completed: ${FILENAME}.gz"

# Optional: Copy to external location
# cp "${BACKUP_DIR}/${FILENAME}.gz" /path/to/external/backup/
