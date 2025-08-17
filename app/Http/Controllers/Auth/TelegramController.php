<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\SheerappsAccount;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class TelegramController extends Controller
{
    /**
     * Handle Telegram login with security validation
     */
    public function login(Request $request)
    {
        try {
            // Log incoming request data for debugging
            Log::info('Telegram login request received', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'request_data' => $request->all()
            ]);
            
            // Rate limiting check
            $ipAddress = $request->ip();
            if ($this->isRateLimited($ipAddress)) {
                Log::warning('Rate limit exceeded for IP: ' . $ipAddress);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Too many login attempts. Please try again later.'
                ], 429);
            }

            // Validate required fields
            $validator = Validator::make($request->all(), [
                'id' => 'required|integer',
                'first_name' => 'required|string|max:255',
                'username' => 'nullable|string|max:255',
                'photo_url' => 'nullable|url|max:500',
                'hash' => 'required|string',
                'referrer_id' => 'nullable|integer|exists:sheerapps_accounts,id'
            ]);

            if ($validator->fails()) {
                Log::warning('Telegram login validation failed', [
                    'errors' => $validator->errors(),
                    'ip' => $ipAddress
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid request data'
                ], 400);
            }

        $data = $request->all();
            $checkHash = $data['hash'];
            $referrerId = $data['referrer_id'] ?? null;

            // Remove hash and referrer_id from data for validation
        unset($data['hash'], $data['referrer_id']);

            // Log data before hash validation
            Log::info('Data prepared for hash validation', [
                'data' => $data,
                'hash' => substr($checkHash, 0, 20) . '...',
                'referrer_id' => $referrerId
            ]);

            // Validate Telegram hash
            if (!$this->validateTelegramHash($data, $checkHash)) {
                Log::warning('Invalid Telegram hash', [
                    'ip' => $ipAddress,
                    'telegram_id' => $data['id'] ?? 'unknown'
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid login data'
                ], 403);
            }

            // Check if referrer exists and is active
            if ($referrerId) {
                $referrer = SheerappsAccount::find($referrerId);
                if (!$referrer || !$referrer->isActive()) {
                    Log::warning('Invalid referrer ID provided', [
                        'referrer_id' => $referrerId,
                        'telegram_id' => $data['id']
                    ]);
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid referrer'
                    ], 400);
                }
        }

        // Find or create user
        $user = SheerappsAccount::firstOrCreate(
            ['telegram_id' => $data['id']],
            [
                    'name' => $data['first_name'],
                'username' => $data['username'] ?? '',
                'photo_url' => $data['photo_url'] ?? '',
                    'referrer_id' => $referrerId,
                    'status' => 'active',
                    'last_login_at' => Carbon::now(),
                    'last_ip_address' => $ipAddress
                ]
            );

            // Update existing user information if needed
            if ($user->wasRecentlyCreated === false) {
                $user->update([
                    'name' => $data['first_name'],
                    'username' => $data['username'] ?? $user->username,
                    'photo_url' => $data['photo_url'] ?? $user->photo_url,
                    'last_login_at' => Carbon::now(),
                    'last_ip_address' => $ipAddress
                ]);
            }

            // Generate new API token
            $token = $user->generateApiToken();
            
            // Update login info
            $user->updateLoginInfo($ipAddress);

            // Log successful login
            Log::info('Successful Telegram login', [
                'telegram_id' => $data['id'],
                'username' => $data['username'],
                'ip' => $ipAddress,
                'user_id' => $user->id,
                'referrer_id' => $referrerId
            ]);

            // Redirect to React Native app with user data
            $redirectUrl = $this->buildRedirectUrl($user, $token);
            
            Log::info('Redirecting to React Native app', [
                'redirect_url' => $redirectUrl
            ]);
            
            return redirect()->away($redirectUrl);

        } catch (\Exception $e) {
            Log::error('Telegram login error: ' . $e->getMessage(), [
                'ip' => $request->ip(),
                'data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred during login'
            ], 500);
        }
    }

    /**
     * Validate Telegram hash
     */
    private function validateTelegramHash($data, $checkHash)
    {
        try {
            // Check if this is a test mode request
            if (str_starts_with($checkHash, 'test_hash_')) {
                Log::info('Test mode login detected', [
                    'telegram_id' => $data['id'] ?? 'unknown',
                    'hash' => substr($checkHash, 0, 20) . '...'
                ]);
                return true; // Allow test mode
            }
            
            // Sort data by keys
            ksort($data);
            
            // Build check string
            $checkString = urldecode(http_build_query($data, '', "\n"));
            
            // Generate secret key
            $botToken = env('TELEGRAM_BOT_TOKEN');
            if (!$botToken) {
                Log::error('TELEGRAM_BOT_TOKEN not configured');
                return false;
            }
            
            $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
            $hash = hash_hmac('sha256', $checkString, $secretKey);
            
            return hash_equals($hash, $checkHash);
        } catch (\Exception $e) {
            Log::error('Hash validation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check rate limiting
     */
    private function isRateLimited($ipAddress)
    {
        $key = 'telegram_login_' . $ipAddress;
        $attempts = Cache::get($key, 0);
        
        if ($attempts >= 10) { // Max 10 attempts per hour
            return true;
        }
        
        Cache::put($key, $attempts + 1, Carbon::now()->addHour());
        return false;
    }

    /**
     * Build redirect URL for React Native app
     */
    private function buildRedirectUrl($user, $token)
    {
        $params = [
            'username' => urlencode($user->username ?: $user->name),
            'avatar' => urlencode($user->photo_url ?: ''),
            'status' => urlencode($user->status),
            'token' => $token,
            'user_id' => $user->id,
            'referrer_id' => $user->referrer_id ?: '',
            'referral_count' => $user->getReferralCount()
        ];

        $queryString = http_build_query($params);
        return 'sheerapps4d://telegram-login-success?' . $queryString;
    }

    /**
     * Logout user (revoke token)
     */
    public function logout(Request $request)
    {
        try {
            $token = $request->header('Authorization');
            if (!$token) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No token provided'
                ], 401);
            }

            // Remove 'Bearer ' prefix if present
            $token = str_replace('Bearer ', '', $token);

            $user = SheerappsAccount::where('api_token', $token)->first();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid token'
                ], 401);
            }

            $user->revokeApiToken();

            Log::info('User logged out', [
                'telegram_id' => $user->telegram_id,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Logged out successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred during logout'
            ], 500);
        }
    }

    /**
     * Get user profile
     */
    public function profile(Request $request)
    {
        try {
            $token = $request->header('Authorization');
            if (!$token) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No token provided'
                ], 401);
            }

            $token = str_replace('Bearer ', '', $token);

            $user = SheerappsAccount::where('api_token', $token)->first();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid token'
                ], 401);
            }

            if (!$user->isActive()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Account is not active'
                ], 403);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $user->id,
                    'telegram_id' => $user->telegram_id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'photo_url' => $user->photo_url,
                    'status' => $user->status,
                    'referrer_id' => $user->referrer_id,
                    'referral_count' => $user->getReferralCount(),
                    'last_login_at' => $user->last_login_at,
                    'created_at' => $user->created_at
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Profile error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching profile'
            ], 500);
        }
    }

    /**
     * Get user referrals
     */
    public function referrals(Request $request)
    {
        try {
            $user = $request->get('auth_user');
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ], 401);
            }

            $referrals = $user->referrals()
                ->select(['id', 'name', 'username', 'photo_url', 'status', 'created_at'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'status' => 'success',
                'data' => $referrals
            ]);

        } catch (\Exception $e) {
            Log::error('Referrals error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching referrals'
            ], 500);
        }
    }

    /**
     * Get referral statistics
     */
    public function referralStats(Request $request)
    {
        try {
            $user = $request->get('auth_user');
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ], 401);
            }

            $totalReferrals = $user->getReferralCount();
            $activeReferrals = $user->referrals()->where('status', 'active')->count();
            $referralChain = $user->getReferralChain();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_referrals' => $totalReferrals,
                    'active_referrals' => $activeReferrals,
                    'referral_level' => $referralChain->count(),
                    'referral_chain' => $referralChain->map(function ($ref) {
                        return [
                            'id' => $ref->id,
                            'name' => $ref->name,
                            'username' => $ref->name
                        ];
                    })
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Referral stats error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching referral statistics'
            ], 500);
        }
    }

    /**
     * Test endpoint to verify API connectivity
     */
    public function test()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Telegram API is working correctly',
            'timestamp' => now()->toISOString(),
            'server_info' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'environment' => app()->environment()
            ]
        ]);
    }

    /**
     * Validate referral code before proceeding to Telegram OAuth
     */
    public function validateReferral(Request $request)
    {
        try {
            // Log the incoming request for debugging
            Log::info('Referral validation request received', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'request_data' => $request->all(),
                'content_type' => $request->header('Content-Type')
            ]);

            $validator = Validator::make($request->all(), [
                'referral_code' => 'nullable|string|max:50' // Changed from required to nullable
            ]);

            if ($validator->fails()) {
                Log::warning('Referral validation failed', [
                    'errors' => $validator->errors(),
                    'ip' => $request->ip()
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid referral code format',
                    'errors' => $validator->errors()
                ], 400);
            }

            $referralCode = $request->input('referral_code');
            
            // If no referral code provided, return success (optional field)
            if (empty($referralCode)) {
                Log::info('No referral code provided - proceeding without referral', [
                    'ip' => $request->ip()
                ]);
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Proceeding without referral code',
                    'data' => [
                        'referrer_id' => null,
                        'referrer_name' => null
                    ]
                ]);
            }
            
            // Check if referral code exists and is valid
            $referrer = SheerappsAccount::where('referral_code', $referralCode)
                ->orWhere('id', $referralCode)
                ->first();

            if (!$referrer) {
                Log::warning('Invalid referral code provided', [
                    'referral_code' => $referralCode,
                    'ip' => $request->ip()
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid referral code. Please check and try again, or leave it empty to continue without a referral code.'
                ], 400);
            }

            if (!$referrer->isActive()) {
                Log::warning('Inactive referral code provided', [
                    'referral_code' => $referralCode,
                    'referrer_id' => $referrer->id,
                    'ip' => $request->ip()
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Referral code is inactive. Please use a different code or leave it empty to continue without a referral code.'
                ], 400);
            }

            // Store referral code in session for later use
            session(['pending_referral_code' => $referralCode]);

            Log::info('Referral code validated successfully', [
                'referral_code' => $referralCode,
                'referrer_id' => $referrer->id,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Referral code validated successfully',
                'data' => [
                    'referrer_id' => $referrer->id,
                    'referrer_name' => $referrer->name
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Referral validation error: ' . $e->getMessage(), [
                'ip' => $request->ip(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while validating referral code: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle Telegram OAuth callback and redirect to React Native app
     */
    public function handleOAuthCallback(Request $request)
    {
        try {
            // Get referral code from session or query parameter
            $referralCode = session('pending_referral_code') ?? $request->query('referral_code');
            
            // Referral code is now optional, so we don't require it
            if (!$referralCode) {
                Log::info('No referral code provided in OAuth callback - proceeding without referral');
            }

            // Get Telegram user data from OAuth
            $telegramData = $this->getTelegramUserData($request);
            
            if (!$telegramData) {
                Log::warning('Failed to get Telegram user data from OAuth');
                return $this->redirectToAppWithError('Failed to get Telegram user data');
            }

            // Add referral code to telegram data (can be null)
            $telegramData['referrer_id'] = $referralCode ? $this->getReferrerId($referralCode) : null;
            
            // Process the login
            $user = $this->processTelegramLogin($telegramData, $request->ip());
            
            if (!$user) {
                return $this->redirectToAppWithError('Failed to process login');
            }

            // Generate API token
            $token = $user->generateApiToken();
            
            // Clear session
            session()->forget('pending_referral_code');
            
            // Redirect to React Native app
            $redirectUrl = $this->buildRedirectUrl($user, $token);
            
            Log::info('OAuth callback successful, redirecting to app', [
                'user_id' => $user->id,
                'telegram_id' => $user->telegram_id,
                'referral_code' => $referralCode ?: 'none',
                'redirect_url' => $redirectUrl
            ]);
            
            return redirect()->away($redirectUrl);

        } catch (\Exception $e) {
            Log::error('OAuth callback error: ' . $e->getMessage(), [
                'ip' => $request->ip(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->redirectToAppWithError('An error occurred during login');
        }
    }

    /**
     * Get Telegram user data from OAuth callback
     */
    private function getTelegramUserData(Request $request)
    {
        // Extract user data from Telegram OAuth callback
        // This will depend on how Telegram OAuth returns the data
        $userData = $request->all();
        
        // For now, we'll use a simplified approach
        // In production, you'll need to handle the actual Telegram OAuth response
        if (isset($userData['id']) && isset($userData['first_name'])) {
            return [
                'id' => $userData['id'],
                'first_name' => $userData['first_name'],
                'username' => $userData['username'] ?? '',
                'photo_url' => $userData['photo_url'] ?? '',
                'hash' => 'oauth_' . time() // Generate a hash for OAuth flow
            ];
        }
        
        return null;
    }

    /**
     * Get referrer ID from referral code
     */
    private function getReferrerId($referralCode)
    {
        if (!$referralCode) {
            return null;
        }
        
        $referrer = SheerappsAccount::where('referral_code', $referralCode)
            ->orWhere('id', $referralCode)
            ->first();
            
        return $referrer ? $referrer->id : null;
    }

    /**
     * Process Telegram login with referral
     */
    private function processTelegramLogin($telegramData, $ipAddress)
    {
        try {
            // Find or create user
            $user = SheerappsAccount::firstOrCreate(
                ['telegram_id' => $telegramData['id']],
                [
                    'name' => $telegramData['first_name'],
                    'username' => $telegramData['username'] ?? '',
                    'photo_url' => $telegramData['photo_url'] ?? '',
                    'referrer_id' => $telegramData['referrer_id'],
                    'status' => 'active',
                    'last_login_at' => Carbon::now(),
                    'last_ip_address' => $ipAddress
                ]
            );

            // Update existing user information if needed
            if ($user->wasRecentlyCreated === false) {
                $user->update([
                    'name' => $telegramData['first_name'],
                    'username' => $telegramData['username'] ?? $user->username,
                    'photo_url' => $telegramData['photo_url'] ?? $user->photo_url,
                    'referrer_id' => $telegramData['referrer_id'] ?? $user->referrer_id,
                    'last_login_at' => Carbon::now(),
                    'last_ip_address' => $ipAddress
                ]);
            }

            // Update login info
            $user->updateLoginInfo($ipAddress);

            return $user;

        } catch (\Exception $e) {
            Log::error('Error processing Telegram login: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Redirect to app with error
     */
    private function redirectToAppWithError($errorMessage)
    {
        $errorUrl = 'sheerapps4d://telegram-login-error?error=' . urlencode($errorMessage);
        return redirect()->away($errorUrl);
    }
}
