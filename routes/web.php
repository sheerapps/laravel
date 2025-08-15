<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Auth\TelegramController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Telegram Login Page (for WebView)
Route::get('/telegram-login', function () {
    return view('telegram-login');
})->name('telegram.login.page');

// TERMS OF SERVICE
Route::get('/terms', function () {
    return response()->view('policies.terms');
});

// PRIVACY POLICY
Route::get('/privacy', function () {
    return response()->view('policies.privacy');
});

// LUCKY DRAW & REWARDS RULES
Route::get('/rules', function () {
    return response()->view('policies.rules');
});