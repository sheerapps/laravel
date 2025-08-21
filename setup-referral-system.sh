#!/bin/bash

echo "=== Setting up Referral System ===\n"

cd /var/www/laravel

echo "1. Pulling latest changes..."
git pull origin main

echo "2. Clearing Laravel caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

echo "3. Running migrations..."
php artisan migrate

echo "4. Creating test users..."
php create-test-user.php

echo "5. Testing referral code validation..."
echo "Testing with referral code '5555':"
curl -s -X POST https://accounts.sheerapp.work/api/validate-referral \
  -H "Content-Type: application/json" \
  -d '{"referral_code": "5555"}' | jq '.'

echo -e "\nTesting with empty referral code:"
curl -s -X POST https://accounts.sheerapp.work/api/validate-referral \
  -H "Content-Type: application/json" \
  -d '{}' | jq '.'

echo -e "\n=== Setup Complete! ==="
echo "You can now:"
echo "1. Use referral code '5555' in your React Native app"
echo "2. Run 'php manage-referral-codes.php' for advanced management"
echo "3. Test the complete Telegram login flow"
