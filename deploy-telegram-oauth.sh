#!/bin/bash

# Telegram OAuth Integration Deployment Script
# This script helps set up the complete Telegram OAuth flow

echo "🚀 Deploying Telegram OAuth Integration..."
echo "=========================================="

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: Please run this script from the Laravel root directory"
    exit 1
fi

# Step 1: Run migrations
echo "📊 Step 1: Running database migrations..."
php artisan migrate --force

if [ $? -eq 0 ]; then
    echo "✅ Migrations completed successfully"
else
    echo "❌ Migration failed. Please check your database configuration"
    exit 1
fi

# Step 2: Clear caches
echo "🧹 Step 2: Clearing application caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

echo "✅ Caches cleared"

# Step 3: Check environment variables
echo "🔧 Step 3: Checking environment variables..."
required_vars=("TELEGRAM_BOT_TOKEN" "TELEGRAM_BOT_ID" "APP_URL")

for var in "${required_vars[@]}"; do
    if [ -z "${!var}" ]; then
        echo "⚠️  Warning: $var is not set in environment"
    else
        echo "✅ $var is configured"
    fi
done

# Step 4: Test the setup
echo "🧪 Step 4: Testing the setup..."
if [ -f "test-telegram-oauth.php" ]; then
    echo "Running integration tests..."
    php test-telegram-oauth.php
else
    echo "⚠️  Test script not found. Skipping tests."
fi

# Step 5: Set proper permissions
echo "🔐 Step 5: Setting proper permissions..."
chmod -R 755 storage bootstrap/cache
chmod -R 755 public

# Try to set ownership if running as root
if [ "$EUID" -eq 0 ]; then
    echo "Setting ownership to www-data..."
    chown -R www-data:www-data storage bootstrap/cache
    chown -R www-data:www-data public
fi

echo "✅ Permissions set"

# Step 6: Generate application key if not exists
if [ -z "$(grep 'APP_KEY=' .env | cut -d '=' -f2)" ] || [ "$(grep 'APP_KEY=' .env | cut -d '=' -f2)" = "" ]; then
    echo "🔑 Step 6: Generating application key..."
    php artisan key:generate --force
    echo "✅ Application key generated"
else
    echo "✅ Application key already exists"
fi

# Step 7: Optimize for production
echo "⚡ Step 7: Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Application optimized"

# Step 8: Display summary
echo ""
echo "🎉 Deployment completed successfully!"
echo "====================================="
echo ""
echo "Next steps:"
echo "1. Configure your Telegram bot with BotFather"
echo "2. Set the bot domain to: $(grep 'APP_URL=' .env | cut -d '=' -f2 | sed 's/https:\/\///')"
echo "3. Test the referral code validation:"
echo "   curl -X POST $(grep 'APP_URL=' .env | cut -d '=' -f2)/api/validate-referral \\"
echo "     -H \"Content-Type: application/json\" \\"
echo "     -d '{\"referral_code\": \"TEST123\"}'"
echo ""
echo "4. Test the OAuth demo page:"
echo "   $(grep 'APP_URL=' .env | cut -d '=' -f2)/telegram-oauth-demo?referral_code=TEST123"
echo ""
echo "5. Test in your React Native app"
echo ""
echo "For detailed setup instructions, see: TELEGRAM_OAUTH_SETUP_GUIDE.md"
echo ""
echo "🔍 To monitor logs: tail -f storage/logs/laravel.log"
echo "🧪 To run tests: php test-telegram-oauth.php"
