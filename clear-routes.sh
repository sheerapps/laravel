#!/bin/bash

# Clear Laravel caches
echo "Clearing Laravel caches..."

# Clear route cache
php artisan route:clear

# Clear config cache
php artisan config:clear

# Clear application cache
php artisan cache:clear

# Clear view cache
php artisan view:clear

# Clear compiled classes
php artisan clear-compiled

# Regenerate autoload files
composer dump-autoload

echo "All caches cleared successfully!"
echo "Routes should now be accessible."
