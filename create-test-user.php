<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\SheerappsAccount;
use Illuminate\Support\Facades\DB;

try {
    echo "=== Creating Test Users for Referral System ===\n\n";
    
    // Check if table exists
    if (!DB::getSchemaBuilder()->hasTable('sheerapps_accounts')) {
        echo "ERROR: sheerapps_accounts table does not exist!\n";
        echo "Please run migrations first: php artisan migrate\n";
        exit(1);
    }
    
    // 1. Create Main Test User (Referrer) with code 5555
    echo "1. Creating main test user with referral code '5555'...\n";
    
    $existingUser = SheerappsAccount::where('referral_code', '5555')->first();
    if ($existingUser) {
        echo "   User with referral code '5555' already exists:\n";
        echo "   ID: {$existingUser->id}\n";
        echo "   Name: {$existingUser->name}\n";
        echo "   Referral Code: {$existingUser->referral_code}\n";
        $mainUser = $existingUser;
    } else {
        $mainUser = new SheerappsAccount();
        $mainUser->telegram_id = 123456789;
        $mainUser->name = 'John Doe (Referrer)';
        $mainUser->username = 'johndoe';
        $mainUser->photo_url = '';
        $mainUser->api_token = bin2hex(random_bytes(32));
        $mainUser->referrer_id = null;
        $mainUser->status = 'active';
        $mainUser->referral_code = '5555'; // Set specific referral code for testing
        $mainUser->last_login_at = now();
        $mainUser->last_ip_address = '127.0.0.1';
        $mainUser->login_history = json_encode([
            [
                'timestamp' => now()->toISOString(),
                'ip_address' => '127.0.0.1'
            ]
        ]);
        
        $mainUser->save();
        echo "   ✅ Main test user created successfully!\n";
        echo "   ID: {$mainUser->id}\n";
        echo "   Name: {$mainUser->name}\n";
        echo "   Referral Code: {$mainUser->referral_code}\n";
        echo "   Status: {$mainUser->status}\n";
    }
    
    // 2. Create Referred User 1
    echo "\n2. Creating referred user 1...\n";
    
    $referredUser1 = new SheerappsAccount();
    $referredUser1->telegram_id = 987654321;
    $referredUser1->name = 'Jane Smith (Referred)';
    $referredUser1->username = 'janesmith';
    $referredUser1->photo_url = '';
    $referredUser1->api_token = bin2hex(random_bytes(32));
    $referredUser1->referrer_id = $mainUser->id; // Referred by main user
    $referredUser1->status = 'active';
    // Let the system generate referral code automatically
    $referredUser1->last_login_at = now();
    $referredUser1->last_ip_address = '127.0.0.1';
    $referredUser1->login_history = json_encode([
        [
            'timestamp' => now()->toISOString(),
            'ip_address' => '127.0.0.1'
        ]
    ]);
    
    $referredUser1->save();
    echo "   ✅ Referred user 1 created successfully!\n";
    echo "   ID: {$referredUser1->id}\n";
    echo "   Name: {$referredUser1->name}\n";
    echo "   Referral Code: {$referredUser1->referral_code}\n";
    echo "   Referred By: {$mainUser->name} (ID: {$mainUser->id})\n";
    
    // 3. Create Referred User 2
    echo "\n3. Creating referred user 2...\n";
    
    $referredUser2 = new SheerappsAccount();
    $referredUser2->telegram_id = 456789123;
    $referredUser2->name = 'Bob Wilson (Referred)';
    $referredUser2->username = 'bobwilson';
    $referredUser2->photo_url = '';
    $referredUser2->api_token = bin2hex(random_bytes(32));
    $referredUser2->referrer_id = $mainUser->id; // Referred by main user
    $referredUser2->status = 'active';
    // Let the system generate referral code automatically
    $referredUser2->last_login_at = now();
    $referredUser2->last_ip_address = '127.0.0.1';
    $referredUser2->login_history = json_encode([
        [
            'timestamp' => now()->toISOString(),
            'ip_address' => '127.0.0.1'
        ]
    ]);
    
    $referredUser2->save();
    echo "   ✅ Referred user 2 created successfully!\n";
    echo "   ID: {$referredUser2->id}\n";
    echo "   Name: {$referredUser2->name}\n";
    echo "   Referral Code: {$referredUser2->referral_code}\n";
    echo "   Referred By: {$mainUser->name} (ID: {$mainUser->id})\n";
    
    // 4. Test Referral Code Lookup
    echo "\n4. Testing referral code lookups...\n";
    
    // Test main user referral code
    $foundMainUser = SheerappsAccount::where('referral_code', '5555')->first();
    if ($foundMainUser) {
        echo "   ✅ Referral code '5555' lookup successful!\n";
        echo "   Found: {$foundMainUser->name} (ID: {$foundMainUser->id})\n";
    } else {
        echo "   ❌ Referral code '5555' lookup failed!\n";
    }
    
    // Test referred users' referral codes
    $foundReferredUser1 = SheerappsAccount::where('referral_code', $referredUser1->referral_code)->first();
    if ($foundReferredUser1) {
        echo "   ✅ Referral code '{$referredUser1->referral_code}' lookup successful!\n";
        echo "   Found: {$foundReferredUser1->name} (ID: {$foundReferredUser1->id})\n";
    }
    
    // 5. Display Referral Statistics
    echo "\n5. Referral Statistics:\n";
    echo "   Main User: {$mainUser->name} (ID: {$mainUser->id})\n";
    echo "   Referral Code: {$mainUser->referral_code}\n";
    echo "   Total Referrals: {$mainUser->getReferralCount()}\n";
    echo "   Referred Users:\n";
    
    $referrals = $mainUser->referrals;
    foreach ($referrals as $referral) {
        echo "     - {$referral->name} (ID: {$referral->id}, Code: {$referral->referral_code})\n";
    }
    
    // 6. Test API Endpoint
    echo "\n6. Testing API endpoint with referral code '5555'...\n";
    echo "   You can now test this referral code in your React Native app!\n";
    echo "   Expected result: Success with referrer info\n\n";
    
    echo "=== Test Users Created Successfully! ===\n";
    echo "You can now use referral code '5555' in your app for testing.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
