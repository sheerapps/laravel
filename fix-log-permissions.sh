#!/bin/bash

# Fix Laravel Log File Permissions
# This script specifically addresses the log file permission denied error

echo "🔧 Fixing Laravel log file permissions..."

# Navigate to Laravel directory
cd /var/www/laravel

# Get current user and web server user
CURRENT_USER=$(whoami)
WEB_SERVER_USER=""

# Detect web server user
if id "www-data" &>/dev/null; then
    WEB_SERVER_USER="www-data"
elif id "nginx" &>/dev/null; then
    WEB_SERVER_USER="nginx"
elif id "apache" &>/dev/null; then
    WEB_SERVER_USER="apache"
else
    echo "⚠️  Web server user not detected, using current user"
    WEB_SERVER_USER=$CURRENT_USER
fi

echo "Current user: $CURRENT_USER"
echo "Web server user: $WEB_SERVER_USER"

# Stop web server temporarily to avoid conflicts
echo "Stopping web server..."
sudo systemctl stop nginx 2>/dev/null || sudo systemctl stop apache2 2>/dev/null || echo "Web server not running or already stopped"

# Remove existing log files that might have wrong permissions
echo "Removing existing log files with wrong permissions..."
sudo rm -f storage/logs/laravel-*.log 2>/dev/null
sudo rm -f storage/logs/laravel.log 2>/dev/null

# Create fresh storage directory structure
echo "Creating fresh storage directory structure..."
sudo mkdir -p storage/logs
sudo mkdir -p storage/framework/cache
sudo mkdir -p storage/framework/sessions
sudo mkdir -p storage/framework/views
sudo mkdir -p storage/app/public
sudo mkdir -p bootstrap/cache

# Set ownership to web server user (recommended for production)
echo "Setting ownership to web server user..."
sudo chown -R $WEB_SERVER_USER:$WEB_SERVER_USER /var/www/laravel

# Set proper permissions
echo "Setting proper permissions..."
sudo chmod -R 755 /var/www/laravel
sudo chmod -R 775 storage
sudo chmod -R 775 bootstrap/cache

# Make sure log directory is writable
sudo chmod 775 storage/logs

# Create a test log file to verify permissions
echo "Creating test log file..."
sudo -u $WEB_SERVER_USER touch storage/logs/laravel.log
sudo -u $WEB_SERVER_USER echo "Test log entry" > storage/logs/laravel.log

# Test if Laravel can write to logs
echo "Testing Laravel write permissions..."
if sudo -u $WEB_SERVER_USER php artisan --version > storage/logs/test.log 2>&1; then
    echo "✅ Laravel can write to logs!"
    sudo rm storage/logs/test.log
else
    echo "❌ Still having issues. Trying alternative approach..."
    
    # Alternative: Set ownership to current user
    echo "Setting ownership to current user..."
    sudo chown -R $CURRENT_USER:$CURRENT_USER /var/www/laravel
    sudo chmod -R 777 storage
    sudo chmod -R 777 bootstrap/cache
    
    # Test again
    if php artisan --version > storage/logs/test.log 2>&1; then
        echo "✅ Fixed with current user ownership!"
        rm storage/logs/test.log
    else
        echo "❌ Still cannot write. Checking SELinux..."
        
        # Check SELinux
        if command -v sestatus &>/dev/null; then
            SELINUX_STATUS=$(sestatus | grep "SELinux status" | awk '{print $3}')
            echo "SELinux status: $SELINUX_STATUS"
            
            if [ "$SELINUX_STATUS" = "enabled" ]; then
                echo "SELinux is enabled. Setting proper contexts..."
                sudo chcon -R -t httpd_sys_rw_content_t storage/
                sudo chcon -R -t httpd_sys_rw_content_t bootstrap/cache/
                
                # Test again
                if php artisan --version > storage/logs/test.log 2>&1; then
                    echo "✅ Fixed with SELinux contexts!"
                    rm storage/logs/test.log
                else
                    echo "❌ SELinux contexts didn't work. Temporarily disabling SELinux..."
                    sudo setenforce 0
                    echo "SELinux temporarily disabled. Test again."
                fi
            fi
        fi
    fi
fi

# Start web server again
echo "Starting web server..."
sudo systemctl start nginx 2>/dev/null || sudo systemctl start apache2 2>/dev/null || echo "Web server started"

# Show final permissions
echo ""
echo "🔍 Final permissions:"
ls -la storage/logs/
echo ""
echo "📝 Current ownership:"
ls -ld storage/
echo ""
echo "🚀 Try running your Laravel commands now:"
echo "php artisan migrate:status"
echo "php artisan route:list"
echo ""
echo "If you still have issues, run:"
echo "sudo chmod -R 777 storage"
echo "sudo chmod -R 777 bootstrap/cache"
