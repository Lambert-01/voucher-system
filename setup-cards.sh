#!/bin/bash

echo "=================================="
echo "Voucher Card Generation Setup"
echo "=================================="
echo ""

# Check PHP GD extension
echo "Checking PHP GD extension..."
if php -m | grep -q "gd"; then
    echo "✓ GD extension is installed"
else
    echo "✗ GD extension is NOT installed"
    echo "  Install with: brew install php-gd (macOS) or apt-get install php-gd (Linux)"
    exit 1
fi

# Check template files
echo ""
echo "Checking template files..."
if [ -f "voucher  card/side1.png" ]; then
    echo "✓ Front template (side1.png) found"
else
    echo "✗ Front template (side1.png) NOT found"
    exit 1
fi

if [ -f "voucher  card/side2.png" ]; then
    echo "✓ Back template (side2.png) found"
else
    echo "✗ Back template (side2.png) NOT found"
    exit 1
fi

# Create output directory
echo ""
echo "Setting up directories..."
mkdir -p "public/assets/cards"
chmod 755 "public/assets/cards"
echo "✓ Cards directory created: public/assets/cards"

mkdir -p "public/assets/fonts"
chmod 755 "public/assets/fonts"
echo "✓ Fonts directory created: public/assets/fonts"

# Check QR codes directory
if [ -d "public/assets/qrcodes" ]; then
    echo "✓ QR codes directory exists"
else
    mkdir -p "public/assets/qrcodes"
    chmod 755 "public/assets/qrcodes"
    echo "✓ QR codes directory created"
fi

# Test card generation
echo ""
echo "Testing card generation capability..."
php -r "
if (extension_loaded('gd')) {
    \$img = imagecreatetruecolor(100, 100);
    if (\$img) {
        echo '✓ GD can create images\n';
        imagedestroy(\$img);
    } else {
        echo '✗ GD cannot create images\n';
        exit(1);
    }
    
    if (function_exists('imagettftext')) {
        echo '✓ TrueType font support available\n';
    } else {
        echo '⚠ TrueType font support not available (will use default fonts)\n';
    }
} else {
    echo '✗ GD extension not loaded\n';
    exit(1);
}
"

echo ""
echo "=================================="
echo "Setup Complete!"
echo "=================================="
echo ""
echo "Next steps:"
echo "1. Go to: http://localhost:8000/admin/generate-cards.php"
echo "2. Select a batch and generate cards"
echo "3. Generated cards will be in: public/assets/cards/"
echo ""
echo "For detailed documentation, see: CARD_GENERATION.md"
echo ""
