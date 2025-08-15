<?php
/**
 * Test Script for Telegram Login
 * Run this script to test the login functionality without the React Native app
 */

// Configuration
$baseUrl = 'http://localhost:8000'; // Change this to your Laravel server URL
$testData = [
    'id' => 123456789,
    'first_name' => 'Test User',
    'username' => 'testuser',
    'photo_url' => 'https://t.me/i/userpic/320/photo.jpg',
    'hash' => 'test_hash_' . time(),
    'referrer_id' => null
];

echo "🧪 Testing Telegram Login API\n";
echo "=============================\n\n";

echo "📡 Base URL: {$baseUrl}\n";
echo "🔑 Test Hash: {$testData['hash']}\n";
echo "👤 Test User: {$testData['first_name']} (@{$testData['username']})\n\n";

// Test 1: Check if the login page is accessible
echo "1️⃣ Testing login page accessibility...\n";
$loginPageUrl = $baseUrl . '/telegram-login';
$loginPageResponse = file_get_contents($loginPageUrl);

if ($loginPageResponse !== false) {
    echo "✅ Login page is accessible\n";
} else {
    echo "❌ Login page is not accessible\n";
    exit(1);
}

// Test 2: Test the API endpoint
echo "\n2️⃣ Testing API endpoint...\n";
$apiUrl = $baseUrl . '/api/telegram-login';

// Prepare the request
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => [
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: TestScript/1.0'
        ],
        'content' => json_encode($testData)
    ]
]);

// Make the request
$response = file_get_contents($apiUrl, false, $context);

if ($response === false) {
    echo "❌ API request failed\n";
    exit(1);
}

// Get response headers
$responseHeaders = $http_response_header ?? [];
$statusLine = $responseHeaders[0] ?? '';

echo "📊 Response Status: {$statusLine}\n";

// Check if it's a redirect
if (strpos($statusLine, '302') !== false || strpos($statusLine, '301') !== false) {
    echo "✅ API returned redirect (expected for successful login)\n";
    
    // Extract redirect location
    foreach ($responseHeaders as $header) {
        if (strpos($header, 'Location:') === 0) {
            $redirectUrl = trim(substr($header, 9));
            echo "🔄 Redirect URL: {$redirectUrl}\n";
            
            if (strpos($redirectUrl, 'sheerapps4d://') === 0) {
                echo "✅ Redirect URL format is correct (React Native deep link)\n";
                
                // Parse the deep link parameters
                $queryString = parse_url($redirectUrl, PHP_URL_QUERY);
                parse_str($queryString, $params);
                
                echo "\n📱 Deep Link Parameters:\n";
                foreach ($params as $key => $value) {
                    echo "   {$key}: {$value}\n";
                }
            } else {
                echo "❌ Redirect URL format is incorrect\n";
            }
            break;
        }
    }
} else {
    echo "📄 Response Body: {$response}\n";
    
    // Try to parse JSON response
    $responseData = json_decode($response, true);
    if ($responseData) {
        echo "📋 Response Data:\n";
        print_r($responseData);
    }
}

echo "\n🎯 Test completed!\n";

// Additional debugging information
echo "\n🔍 Debug Information:\n";
echo "   - Test data sent: " . json_encode($testData, JSON_PRETTY_PRINT) . "\n";
echo "   - API endpoint: {$apiUrl}\n";
echo "   - Login page: {$loginPageUrl}\n";

echo "\n💡 Next Steps:\n";
echo "   1. Check Laravel logs for detailed information\n";
echo "   2. Verify database connection and migrations\n";
echo "   3. Test with React Native WebView\n";
echo "   4. Configure your Telegram bot for production use\n";
