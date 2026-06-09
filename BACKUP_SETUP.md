# Backup System Setup

## Daily Automatic Backup

The system includes an automated backup script at `scripts/backup.sh`.

### Features
- Runs daily at 2:00 AM
- Compresses backups with gzip
- Stores backups in `storage/backups/`
- Auto-deletes backups older than 30 days
- Can copy to external location for weekly/monthly archives

### Setup Cron Job

1. Make script executable (already done):
```bash
chmod +x /Users/apple/project/voucher\ system/scripts/backup.sh
```

2. Open crontab editor:
```bash
crontab -e
```

3. Add this line (daily at 2 AM):
```
0 2 * * * /Users/apple/project/voucher\ system/scripts/backup.sh >> /Applications/MAMP/htdocs/voucher-system/storage/logs/backup.log 2>&1
```

4. Save and exit (in vi: press ESC, type `:wq`, press ENTER)

5. Verify cron job is set:
```bash
crontab -l
```

### Manual Backup

Run anytime:
```bash
/Users/apple/project/voucher\ system/scripts/backup.sh
```

### Restore from Backup

```bash
gunzip /Applications/MAMP/htdocs/voucher-system/storage/backups/nhonest_backup_YYYYMMDD_HHMMSS.sql.gz
/Applications/MAMP/Library/bin/mysql80/bin/mysql -h 127.0.0.1 -P 8889 -u root -proot nhonest_voucher_system < nhonest_backup_YYYYMMDD_HHMMSS.sql
```

### Weekly External Copy

Modify the script to copy to external drive:
```bash
# Uncomment last line in backup.sh and set path:
cp "${BACKUP_DIR}/${FILENAME}.gz" /Volumes/ExternalDrive/backups/
```

### Monthly Restore Test

Test restore monthly:
```bash
# Create test database
/Applications/MAMP/Library/bin/mysql80/bin/mysql -h 127.0.0.1 -P 8889 -u root -proot -e "CREATE DATABASE test_restore"

# Restore to test database
gunzip -c latest_backup.sql.gz | /Applications/MAMP/Library/bin/mysql80/bin/mysql -h 127.0.0.1 -P 8889 -u root -proot test_restore

# Verify
/Applications/MAMP/Library/bin/mysql80/bin/mysql -h 127.0.0.1 -P 8889 -u root -proot test_restore -e "SELECT COUNT(*) FROM vouchers"

# Drop test database
/Applications/MAMP/Library/bin/mysql80/bin/mysql -h 127.0.0.1 -P 8889 -u root -proot -e "DROP DATABASE test_restore"
```
