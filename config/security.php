<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains security-related configuration for the application
    | including rate limiting, API security, and Telegram bot settings.
    |
    */

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'bot_username' => env('TELEGRAM_BOT_USERNAME'),
        'webapp_data_secret' => 'WebAppData',
    ],

    'api' => [
        'token_length' => 64,
        'token_expiry_hours' => env('API_TOKEN_EXPIRY_HOURS', 24),
        'max_login_attempts' => env('MAX_LOGIN_ATTEMPTS', 5),
        'login_timeout_minutes' => env('LOGIN_TIMEOUT_MINUTES', 15),
    ],

    'rate_limiting' => [
        'telegram_login' => [
            'max_attempts' => env('RATE_LIMIT_ATTEMPTS', 10),
            'decay_minutes' => env('RATE_LIMIT_DECAY_MINUTES', 60),
        ],
        'api_requests' => [
            'max_attempts' => 100,
            'decay_minutes' => 1,
        ],
    ],

    'session' => [
        'secure_cookie' => env('SESSION_SECURE_COOKIE', true),
        'http_only' => env('SESSION_HTTP_ONLY', true),
        'same_site' => env('SESSION_SAME_SITE', 'strict'),
        'lifetime' => env('SESSION_LIFETIME', 120),
    ],

    'headers' => [
        'x_frame_options' => 'DENY',
        'x_content_type_options' => 'nosniff',
        'x_xss_protection' => '1; mode=block',
        'referrer_policy' => 'strict-origin-when-cross-origin',
        'permissions_policy' => 'geolocation=(), microphone=(), camera=()',
    ],

    'cors' => [
        'allowed_origins' => [
            'https://yourdomain.com',
            'sheerapps4d://',
        ],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
        'exposed_headers' => [],
        'max_age' => 86400,
        'supports_credentials' => false,
    ],
];
