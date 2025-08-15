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
     * Show Telegram login page for WebView
     */
    public function showLoginPage(Request $request)
    {
        try {
            $referralId = $request->get('ref') ?? $request->get('referral_id');
            
            // Log referral attempt
            if ($referralId) {
                Log::info('Referral login attempt', [
                    'referral_id' => $referralId,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);
            }

            // Return HTML page for WebView
            return view('auth.telegram-login', [
                'referralId' => $referralId,
                'botUsername' => env('TELEGRAM_BOT_USERNAME'),
                'appName' => env('APP_NAME', 'SheerApps 4D')
            ]);

        } catch (\Exception $e) {
            Log::error('Error showing login page: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load login page'
            ], 500);
        }
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

            // Get referral ID from various sources
            $referralId = $request->get('referrer_id') ?? 
                         $request->get('ref') ?? 
                         $request->get('referral_id') ?? 
                         null;

            // Validate required fields
            $validator = Validator::make($request->all(), [
                'id' => 'required|integer',
                'first_name' => 'required|string|max:255',
                'username' => 'nullable|string|max:255',
                'photo_url' => 'nullable|url|max:500',
                'hash' => 'required|string',
            ]);

            if ($validator->fails()) {
                Log::warning('Telegram login validation failed', [
                    'errors' => $validator->errors(),
                    'ip' => $ipAddress,
                    'referral_id' => $referralId
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid request data'
                ], 400);
            }

            $data = $request->all();
            $checkHash = $data['hash'];

            // Remove hash and referral-related fields from data for validation
            unset($data['hash'], $data['referrer_id'], $data['ref'], $data['referral_id']);

            // Validate Telegram hash
            if (!$this->validateTelegramHash($data, $checkHash)) {
                Log::warning('Invalid Telegram hash', [
                    'ip' => $ipAddress,
                    'telegram_id' => $data['id'] ?? 'unknown',
                    'referral_id' => $referralId
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid login data'
                ], 403);
            }

            // Check if referrer exists and is active
            if ($referralId) {
                $referrer = SheerappsAccount::find($referralId);
                if (!$referrer || !$referrer->isActive()) {
                    Log::warning('Invalid referrer ID provided', [
                        'referrer_id' => $referralId,
                        'telegram_id' => $data['id']
                    ]);
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid referrer'
                    ], 400);
                }

                // Log successful referral
                Log::info('Successful referral login', [
                    'referrer_id' => $referralId,
                    'new_user_telegram_id' => $data['id'],
                    'ip' => $ipAddress
                ]);
            }

            // Find or create user
            $user = SheerappsAccount::firstOrCreate(
                ['telegram_id' => $data['id']],
                [
                    'name' => $data['first_name'],
                    'username' => $data['username'] ?? '',
                    'photo_url' => $data['photo_url'] ?? '',
                    'referrer_id' => $referralId,
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

                // Update referrer if it changed
                if ($referralId && $user->referrer_id !== $referralId) {
                    $user->update(['referrer_id' => $referralId]);
                }
            }

            // Generate new API token
            $token = $user->generateApiToken();
            
            // Update login info
            $user->updateLoginInfo($ipAddress);

            // Log successful login
            Log::info('Successful Telegram login', [
                'telegram_id' => $data['id'],
                'username' => $data['username'],
                'user_id' => $user->id,
                'referrer_id' => $referralId,
                'ip' => $ipAddress
            ]);

            // Redirect to React Native app with user data
            $redirectUrl = $this->buildRedirectUrl($user, $token);
            
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
                'user_id' => $user->id,
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
