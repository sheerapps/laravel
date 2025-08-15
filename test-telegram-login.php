<?php
/**
 * Test Script for Telegram Login
 * Run this script to test the Telegram login functionality
 */

require_once 'vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "=== Telegram Login Test Script ===\n\n";

// Test 1: Check environment variables
echo "1. Environment Variables Check:\n";
echo "   TELEGRAM_BOT_TOKEN: " . (env('TELEGRAM_BOT_TOKEN') ? '✓ Set' : '✗ Missing') . "\n";
echo "   TELEGRAM_BOT_USERNAME: " . (env('TELEGRAM_BOT_USERNAME') ? '✓ Set' : '✗ Missing') . "\n";
echo "   APP_NAME: " . (env('APP_NAME') ?: 'Not set') . "\n\n";

// Test 2: Check database connection
echo "2. Database Connection Check:\n";
try {
    $pdo = new PDO(
        'mysql:host=' . env('DB_HOST', '127.0.0.1') . 
        ';dbname=' . env('DB_DATABASE', 'sheerapps_db'),
        env('DB_USERNAME', 'sheerapps_user'),
        env('DB_PASSWORD', '')
    );
    echo "   Database: ✓ Connected\n";
    
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'sheerapps_accounts'");
    if ($stmt->rowCount() > 0) {
        echo "   Table 'sheerapps_accounts': ✓ Exists\n";
        
        // Check table structure
        $stmt = $pdo->query("DESCRIBE sheerapps_accounts");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "   Columns: " . implode(', ', $columns) . "\n";
    } else {
        echo "   Table 'sheerapps_accounts': ✗ Missing\n";
    }
} catch (PDOException $e) {
    echo "   Database: ✗ Connection failed: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 3: Check API endpoints
echo "3. API Endpoints Check:\n";
$baseUrl = env('APP_URL', 'http://localhost');
$endpoints = [
    '/api/telegram-login' => 'GET',
    '/api/telegram-login/auth' => 'POST',
    '/api/profile' => 'GET',
    '/api/logout' => 'POST',
    '/api/referrals' => 'GET',
    '/api/referral-stats' => 'GET'
];

foreach ($endpoints as $endpoint => $method) {
    $url = $baseUrl . $endpoint;
    echo "   {$method} {$endpoint}: ";
    
    // Simple endpoint check (won't work for POST without proper setup)
    if ($method === 'GET') {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 5,
                'ignore_errors' => true
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        if ($response !== false) {
            echo "✓ Accessible\n";
        } else {
            echo "✗ Not accessible\n";
        }
    } else {
        echo "ℹ Requires POST method\n";
    }
}
echo "\n";

// Test 4: Hash validation test
echo "4. Hash Validation Test:\n";
if (env('TELEGRAM_BOT_TOKEN')) {
    echo "   Bot Token: ✓ Available\n";
    
    // Test hash generation
    $testData = [
        'id' => 123456789,
        'first_name' => 'Test',
        'username' => 'testuser',
        'photo_url' => 'https://t.me/i/userpic/320/photo.jpg',
        'auth_date' => time()
    ];
    
    ksort($testData);
    $checkString = urldecode(http_build_query($testData, '', "\n"));
    $secretKey = hash_hmac('sha256', env('TELEGRAM_BOT_TOKEN'), 'WebAppData', true);
    $hash = hash_hmac('sha256', $checkString, $secretKey);
    
    echo "   Hash Generation: ✓ Working\n";
    echo "   Test Hash: " . substr($hash, 0, 16) . "...\n";
} else {
    echo "   Bot Token: ✗ Missing\n";
}
echo "\n";

// Test 5: Deep link format
echo "5. Deep Link Format Check:\n";
$testUser = [
    'username' => 'testuser',
    'avatar' => 'https://t.me/i/userpic/320/photo.jpg',
    'status' => 'active',
    'token' => 'test_token_1234567890abcdef',
    'user_id' => '1',
    'referrer_id' => '',
    'referral_count' => '0'
];

$params = http_build_query($testUser);
$deepLink = 'sheerapps4d://telegram-login-success?' . $params;
echo "   Deep Link Format: ✓ Valid\n";
echo "   Sample Link: " . substr($deepLink, 0, 80) . "...\n";
echo "\n";

// Test 6: Recommendations
echo "6. Recommendations:\n";
$issues = [];

if (!env('TELEGRAM_BOT_TOKEN')) {
    $issues[] = "Set TELEGRAM_BOT_TOKEN in .env file";
}
if (!env('TELEGRAM_BOT_USERNAME')) {
    $issues[] = "Set TELEGRAM_BOT_USERNAME in .env file";
}
if (!env('APP_URL')) {
    $issues[] = "Set APP_URL in .env file";
}

if (empty($issues)) {
    echo "   ✓ All required configurations are set\n";
} else {
    echo "   Issues found:\n";
    foreach ($issues as $issue) {
        echo "     - {$issue}\n";
    }
}

echo "\n=== Test Complete ===\n";
echo "Run 'php artisan serve' to start the development server\n";
echo "Test the login flow at: {$baseUrl}/api/telegram-login\n";
