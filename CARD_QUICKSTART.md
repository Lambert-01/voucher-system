# Quick Start: Voucher Card Generation

## What's New? 🎉

Your voucher system can now generate professional ATM-style cards that match your template designs (side1.png and side2.png).

## Setup (One-Time)

### 1. Run Setup Script
```bash
cd "/Users/apple/project/voucher system"
./setup-cards.sh
```

This will:
- Check PHP GD extension
- Verify template files exist
- Create necessary directories
- Test card generation

### 2. Verify System
Visit: `http://localhost:8000/admin/test-cards.php`
- Click "Generate Test Card"
- View the generated cards
- Verify text positioning and layout

## How to Use

### Option 1: Single Voucher Card
1. Go to Admin → Vouchers
2. Click "View" on any voucher
3. Click "Print Card"
4. Card auto-generates and opens print dialog

### Option 2: Bulk Generation
1. Go to Admin → Generate Cards (`/admin/generate-cards.php`)
2. Select a voucher batch
3. Click "Generate Cards"
4. All cards for that batch are generated

### Option 3: During Import
Cards can be auto-generated when importing vouchers (feature can be added).

## File Structure

```
voucher system/
├── voucher card/
│   ├── side1.png              ← Your FRONT template
│   ├── side2.png              ← Your BACK template
│   └── *.png                  ← Example cards
│
├── public/assets/cards/       ← Generated cards appear here
│   ├── voucher_1_front.png
│   ├── voucher_1_back.png
│   └── ...
│
└── app/Helpers/card.php       ← Card generation logic
```

## What Gets Generated?

### Front Card (side1.png + data)
- Voucher number (gold text)
- Client name (white, uppercase)
- EVA ID (white, smaller)
- Amount (gold, large)

### Back Card (side2.png + QR)
- QR code (200x200px)
- All card terms and conditions from template

## Customization

### Adjust Text Position
Edit `/app/Helpers/card.php`:

```php
// Line 40-60: Change coordinates (x, y)
imagettftext($front, 16, 0, 50, 350, ...);  // Voucher number
imagettftext($front, 20, 0, 50, 410, ...);  // Client name
imagettftext($front, 12, 0, 50, 445, ...);  // EVA ID
imagettftext($front, 18, 0, 50, 510, ...);  // Amount
```

### Change Colors
```php
// Line 25-30
$gold = imagecolorallocate($front, 212, 175, 55);     // Gold
$white = imagecolorallocate($front, 255, 255, 255);   // White
```

### Font Sizes
```php
imagettftext($front, SIZE, 0, x, y, $color, $font, $text);
//                   ^^^^
//                   Change this number
```

## Troubleshooting

### Cards not generating?
```bash
# Check GD extension
php -m | grep gd

# Check permissions
chmod 755 public/assets/cards
```

### Text in wrong position?
- Open generated card
- Note where text appears
- Adjust coordinates in `card.php`
- Regenerate and check again

### No QR code on back?
- Ensure QR codes were generated during import
- Check `/public/assets/qrcodes/` directory
- Run QR generation: `/admin/generate-qr.php`

## Template Requirements

Your side1.png and side2.png should be:
- **Size**: 85.6mm × 53.98mm (credit card size)
- **Resolution**: 1011 × 638 pixels @ 300 DPI
- **Format**: PNG with transparency
- **Design**: Leave space for text overlay

## Navigation

The new pages are accessible via:
- **Test**: `/admin/test-cards.php`
- **Bulk Generate**: `/admin/generate-cards.php`
- **API**: `/admin/generate-card.php?id=1`
- **Print**: `/admin/print-card.php?id=1`

## Quick Tips

1. **Test first**: Use test-cards.php before bulk generation
2. **Position carefully**: Take time to position text correctly
3. **High quality**: Use 300 DPI templates for printing
4. **Batch at night**: Generate large batches during off-peak
5. **Keep templates**: Don't delete side1.png and side2.png

## Need Help?

1. Check `CARD_GENERATION.md` for detailed docs
2. Run `./setup-cards.sh` to verify setup
3. Visit `/admin/test-cards.php` for diagnostics
4. Contact support: 0788633739

---

**Ready to generate cards?**
1. Run `./setup-cards.sh`
2. Visit http://localhost:8000/admin/test-cards.php
3. Generate your first test card!
