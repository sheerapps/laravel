<?php
/**
 * Simple Test Script for PHP 7.1
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel the old way
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Simple Telegram OAuth Test ===\n\n";

// Test 1: Check environment
echo "1. Environment Check:\n";
echo "   APP_URL: " . env('APP_URL', 'Not set') . "\n";
echo "   APP_ENV: " . env('APP_ENV', 'Not set') . "\n";
echo "   TELEGRAM_BOT_ID: " . env('TELEGRAM_BOT_ID', 'Not set') . "\n";

// Test 2: Database connection
echo "\n2. Database Connection:\n";
try {
    $pdo = DB::connection()->getPdo();
    echo "   ✅ Database connection successful\n";
} catch (Exception $e) {
    echo "   ❌ Database connection failed: " . $e->getMessage() . "\n";
}

// Test 3: Check table
echo "\n3. Database Table Check:\n";
try {
    $tableExists = Schema::hasTable('sheerapps_accounts');
    if ($tableExists) {
        echo "   ✅ sheerapps_accounts table exists\n";
        
        $hasReferralCode = Schema::hasColumn('sheerapps_accounts', 'referral_code');
        if ($hasReferralCode) {
            echo "   ✅ referral_code column exists\n";
        } else {
            echo "   ❌ referral_code column missing\n";
        }
    } else {
        echo "   ❌ sheerapps_accounts table missing\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error checking table: " . $e->getMessage() . "\n";
}

// Test 4: Test API endpoint
echo "\n4. API Endpoint Test:\n";
try {
    $response = file_get_contents('https://accounts.sheerapp.work/api/test-telegram');
    if ($response) {
        echo "   ✅ Test endpoint accessible\n";
        $data = json_decode($response, true);
        if ($data && isset($data['status'])) {
            echo "   ✅ Response: " . $data['status'] . "\n";
        }
    } else {
        echo "   ❌ Test endpoint not accessible\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error testing endpoint: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
