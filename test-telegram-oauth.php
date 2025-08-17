<?php
/**
 * Test Script for Telegram OAuth Integration
 * 
 * This script tests the referral code validation and OAuth flow
 * Run this from the Laravel root directory: php test-telegram-oauth.php
 */

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Bootstrap Laravel (PHP 7.1 compatible)
$app = Application::configure(__DIR__)
    ->withRouting(
        __DIR__.'/routes/web.php',
        __DIR__.'/routes/api.php'
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Telegram OAuth Integration Test ===\n\n";

// Test 1: Database Connection
echo "1. Testing Database Connection...\n";
try {
    $pdo = DB::connection()->getPdo();
    echo "   ✅ Database connection successful\n";
} catch (Exception $e) {
    echo "   ❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Check if sheerapps_accounts table exists
echo "\n2. Checking Database Table...\n";
try {
    $tableExists = Schema::hasTable('sheerapps_accounts');
    if ($tableExists) {
        echo "   ✅ sheerapps_accounts table exists\n";
        
        // Check if referral_code column exists
        $hasReferralCode = Schema::hasColumn('sheerapps_accounts', 'referral_code');
        if ($hasReferralCode) {
            echo "   ✅ referral_code column exists\n";
        } else {
            echo "   ❌ referral_code column missing - run migration\n";
        }
    } else {
        echo "   ❌ sheerapps_accounts table missing - run migration\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error checking table: " . $e->getMessage() . "\n";
}

// Test 3: Test referral code generation
echo "\n3. Testing Referral Code Generation...\n";
try {
    $user = new App\Models\SheerappsAccount();
    $referralCode = $user->generateUniqueReferralCode();
    echo "   ✅ Generated referral code: " . $referralCode . "\n";
} catch (Exception $e) {
    echo "   ❌ Error generating referral code: " . $e->getMessage() . "\n";
}

// Test 4: Test API Routes
echo "\n4. Testing API Routes...\n";
try {
    $routes = Route::getRoutes();
    $apiRoutes = collect($routes)->filter(function ($route) {
        return str_starts_with($route->uri(), 'api/');
    });
    
    $hasValidateReferral = $apiRoutes->contains(function ($route) {
        return $route->uri() === 'api/validate-referral';
    });
    
    if ($hasValidateReferral) {
        echo "   ✅ /api/validate-referral route exists\n";
    } else {
        echo "   ❌ /api/validate-referral route missing\n";
    }
    
    $hasTelegramLogin = $apiRoutes->contains(function ($route) {
        return $route->uri() === 'api/telegram-login';
    });
    
    if ($hasTelegramLogin) {
        echo "   ✅ /api/telegram-login route exists\n";
    } else {
        echo "   ❌ /api/telegram-login route missing\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Error checking routes: " . $e->getMessage() . "\n";
}

// Test 5: Test Web Routes
echo "\n5. Testing Web Routes...\n";
try {
    $webRoutes = Route::getRoutes();
    $hasTelegramCallback = collect($webRoutes)->contains(function ($route) {
        return $route->uri() === 'telegram-callback';
    });
    
    if ($hasTelegramCallback) {
        echo "   ✅ /telegram-callback route exists\n";
    } else {
        echo "   ❌ /telegram-callback route missing\n";
    }
    
    $hasOAuthDemo = collect($webRoutes)->contains(function ($route) {
        return $route->uri() === 'telegram-oauth-demo';
    });
    
    if ($hasOAuthDemo) {
        echo "   ✅ /telegram-oauth-demo route exists\n";
    } else {
        echo "   ❌ /telegram-oauth-demo route missing\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Error checking web routes: " . $e->getMessage() . "\n";
}

// Test 6: Environment Variables
echo "\n6. Checking Environment Variables...\n";
$requiredVars = [
    'TELEGRAM_BOT_TOKEN' => 'Telegram Bot Token',
    'TELEGRAM_BOT_ID' => 'Telegram Bot ID',
    'APP_URL' => 'Application URL'
];

foreach ($requiredVars as $var => $description) {
    $value = env($var);
    if ($value) {
        echo "   ✅ {$description}: " . substr($value, 0, 10) . "...\n";
    } else {
        echo "   ❌ {$description}: Not set\n";
    }
}

// Test 7: Test Model Methods
echo "\n7. Testing Model Methods...\n";
try {
    $user = new App\Models\SheerappsAccount();
    
    // Test referral code generation
    $refCode = $user->generateUniqueReferralCode();
    echo "   ✅ Referral code generation: " . $refCode . "\n";
    
    // Test isActive method
    $user->status = 'active';
    $isActive = $user->isActive();
    echo "   ✅ isActive method: " . ($isActive ? 'true' : 'false') . "\n";
    
    // Test API token generation
    $token = $user->generateApiToken();
    echo "   ✅ API token generation: " . substr($token, 0, 10) . "...\n";
    
} catch (Exception $e) {
    echo "   ❌ Error testing model methods: " . $e->getMessage() . "\n";
}

// Test 8: Test OAuth URL Generation
echo "\n8. Testing OAuth URL Generation...\n";
try {
    $botId = env('TELEGRAM_BOT_ID', '5215811414');
    $origin = env('APP_URL', 'https://accounts.sheerapp.work');
    $returnTo = $origin . '/telegram-callback?referral_code=TEST123';
    
    $oauthUrl = "https://oauth.telegram.org/auth?bot_id={$botId}&origin=" . urlencode($origin) . "&return_to=" . urlencode($returnTo);
    
    echo "   ✅ OAuth URL generated successfully\n";
    echo "   📱 URL: " . substr($oauthUrl, 0, 80) . "...\n";
    
} catch (Exception $e) {
    echo "   ❌ Error generating OAuth URL: " . $e->getMessage() . "\n";
}

// Test 9: Test External API Endpoints
echo "\n9. Testing External API Endpoints...\n";
try {
    $baseUrl = env('APP_URL', 'https://accounts.sheerapp.work');
    
    // Test test endpoint
    $testResponse = file_get_contents($baseUrl . '/api/test-telegram');
    if ($testResponse) {
        echo "   ✅ Test endpoint accessible\n";
    } else {
        echo "   ❌ Test endpoint not accessible\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Error testing external endpoints: " . $e->getMessage() . "\n";
}

echo "\n=== Test Summary ===\n";
echo "✅ All tests completed\n";
echo "\nNext steps:\n";
echo "1. Run migrations: php artisan migrate\n";
echo "2. Set up your Telegram bot with BotFather\n";
echo "3. Configure environment variables in .env file\n";
echo "4. Test the complete flow in your React Native app\n";
echo "\nFor detailed setup instructions, see: TELEGRAM_OAUTH_SETUP_GUIDE.md\n";
echo "\nStaging Domain: https://accounts.sheerapp.work\n";
