# N.HONEST Voucher Management System

Complete production-ready voucher management system for N.HONEST Supermarket.

## Features

- Gift voucher and company monthly voucher management
- QR code generation and scanning
- **ATM-style printable voucher cards** 🆕
- **Template-based card generation** 🆕
- Role-based access (Boss, Admin, Cashier)
- Cashier redemption with balance tracking
- Monthly reports and analytics
- Transaction history and audit logs
- CSV import for bulk voucher creation
- **Bulk card generation for batches** 🆕

## Requirements

- PHP 8.0 or higher
- MySQL 5.7 or higher / MariaDB 10.3+
- Apache/Nginx web server
- PDO PHP Extension
- GD PHP Extension

## Installation

### 1. Database Setup

```bash
mysql -u root -p < database.sql
```

This creates the database `nhonest_voucher_system` with all tables and default users.

### 2. Configure Environment

Edit `.env` file with your database credentials:

```
DB_HOST=localhost
DB_NAME=nhonest_voucher_system
DB_USER=your_username
DB_PASS=your_password
```

### 3. Set Permissions

```bash
chmod 755 public/assets/qrcodes
chmod 755 public/assets/cards
chmod 755 storage/exports
chmod 755 storage/logs
```

### 4. Web Server Configuration

#### Apache
Point document root to `public/` directory. The `.htaccess` file handles URL rewriting.

#### Nginx
Add this to your server block:

```nginx
root /path/to/voucher-system/public;
index index.php;

location / {
    try_files $uri $uri/ $uri.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

### 5. Access the System

Open your browser and navigate to: `http://localhost:8000/login.php`

## Production Login Setup

Before going live, create real staff accounts from **Admin > Users**, reset any seeded account passwords, and block accounts that should not be used in production.

## Usage Guide

### For Admin

1. **Create Companies**: Go to Companies → Add company details
2. **Create Batches**: Go to Batches → Create monthly voucher batch
3. **Import Vouchers**: Go to Import → Upload CSV file with voucher data
4. **Generate QR Codes**: System automatically generates QR codes during import
5. **Generate Cards**: Go to Generate Cards → Select batch → Create ATM-style cards 🆕

### For Cashier

1. **Scan Voucher**: Go to Scan Voucher → Scan QR code or type voucher number
2. **View Details**: System displays client name, balance, and status
3. **Redeem**: Enter amount used and receipt number → Click Redeem
4. **View Transactions**: See your redemption history

### For Boss

1. **View Dashboard**: See monthly statistics and company summaries
2. **View Reports**: Filter by month and company
3. **Export Reports**: Download PDF or Excel reports

## Voucher Card Generation 🆕

The system generates professional ATM-style voucher cards using your template designs.

### Quick Start

```bash
# Run setup
./setup-cards.sh

# Test the system
http://localhost:8000/admin/test-cards.php

# Generate cards for a batch
http://localhost:8000/admin/generate-cards.php
```

### Features
- Uses your professional template designs (side1.png and side2.png)
- Overlays voucher data (number, name, EVA ID, amount)
- Includes QR code on back
- Standard ATM card size (85.6mm × 53.98mm)
- Print-ready PNG output
- Bulk generation for entire batches
- Visual positioning tool included

### Documentation
- **Quick Guide**: See `CARD_QUICKSTART.md`
- **Full Docs**: See `CARD_GENERATION.md`
- **Visual Guide**: See `VISUAL_GUIDE.md`
- **Implementation**: See `IMPLEMENTATION_SUMMARY.md`

### Card Generation Tools
- `/admin/generate-cards.php` - Bulk generation interface
- `/admin/test-cards.php` - Testing and diagnostics
- `/admin/card-coordinate-helper.html` - Visual positioning tool
- `/admin/print-card.php` - Print individual cards

## CSV Import Format

Create a CSV file with these columns:

```
Voucher No,Client Name,EVA ID,Amount
HSV-2026-0001,ALLELUIA ALAIN,PX-Q3-2026-CB80,436800
HSV-2026-0002,BAHIGIRORA JEAN,PX-Q3-2026-CB70,436800
```

## Security Features

- Password hashing with bcrypt
- SQL injection protection with PDO prepared statements
- Role-based access control
- Transaction locking to prevent double spending
- Audit logging for all critical actions
- Session timeout

## Backup

Daily backup recommended:

```bash
mysqldump -u root -p nhonest_voucher_system > backup_$(date +%Y%m%d).sql
```

## Production Deployment

1. Enable HTTPS/SSL certificate
2. Update `.env` with production database credentials
3. Uncomment HTTPS redirect in `.htaccess`
4. Set `error_reporting` to 0 in PHP config
5. Enable automated database backups
6. Restrict file permissions (644 for files, 755 for directories)

## Database Schema

- `users` - System users (boss, admin, cashier)
- `companies` - Organizations buying vouchers
- `voucher_batches` - Monthly voucher groups
- `vouchers` - Individual voucher records
- `voucher_transactions` - Redemption history
- `audit_logs` - System activity logs

## Troubleshooting

### QR Codes not generating
- Ensure `public/assets/qrcodes/` directory is writable
- Check internet connection (uses QR API)

### Cannot login
- Verify database connection in `.env`
- Ensure users table is populated
- Check session configuration in PHP

### Voucher cannot be redeemed
- Check voucher status (must be active or partially_used)
- Verify balance is greater than zero
- Ensure amount does not exceed balance

## Support

For technical support, contact: honestsupermarket.com | 0788633739

## License

Proprietary - N.HONEST Supermarket Internal Use Only
