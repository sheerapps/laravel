#!/bin/bash
echo "Fixing Laravel permissions on CentOS 7..."

# Variables
LARAVEL_PATH="/var/www/laravel"
WEB_USER="apache"

echo "1. Setting ownership to $WEB_USER..."
sudo chown -R $WEB_USER:$WEB_USER $LARAVEL_PATH

echo "2. Setting directory permissions..."
sudo find $LARAVEL_PATH -type d -exec chmod 755 {} \;

echo "3. Setting file permissions..."
sudo find $LARAVEL_PATH -type f -exec chmod 644 {} \;

echo "4. Setting storage permissions..."
sudo chmod -R 775 $LARAVEL_PATH/storage
sudo chmod -R 775 $LARAVEL_PATH/bootstrap/cache
sudo chmod -R 775 $LARAVEL_PATH/public

echo "5. Creating necessary directories..."
sudo mkdir -p $LARAVEL_PATH/storage/logs
sudo mkdir -p $LARAVEL_PATH/storage/framework/cache
sudo mkdir -p $LARAVEL_PATH/storage/framework/sessions
sudo mkdir -p $LARAVEL_PATH/storage/framework/views

echo "6. Setting specific permissions for logs..."
sudo chmod 775 $LARAVEL_PATH/storage/logs
sudo chmod 775 $LARAVEL_PATH/storage/framework/cache
sudo chmod 775 $LARAVEL_PATH/storage/framework/sessions
sudo chmod 775 $LARAVEL_PATH/storage/framework/views

echo "7. Fixing SELinux context..."
if command -v semanage &> /dev/null; then
    sudo semanage fcontext -a -t httpd_exec_t "$LARAVEL_PATH/storage(/.*)?" 2>/dev/null || true
    sudo restorecon -Rv $LARAVEL_PATH/storage 2>/dev/null || true
fi

echo "8. Testing write access..."
sudo -u $WEB_USER touch $LARAVEL_PATH/storage/test.txt
if [ -f "$LARAVEL_PATH/storage/test.txt" ]; then
    echo "✅ Write access successful!"
    sudo rm $LARAVEL_PATH/storage/test.txt
else
    echo "❌ Write access failed!"
fi

echo "9. Clearing Laravel caches..."
cd $LARAVEL_PATH
sudo -u $WEB_USER php artisan config:clear
sudo -u $WEB_USER php artisan route:clear
sudo -u $WEB_USER php artisan view:clear
sudo -u $WEB_USER php artisan cache:clear

echo "Done! Restart your web server if needed."
