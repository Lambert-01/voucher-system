# Voucher Card Generation

## Overview
The system generates ATM-style voucher cards using your template images (side1.png and side2.png). Each card displays voucher information overlaid on your professional design.

## How It Works

### Templates
- **Front (side1.png)**: Displays voucher number, client name, EVA ID, and amount
- **Back (side2.png)**: Shows QR code and card information

### Card Generation Process
1. System loads your template images
2. Overlays voucher data using GD Library
3. Adds QR code to back side
4. Saves as PNG files in `/public/assets/cards/`

## Usage

### Generate Single Card
1. Go to Admin → Vouchers
2. Click "View" on any voucher
3. Click "Print Card" button
4. Card will auto-generate and open print dialog

### Generate Batch Cards
1. Go to Admin → Generate Cards
2. Select a voucher batch from dropdown
3. Click "Generate Cards"
4. System creates cards for all vouchers in batch

### Programmatic Generation
```php
require_once 'app/Helpers/card.php';

$templates = getCardTemplatePaths();
$outputPath = 'public/assets/cards/voucher_123';

$cardImages = generateVoucherCard(
    $voucherData, 
    $templates['front'], 
    $templates['back'], 
    $outputPath
);
```

## Customization

### Adjust Text Position
Edit `/app/Helpers/card.php` and modify coordinates:

```php
// Voucher number position (x, y)
imagettftext($front, 16, 0, 50, 350, $gold, $font, $voucher['voucher_no']);

// Client name position
imagettftext($front, 20, 0, 50, 410, $white, $font, $clientName);

// EVA ID position
imagettftext($front, 12, 0, 50, 445, $white, $font, $voucher['eva_id']);

// Amount position
imagettftext($front, 18, 0, 50, 510, $gold, $font, $amount);

// QR code position on back
imagecopyresampled($back, $qr, 60, 180, 0, 0, 200, 200, ...);
```

### Change Colors
```php
$white = imagecolorallocate($front, 255, 255, 255);
$gold = imagecolorallocate($front, 212, 175, 55);
$darkGreen = imagecolorallocate($front, 23, 74, 52);
```

### Font Sizes
- Voucher number: 16pt
- Client name: 20pt (bold)
- EVA ID: 12pt
- Amount: 18pt (bold)

## Template Requirements

### Image Specifications
- **Format**: PNG with transparency support
- **Size**: 85.6mm × 53.98mm (credit card size)
- **Resolution**: 300 DPI recommended for print quality
- **Dimensions**: 1011 × 638 pixels at 300 DPI

### Design Guidelines
- Leave space for text overlay in designated areas
- Ensure adequate contrast for text visibility
- Reserve area for QR code on back (approx 200×200px)

## Fonts

### Default Fonts
System uses GD's built-in fonts if custom fonts unavailable.

### Custom Fonts (Optional)
Place TrueType fonts in `/public/assets/fonts/`:
- `arial.ttf` - Regular text
- `arialbd.ttf` - Bold text

## File Structure
```
voucher system/
├── voucher card/
│   ├── side1.png          # Front template
│   ├── side2.png          # Back template
│   └── *.png              # Example cards
├── public/
│   ├── assets/
│   │   ├── cards/         # Generated cards (auto-created)
│   │   ├── qrcodes/       # QR codes
│   │   └── fonts/         # Optional TTF fonts
│   └── admin/
│       ├── generate-card.php      # Single card API
│       ├── generate-cards.php     # Bulk generation
│       └── print-card.php         # Print interface
└── app/
    └── Helpers/
        └── card.php        # Card generation logic
```

## Troubleshooting

### Cards not generating
- Ensure GD extension is enabled: `php -m | grep gd`
- Check `/public/assets/cards/` is writable: `chmod 755`
- Verify template images exist in `/voucher card/`

### Text not appearing
- Check font paths in `card.php`
- Verify coordinates match your template layout
- Ensure adequate color contrast

### QR codes missing
- Confirm QR codes exist in `/public/assets/qrcodes/`
- Check QR code path in database
- Verify QR generation ran during import

### Poor print quality
- Use higher DPI templates (300+ DPI)
- Save PNGs with maximum compression quality
- Check printer settings for photo/high quality

## API Endpoints

### Generate Single Card
```
GET /admin/generate-card.php?id={voucher_id}
```
Returns JSON with card file paths.

### Print Card
```
GET /admin/print-card.php?id={voucher_id}
```
Opens print dialog with generated card.

## Performance

### Optimization Tips
- Generate cards in batches during off-peak hours
- Cache generated cards (already saved as files)
- Use lower DPI for digital-only cards
- Enable PHP OpCache for better performance

### Batch Sizes
- Small batch (1-50 vouchers): ~5-10 seconds
- Medium batch (51-200 vouchers): ~20-40 seconds
- Large batch (200+ vouchers): ~1-2 minutes

## Security
- Only admins can generate cards (role check)
- Files saved with secure permissions (755)
- No user input in file paths (prevents injection)
- Template paths hardcoded

## Future Enhancements
- [ ] PDF export for multiple cards
- [ ] Email cards to clients
- [ ] Batch download as ZIP
- [ ] Print queue management
- [ ] Card design editor
