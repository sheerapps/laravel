<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\SheerappsAccount;
use Illuminate\Support\Facades\DB;

function displayMenu() {
    echo "\n=== Referral Code Management System ===\n";
    echo "1. View all users and their referral codes\n";
    echo "2. View referral statistics\n";
    echo "3. Generate new referral code for existing user\n";
    echo "4. Test referral code validation\n";
    echo "5. Create new test user\n";
    echo "6. Exit\n";
    echo "Choose an option (1-6): ";
}

function viewAllUsers() {
    echo "\n=== All Users and Referral Codes ===\n";
    
    $users = SheerappsAccount::orderBy('id')->get();
    
    if ($users->isEmpty()) {
        echo "No users found in the system.\n";
        return;
    }
    
    echo sprintf("%-5s %-20s %-15s %-15s %-10s\n", "ID", "Name", "Username", "Referral Code", "Status");
    echo str_repeat("-", 70) . "\n";
    
    foreach ($users as $user) {
        $referrerName = $user->referrer ? $user->referrer->name : 'None';
        echo sprintf("%-5s %-20s %-15s %-15s %-10s\n", 
            $user->id, 
            substr($user->name, 0, 19), 
            substr($user->username ?: 'N/A', 0, 14),
            $user->referral_code,
            $user->status
        );
    }
    
    echo "\nReferral Relationships:\n";
    foreach ($users as $user) {
        if ($user->referrer) {
            echo "  {$user->name} ← {$user->referrer->name} (Code: {$user->referrer->referral_code})\n";
        }
    }
}

function viewReferralStats() {
    echo "\n=== Referral Statistics ===\n";
    
    $users = SheerappsAccount::all();
    
    if ($users->isEmpty()) {
        echo "No users found in the system.\n";
        return;
    }
    
    foreach ($users as $user) {
        $referralCount = $user->getReferralCount();
        $referrerName = $user->referrer ? $user->referrer->name : 'None';
        
        echo "\nUser: {$user->name} (ID: {$user->id})\n";
        echo "  Referral Code: {$user->referral_code}\n";
        echo "  Referred By: {$referrerName}\n";
        echo "  Total Referrals: {$referralCount}\n";
        
        if ($referralCount > 0) {
            $referrals = $user->referrals;
            echo "  Referred Users:\n";
            foreach ($referrals as $referral) {
                echo "    - {$referral->name} (ID: {$referral->id}, Code: {$referral->referral_code})\n";
            }
        }
    }
}

function generateNewReferralCode() {
    echo "\n=== Generate New Referral Code ===\n";
    
    $users = SheerappsAccount::orderBy('id')->get();
    
    if ($users->isEmpty()) {
        echo "No users found in the system.\n";
        return;
    }
    
    echo "Select user to generate new referral code for:\n";
    foreach ($users as $user) {
        echo "  {$user->id}. {$user->name} (Current: {$user->referral_code})\n";
    }
    
    echo "Enter user ID: ";
    $handle = fopen("php://stdin", "r");
    $userId = trim(fgets($handle));
    fclose($handle);
    
    $user = SheerappsAccount::find($userId);
    if (!$user) {
        echo "Invalid user ID.\n";
        return;
    }
    
    $oldCode = $user->referral_code;
    $user->referral_code = $user->generateUniqueReferralCode();
    $user->save();
    
    echo "✅ New referral code generated!\n";
    echo "  User: {$user->name}\n";
    echo "  Old Code: {$oldCode}\n";
    echo "  New Code: {$user->referral_code}\n";
}

function testReferralCode() {
    echo "\n=== Test Referral Code Validation ===\n";
    
    echo "Enter referral code to test: ";
    $handle = fopen("php://stdin", "r");
    $referralCode = trim(fgets($handle));
    fclose($handle);
    
    if (empty($referralCode)) {
        echo "Testing with empty referral code...\n";
        echo "Expected: Success (proceeding without referral)\n";
        return;
    }
    
    $user = SheerappsAccount::where('referral_code', $referralCode)
        ->orWhere('id', $referralCode)
        ->first();
    
    if (!$user) {
        echo "❌ Referral code '{$referralCode}' is invalid.\n";
        echo "No user found with this referral code or ID.\n";
        return;
    }
    
    if (!$user->isActive()) {
        echo "❌ Referral code '{$referralCode}' is inactive.\n";
        echo "User: {$user->name} (Status: {$user->status})\n";
        return;
    }
    
    echo "✅ Referral code '{$referralCode}' is valid!\n";
    echo "  User: {$user->name}\n";
    echo "  ID: {$user->id}\n";
    echo "  Status: {$user->status}\n";
    echo "  Username: {$user->username ?: 'N/A'}\n";
}

function createNewTestUser() {
    echo "\n=== Create New Test User ===\n";
    
    echo "Enter user name: ";
    $handle = fopen("php://stdin", "r");
    $name = trim(fgets($handle));
    fclose($handle);
    
    if (empty($name)) {
        echo "Name cannot be empty.\n";
        return;
    }
    
    echo "Enter username (optional): ";
    $handle = fopen("php://stdin", "r");
    $username = trim(fgets($handle));
    fclose($handle);
    
    echo "Enter referral code to use (or leave empty for auto-generation): ";
    $handle = fopen("php://stdin", "r");
    $referralCode = trim(fgets($handle));
    fclose($handle);
    
    try {
        $user = new SheerappsAccount();
        $user->telegram_id = rand(100000000, 999999999);
        $user->name = $name;
        $user->username = $username ?: '';
        $user->photo_url = '';
        $user->api_token = bin2hex(random_bytes(32));
        $user->referrer_id = null;
        $user->status = 'active';
        
        if (!empty($referralCode)) {
            // Check if referral code already exists
            $existingUser = SheerappsAccount::where('referral_code', $referralCode)->first();
            if ($existingUser) {
                echo "❌ Referral code '{$referralCode}' already exists.\n";
                return;
            }
            $user->referral_code = $referralCode;
        }
        // If no referral code provided, the model will auto-generate one
        
        $user->last_login_at = now();
        $user->last_ip_address = '127.0.0.1';
        $user->login_history = json_encode([
            [
                'timestamp' => now()->toISOString(),
                'ip_address' => '127.0.0.1'
            ]
        ]);
        
        $user->save();
        
        echo "✅ Test user created successfully!\n";
        echo "  ID: {$user->id}\n";
        echo "  Name: {$user->name}\n";
        echo "  Username: {$user->username ?: 'N/A'}\n";
        echo "  Referral Code: {$user->referral_code}\n";
        echo "  Status: {$user->status}\n";
        
    } catch (Exception $e) {
        echo "❌ Error creating user: " . $e->getMessage() . "\n";
    }
}

// Main loop
while (true) {
    displayMenu();
    
    $handle = fopen("php://stdin", "r");
    $choice = trim(fgets($handle));
    fclose($handle);
    
    switch ($choice) {
        case '1':
            viewAllUsers();
            break;
        case '2':
            viewReferralStats();
            break;
        case '3':
            generateNewReferralCode();
            break;
        case '4':
            testReferralCode();
            break;
        case '5':
            createNewTestUser();
            break;
        case '6':
            echo "\nGoodbye!\n";
            exit(0);
        default:
            echo "Invalid option. Please choose 1-6.\n";
    }
    
    echo "\nPress Enter to continue...";
    $handle = fopen("php://stdin", "r");
    fgets($handle);
    fclose($handle);
}
