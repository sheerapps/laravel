<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\SheerappsAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

try {
    echo "=== Creating Google Review Test Account ===\n\n";
    
    // Check if table exists
    if (!DB::getSchemaBuilder()->hasTable('sheerapps_accounts')) {
        echo "ERROR: sheerapps_accounts table does not exist!\n";
        echo "Please run migrations first: php artisan migrate\n";
        exit(1);
    }
    
    // Check if Google review account already exists
    $existingAccount = SheerappsAccount::where('email', 'demo@sheerapps.com')->first();
    if ($existingAccount) {
        echo "Google review account already exists!\n";
        echo "Email: demo@sheerapps.com\n";
        echo "Password: demo123\n";
        echo "ID: {$existingAccount->id}\n";
        echo "Referral Code: {$existingAccount->referral_code}\n";
        exit;
    }
    
    // Create Google Review Demo Account
    echo "Creating Google review demo account...\n";
    
    $demoAccount = new SheerappsAccount();
    $demoAccount->telegram_id = null; // No telegram ID for email users
    $demoAccount->name = 'Demo User';
    $demoAccount->username = 'demouser';
    $demoAccount->email = 'demo@sheerapps.com';
    $demoAccount->password = Hash::make('demo123');
    $demoAccount->photo_url = '';
    $demoAccount->api_token = bin2hex(random_bytes(32));
    $demoAccount->referrer_id = null;
    $demoAccount->status = 'active';
    $demoAccount->loginMethod = 'email';
    $demoAccount->email_verified_at = now();
    $demoAccount->last_login_at = now();
    $demoAccount->last_ip_address = '127.0.0.1';
    $demoAccount->login_history = json_encode([
        [
            'timestamp' => now()->toISOString(),
            'ip_address' => '127.0.0.1',
            'method' => 'email'
        ]
    ]);
    
    try {
        $demoAccount->save();
        echo "✅ Google review demo account created successfully!\n\n";
        echo "=== ACCOUNT DETAILS ===\n";
        echo "Email: demo@sheerapps.com\n";
        echo "Password: demo123\n";
        echo "ID: {$demoAccount->id}\n";
        echo "Name: {$demoAccount->name}\n";
        echo "Username: {$demoAccount->username}\n";
        echo "Referral Code: {$demoAccount->referral_code}\n";
        echo "Login Method: {$demoAccount->loginMethod}\n";
        echo "Status: {$demoAccount->status}\n";
        echo "Created: {$demoAccount->created_at}\n\n";
        
        echo "=== GOOGLE REVIEW INSTRUCTIONS ===\n";
        echo "1. Use these credentials to test your app\n";
        echo "2. Demonstrate both Telegram and Email login\n";
        echo "3. Show the referral system working\n";
        echo "4. Highlight the dual authentication methods\n";
        echo "5. Mention this supports Google Play policy compliance\n\n";
        
        echo "=== TESTING CHECKLIST ===\n";
        echo "✅ Email login: demo@sheerapps.com / demo123\n";
        echo "✅ Referral code: {$demoAccount->referral_code}\n";
        echo "✅ User profile display\n";
        echo "✅ Login method tracking\n";
        echo "✅ Referral system\n";
        
    } catch (Exception $e) {
        echo "❌ Error creating demo account: " . $e->getMessage() . "\n";
        echo "This might be due to database constraints. Please check the schema.\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}