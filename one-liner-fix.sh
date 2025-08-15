#!/bin/bash

# One-liner fix for Laravel log permissions
# Run this script for immediate resolution

echo "🚀 Quick one-liner fix for Laravel log permissions..."

cd /var/www/laravel

# Remove problematic log files
sudo rm -f storage/logs/laravel-*.log storage/logs/laravel.log

# Set permissions to 777 (immediate fix)
sudo chmod -R 777 storage bootstrap/cache

# Set ownership to current user
sudo chown -R $(whoami):$(whoami) /var/www/laravel

# Test
if touch storage/logs/test.log 2>/dev/null; then
    echo "✅ Fixed! Laravel can now write to logs."
    rm storage/logs/test.log
else
    echo "❌ Still having issues. Run the full fix script."
fi

echo "Try: php artisan migrate:status"
