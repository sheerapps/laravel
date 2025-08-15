<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Auth\TelegramController;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public routes (no authentication required)
Route::match(['GET', 'POST'], '/telegram-login', [TelegramController::class, 'login'])->name('telegram.login');

// New direct login routes (eliminates web page step)
Route::post('/telegram-direct-login', [TelegramController::class, 'directLogin'])->name('telegram.direct.login');
Route::post('/telegram-verify', [TelegramController::class, 'verifyLogin'])->name('telegram.verify');

// Protected routes (require authentication)
Route::middleware(['api.auth'])->group(function () {
    // User management
    Route::get('/profile', [TelegramController::class, 'profile'])->name('user.profile');
    Route::post('/logout', [TelegramController::class, 'logout'])->name('user.logout');
    
    // Referral management
    Route::get('/referrals', [TelegramController::class, 'referrals'])->name('user.referrals');
    Route::get('/referral-stats', [TelegramController::class, 'referralStats'])->name('user.referral.stats');
    
    // Test endpoint
    Route::get('/test', [ApiController::class, 'test'])->name('api.test');
});

// Fallback for undefined routes
Route::fallback(function () {
    return response()->json([
        'status' => 'error',
        'message' => 'Endpoint not found'
    ], 404);
});
