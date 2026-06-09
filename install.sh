#!/bin/bash

# N.HONEST Voucher Management System Installation Script

echo "================================================"
echo "N.HONEST Voucher Management System"
echo "Installation Script"
echo "================================================"
echo ""

# Check PHP version
echo "Checking PHP version..."
php -v | head -n 1

# Check MySQL
echo "Checking MySQL..."
mysql --version

echo ""
echo "Installation Steps:"
echo "1. Create database"
echo "2. Configure environment"
echo "3. Set permissions"
echo "4. Start server"
echo ""

# Database setup
read -p "Do you want to create the database now? (y/n): " create_db

if [ "$create_db" = "y" ]; then
    read -p "Enter MySQL username (default: root): " db_user
    db_user=${db_user:-root}
    
    read -sp "Enter MySQL password: " db_pass
    echo ""
    
    echo "Creating database..."
    mysql -u "$db_user" -p"$db_pass" < database.sql
    
    if [ $? -eq 0 ]; then
        echo "✓ Database created successfully"
    else
        echo "✗ Database creation failed"
        exit 1
    fi
fi

# Set permissions
echo ""
echo "Setting directory permissions..."
chmod 755 public/assets/qrcodes
chmod 755 public/assets/cards
chmod 755 storage/exports
chmod 755 storage/logs
echo "✓ Permissions set"

# Check .env file
if [ ! -f .env ]; then
    echo ""
    echo "⚠ Warning: .env file not found"
    echo "Please configure your .env file with database credentials"
else
    echo "✓ .env file exists"
fi

echo ""
echo "================================================"
echo "Installation Complete!"
echo "================================================"
echo ""
echo "To start the development server, run:"
echo "  cd public && php -S localhost:8000"
echo ""
echo "Then open your browser to:"
echo "  http://localhost:8000/login.php"
echo ""
echo "Default login credentials:"
echo "  Boss:    username: boss,    password: password123"
echo "  Admin:   username: admin,   password: password123"
echo "  Cashier: username: cashier, password: password123"
echo ""
echo "================================================"
