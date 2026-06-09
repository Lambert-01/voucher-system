# N.HONEST Voucher Management System
## Quick Start Guide

### Installation (5 minutes)

1. **Install Database**
   ```bash
   mysql -u root -p < database.sql
   ```
   This creates the database with sample data.

2. **Configure Environment**
   Edit `.env` file:
   ```
   DB_USER=root
   DB_PASS=your_password
   ```

3. **Start Server**
   ```bash
   cd public
   php -S localhost:8000
   ```

4. **Open Browser**
   Go to: http://localhost:8000/login.php

### Default Logins

All use password: `password123`

- **Boss**: `boss`
- **Admin**: `admin`
- **Cashier**: `cashier`

### Test the System (10 minutes)

#### As Admin:

1. Login as `admin`
2. Go to **Batches** → Create new batch
   - Company: IOM (already exists)
   - Batch Code: TEST-2026-Q1
   - Batch Name: Test Batch January 2026
   - Month: 2026-01
   - Click Create

3. Go to **Import Vouchers**
   - Select batch: TEST-2026-Q1
   - Upload file: `sample_vouchers.csv`
   - Click Import
   - Wait for 19 vouchers to be created

4. Go to **Vouchers** → View all vouchers
   - Check QR codes are generated
   - Click "Print" on any voucher to see the card

#### As Cashier:

1. Logout → Login as `cashier`
2. Go to **Scan Voucher**
3. Enter voucher number: `HSV-2026-0001`
4. Click Search
5. You'll see:
   - Client: ALLELUIA ALAIN
   - Balance: 436,800 RWF
6. Enter amount: `50000`
7. Enter receipt: `REC-001`
8. Click **Redeem Voucher**
9. See confirmation with new balance

#### As Boss:

1. Logout → Login as `boss`
2. View **Dashboard**
   - See total issued
   - See total used
   - See remaining balance
3. Go to **Reports**
   - Filter by month: 2026-01
   - See company summary
   - See all transactions

### System Features

✓ Company management
✓ Batch creation
✓ CSV import (bulk vouchers)
✓ QR code generation
✓ Printable voucher cards
✓ Scan and redeem
✓ Transaction history
✓ Balance tracking
✓ Monthly reports
✓ Audit logs
✓ Role-based access

### File Structure

```
voucher system/
├── public/              # Web root
│   ├── boss/           # Boss pages
│   ├── admin/          # Admin pages
│   ├── cashier/        # Cashier pages
│   ├── assets/         # CSS, JS, QR codes
│   └── login.php       # Entry point
├── app/
│   ├── Models/         # Database models
│   ├── Helpers/        # Helper functions
│   └── Views/          # View templates
├── config/             # Configuration
├── database.sql        # Database setup
├── sample_vouchers.csv # Test data
└── .env               # Environment config
```

### Troubleshooting

**Cannot connect to database**
- Check MySQL is running
- Verify credentials in `.env`
- Run `mysql -u root -p` to test connection

**QR codes not showing**
- Check `public/assets/qrcodes/` is writable
- Verify internet connection (uses QR API)

**Voucher cannot be redeemed**
- Status must be "active" or "partially_used"
- Balance must be greater than 0
- Amount cannot exceed balance

### Production Deployment

1. Upload all files to server
2. Point domain to `public/` directory
3. Update `.env` with production database
4. Enable HTTPS (required for production)
5. Set up daily database backups
6. Change default passwords immediately

### Support

Contact: 0788633739
Website: honestsupermarket.com
