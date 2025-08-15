#!/bin/bash

# Quick Fix for Laravel Storage Permissions
# Run this script to immediately fix the permission issue

echo "🚀 Quick fixing Laravel permissions..."

cd /var/www/laravel

# Quick fix - set permissions to 777 (temporary, for immediate use)
echo "Setting storage permissions to 777..."
sudo chmod -R 777 storage
sudo chmod -R 777 bootstrap/cache

# Set ownership to current user
echo "Setting ownership..."
sudo chown -R $(whoami):$(whoami) /var/www/laravel

# Test write permission
if touch storage/logs/test.log 2>/dev/null; then
    echo "✅ Permissions fixed! Laravel can now write to storage."
    rm storage/logs/test.log
else
    echo "❌ Still having issues. Check SELinux or run the full fix script."
fi

echo "Try running your Laravel commands now!"
