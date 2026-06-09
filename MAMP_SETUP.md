# MAMP Configuration for N.HONEST Voucher System

## Setup Instructions for MAMP

### 1. Move Project to MAMP htdocs

```bash
# Copy the entire project to MAMP's htdocs folder
cp -r "/Users/apple/project/voucher system" /Applications/MAMP/htdocs/voucher-system
```

Or manually:
- Open Finder
- Go to `/Applications/MAMP/htdocs/`
- Copy the "voucher system" folder there
- Rename it to `voucher-system` (remove spaces)

### 2. Configure Database

1. Open MAMP and start servers
2. Click "Open WebStart page" or go to: http://localhost:8888/MAMP/
3. Click "Tools" → "phpMyAdmin"
4. Click "Import" tab
5. Choose file: `database.sql`
6. Click "Go"

OR use command line:
```bash
/Applications/MAMP/Library/bin/mysql -u root -p < database.sql
# Default MAMP password is usually: root
```

### 3. Update .env File

Edit `/Applications/MAMP/htdocs/voucher-system/.env`:

```
APP_NAME="N.HONEST Voucher Management System"
APP_URL="http://localhost:8888/voucher-system"
DB_HOST="localhost"
DB_NAME="nhonest_voucher_system"
DB_USER="root"
DB_PASS="root"
SESSION_LIFETIME=60
```

### 4. Set Permissions

```bash
cd /Applications/MAMP/htdocs/voucher-system
chmod -R 755 public/assets/qrcodes
chmod -R 755 public/assets/cards
chmod -R 755 storage/exports
chmod -R 755 storage/logs
```

### 5. Access the System

Open your browser and go to:
```
http://localhost:8888/voucher-system/public/login.php
```

Or if MAMP is on port 80:
```
http://localhost/voucher-system/public/login.php
```

### Default Login

- **Boss**: username: `boss`, password: `password123`
- **Admin**: username: `admin`, password: `password123`
- **Cashier**: username: `cashier`, password: `password123`

### Troubleshooting

**Port 8000 already in use**
- MAMP uses port 8888 by default (or 80)
- Don't use `php -S`, just use MAMP's Apache server

**Cannot connect to database**
- Check MAMP MySQL is running (green light)
- Default MAMP credentials: username=`root`, password=`root`
- Update `.env` with correct credentials

**Page not found**
- Make sure you're accessing: `http://localhost:8888/voucher-system/public/login.php`
- Check MAMP document root is `/Applications/MAMP/htdocs`

**Permission denied**
- Run: `sudo chmod -R 755 /Applications/MAMP/htdocs/voucher-system`

### Quick Start with MAMP

1. Start MAMP
2. Import database.sql via phpMyAdmin
3. Update .env file
4. Go to: http://localhost:8888/voucher-system/public/login.php
5. Login as admin/password123
6. Import sample_vouchers.csv
7. Test redemption as cashier
