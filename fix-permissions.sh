#!/bin/bash

# Fix Laravel Storage Permissions Script
# This script fixes common permission issues that prevent Laravel from writing logs

echo "🔧 Fixing Laravel storage permissions..."

# Get the current user and group
CURRENT_USER=$(whoami)
CURRENT_GROUP=$(id -gn)

echo "Current user: $CURRENT_USER"
echo "Current group: $CURRENT_GROUP"

# Navigate to Laravel directory
cd /var/www/laravel

# Set proper ownership for the entire Laravel directory
echo "Setting ownership to $CURRENT_USER:$CURRENT_GROUP..."
sudo chown -R $CURRENT_USER:$CURRENT_GROUP /var/www/laravel

# Set proper permissions for storage and bootstrap/cache directories
echo "Setting storage permissions..."
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Set specific permissions for log files
echo "Setting log file permissions..."
chmod 664 storage/logs/laravel.log 2>/dev/null || echo "Log file doesn't exist yet, will be created with correct permissions"

# Create storage directories if they don't exist
echo "Creating storage directories..."
mkdir -p storage/logs
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/app/public
mkdir -p bootstrap/cache

# Set permissions for newly created directories
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# If using web server user (like www-data), also give them access
if id "www-data" &>/dev/null; then
    echo "Adding www-data user to current group..."
    sudo usermod -a -G $CURRENT_GROUP www-data
    
    # Alternative: Set ownership to web server user
    echo "Setting ownership to www-data for web server access..."
    sudo chown -R www-data:www-data /var/www/laravel
    sudo chmod -R 775 /var/www/laravel
    sudo chmod -R 775 storage
    sudo chmod -R 775 bootstrap/cache
fi

# If using nginx user, also give them access
if id "nginx" &>/dev/null; then
    echo "Adding nginx user to current group..."
    sudo usermod -a -G $CURRENT_GROUP nginx
    
    # Alternative: Set ownership to nginx user
    echo "Setting ownership to nginx for web server access..."
    sudo chown -R nginx:nginx /var/www/laravel
    sudo chmod -R 775 /var/www/laravel
    sudo chmod -R 775 storage
    sudo chmod -R 775 bootstrap/cache
fi

# Test if we can write to storage
echo "Testing write permissions..."
if touch storage/logs/test.log 2>/dev/null; then
    echo "✅ Write permission test successful!"
    rm storage/logs/test.log
else
    echo "❌ Write permission test failed!"
    echo "Trying alternative permission settings..."
    
    # More permissive settings (use with caution in production)
    sudo chmod -R 777 storage
    sudo chmod -R 777 bootstrap/cache
    
    if touch storage/logs/test.log 2>/dev/null; then
        echo "✅ Write permission test successful with 777 permissions!"
        rm storage/logs/test.log
    else
        echo "❌ Still cannot write to storage. Check SELinux or other security policies."
    fi
fi

echo ""
echo "🔍 Current permissions:"
ls -la storage/
echo ""
ls -la bootstrap/

echo ""
echo "📝 Next steps:"
echo "1. Try running your Laravel commands again"
echo "2. If still having issues, check SELinux status: sestatus"
echo "3. If SELinux is enabled, you may need to set proper contexts"
echo "4. Check web server user: ps aux | grep nginx"
echo "5. Ensure web server can access the Laravel directory"

echo ""
echo "🚀 Permissions fixed! Try your Laravel commands again."
