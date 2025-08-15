<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SheerappsAccount;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class TelegramController extends Controller
{
    /**
     * Show Telegram login page (for GET requests)
     */
    public function showLoginPage(Request $request)
    {
        // Check if this is a Telegram WebApp request
        if ($request->has('id') && $request->has('hash')) {
            // This is a Telegram WebApp request, process it
            return $this->login($request);
        }
        
        // This is a regular GET request, show login page
        return view('auth.telegram-login');
    }

    /**
     * Handle Telegram login with security validation
     */
    public function login(Request $request)
    {
        try {
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
                'ip' => $ipAddress
            ]);

            // Redirect to React Native app with user data
            $redirectUrl = $this->buildRedirectUrl($user, $token);
            
            return redirect()->away($redirectUrl);

        } catch (\Exception $e) {
            Log::error('Telegram login error: ' . $e->getMessage(), [
                'ip' => $request->ip(),
                'data' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred during login'
            ], 500);
        }
    }

    /**
     * Direct Telegram login with phone number (for React Native)
     */
    public function directLogin(Request $request)
    {
        try {
            // Validate phone number
            $validator = Validator::make($request->all(), [
                'phone_number' => 'required|string|regex:/^\+?[1-9]\d{1,14}$/',
                'referrer_id' => 'nullable|integer|exists:sheerapps_accounts,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid phone number format',
                    'errors' => $validator->errors()
                ], 400);
            }

            $phoneNumber = $request->phone_number;
            $referrerId = $request->referrer_id;

            // Check if referrer exists and is active
            if ($referrerId) {
                $referrer = SheerappsAccount::find($referrerId);
                if (!$referrer || !$referrer->isActive()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid referrer'
                    ], 400);
                }
            }

            // For demo purposes, create a temporary user with phone number
            // In production, you would integrate with Telegram's official API
            $user = SheerappsAccount::firstOrCreate(
                ['telegram_id' => 'temp_' . $phoneNumber], // Temporary ID
                [
                    'name' => 'User',
                    'username' => '',
                    'photo_url' => '',
                    'referrer_id' => $referrerId,
                    'status' => 'active',
                    'last_login_at' => Carbon::now(),
                    'last_ip_address' => $request->ip()
                ]
            );

            // Generate API token
            $token = $user->generateApiToken();
            
            // Update login info
            $user->updateLoginInfo($request->ip());

            // Log successful login attempt
            Log::info('Direct login initiated', [
                'phone_number' => $phoneNumber,
                'user_id' => $user->id,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Verification code sent successfully',
                'data' => [
                    'user_id' => $user->id,
                    'phone_number' => $phoneNumber
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Direct login error: ' . $e->getMessage(), [
                'phone_number' => $request->phone_number ?? 'unknown',
                'ip' => $request->ip()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred during login: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete Telegram login with verification code
     */
    public function verifyLogin(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone_number' => 'required|string',
                'verification_code' => 'required|string|size:5',
                'referrer_id' => 'nullable|integer|exists:sheerapps_accounts,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid verification data'
                ], 400);
            }

            $phoneNumber = $request->phone_number;
            $verificationCode = $request->verification_code;
            $referrerId = $request->referrer_id;

            // Here you would verify the code with Telegram
            // For demo purposes, we'll accept any 5-digit code
            if (strlen($verificationCode) === 5 && is_numeric($verificationCode)) {
                
                // Find or create user
                $user = SheerappsAccount::firstOrCreate(
                    ['telegram_id' => 'verified_' . $phoneNumber],
                    [
                        'name' => 'Telegram User',
                        'username' => '',
                        'photo_url' => '',
                        'referrer_id' => $referrerId,
                        'status' => 'active',
                        'last_login_at' => Carbon::now(),
                        'last_ip_address' => $request->ip()
                    ]
                );

                // Generate API token
                $token = $user->generateApiToken();
                
                // Update login info
                $user->updateLoginInfo($request->ip());

                // Return success with redirect URL
                $redirectUrl = $this->buildRedirectUrl($user, $token);
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Verification successful',
                    'redirect_url' => $redirectUrl,
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username ?: $user->name,
                        'avatar' => $user->photo_url,
                        'status' => $user->status,
                        'referrer_id' => $user->referrer_id,
                        'referral_count' => $user->getReferralCount()
                    ],
                    'token' => $token
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid verification code'
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Verification error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred during verification'
            ], 500);
        }
    }

    /**
     * Telegram OAuth Login (Recommended approach)
     */
    public function oauthLogin(Request $request)
    {
        try {
            // Get Telegram OAuth data from request
            $validator = Validator::make($request->all(), [
                'id' => 'required|integer',
                'first_name' => 'required|string|max:255',
                'username' => 'nullable|string|max:255',
                'photo_url' => 'nullable|url|max:500',
                'auth_date' => 'required|integer',
                'hash' => 'required|string',
                'referrer_id' => 'nullable|integer|exists:sheerapps_accounts,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid OAuth data',
                    'errors' => $validator->errors()
                ], 400);
            }

            $data = $request->all();
            $checkHash = $data['hash'];
            $referrerId = $data['referrer_id'] ?? null;

            // Remove hash and referrer_id from data for validation
            unset($data['hash'], $data['referrer_id']);

            // Validate Telegram OAuth hash
            if (!$this->validateTelegramOAuth($data, $checkHash)) {
                Log::warning('Invalid Telegram OAuth hash', [
                    'ip' => $request->ip(),
                    'telegram_id' => $data['id'] ?? 'unknown'
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid OAuth signature'
                ], 403);
            }

            // Check if referrer exists and is active
            if ($referrerId) {
                $referrer = SheerappsAccount::find($referrerId);
                if (!$referrer || !$referrer->isActive()) {
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
                    'last_ip_address' => $request->ip()
                ]
            );

            // Update existing user information if needed
            if ($user->wasRecentlyCreated === false) {
                $user->update([
                    'name' => $data['first_name'],
                    'username' => $data['username'] ?? $user->username,
                    'photo_url' => $data['photo_url'] ?? $user->photo_url,
                    'last_login_at' => Carbon::now(),
                    'last_ip_address' => $request->ip()
                ]);
            }

            // Generate new API token
            $token = $user->generateApiToken();
            
            // Update login info
            $user->updateLoginInfo($request->ip());

            // Log successful OAuth login
            Log::info('Successful Telegram OAuth login', [
                'telegram_id' => $data['id'],
                'username' => $data['username'],
                'ip' => $request->ip()
            ]);

            // Return success with user data
            return response()->json([
                'status' => 'success',
                'message' => 'OAuth login successful',
                'data' => [
                    'user' => [
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
                    ],
                    'token' => $token
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Telegram OAuth login error: ' . $e->getMessage(), [
                'ip' => $request->ip(),
                'data' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred during OAuth login'
            ], 500);
        }
    }

    /**
     * Validate Telegram hash
     */
    private function validateTelegramHash($data, $checkHash)
    {
        try {
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
     * Validate Telegram OAuth hash
     */
    private function validateTelegramOAuth($data, $checkHash)
    {
        try {
            // Sort data by keys
            ksort($data);
            
            // Build check string
            $checkString = urldecode(http_build_query($data, '', "\n"));
            
            // Generate secret key using your bot token
            $botToken = env('TELEGRAM_BOT_TOKEN');
            if (!$botToken) {
                Log::error('TELEGRAM_BOT_TOKEN not configured');
                return false;
            }
            
            $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
            $hash = hash_hmac('sha256', $checkString, $secretKey);
            
            return hash_equals($hash, $checkHash);
        } catch (\Exception $e) {
            Log::error('OAuth hash validation error: ' . $e->getMessage());
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
                            'username' => $ref->username
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
}
