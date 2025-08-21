<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\SheerappsAccount;
use Carbon\Carbon;

class EmailAuthController extends Controller
{
    /**
     * Check if email is already registered
     */
    public function checkEmail(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid email format',
                    'errors' => $validator->errors()
                ], 400);
            }

            $email = $request->email;
            $user = SheerappsAccount::where('email', $email)->first();

            return response()->json([
                'status' => 'success',
                'isRegistered' => $user ? true : false,
                'message' => $user ? 'Email is registered' : 'Email is not registered'
            ]);

        } catch (\Exception $e) {
            \Log::error('Email check error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Server error occurred',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Handle email login for existing users
     */
    public function emailLogin(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:6'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $email = $request->email;
            $password = $request->password;

            // Find user by email
            $user = SheerappsAccount::where('email', $email)->first();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Email not found'
                ], 404);
            }

            // Check if user has password (email users)
            if (!$user->password) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This account was created with Telegram. Please use Telegram login.'
                ], 400);
            }

            // Verify password
            if (!Hash::check($password, $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid password'
                ], 401);
            }

            // Update login info
            $user->updateLoginInfo();

            // Generate token
            $token = $this->generateToken($user);

            return response()->json([
                'status' => 'success',
                'message' => 'Login successful',
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'status' => $user->status,
                    'referrer_id' => $user->referrer_id,
                    'referral_count' => $user->referral_count,
                    'token' => $token,
                    'loginMethod' => 'email'
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Email login error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Server error occurred',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Handle email registration for new users
     */
    public function emailRegister(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'full_name' => 'required|string|max:255',
                'email' => 'required|email|unique:sheerapps_accounts,email',
                'password' => 'required|string|min:6',
                'referral_code' => 'nullable|string|max:50'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Check referral code if provided
            $referrerId = null;
            if ($request->referral_code) {
                $referrer = SheerappsAccount::where('referral_code', $request->referral_code)->first();
                if ($referrer) {
                    $referrerId = $referrer->id;
                }
            }

            // Generate unique referral code for new user
            $referralCode = $this->generateReferralCode();

            // Create user
            $user = SheerappsAccount::create([
                'username' => $request->full_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'referral_code' => $referralCode,
                'referrer_id' => $referrerId,
                'status' => 'pending_verification',
                'loginMethod' => 'email',
                'created_at' => Carbon::now('Asia/Kuala_Lumpur'),
                'updated_at' => Carbon::now('Asia/Kuala_Lumpur')
            ]);

            // Send OTP email
            $otp = $this->generateOTP();
            $this->sendOTPEmail($user->email, $otp);

            // Store OTP temporarily (you might want to use cache or a separate table)
            \Cache::put('otp_' . $user->email, $otp, 300); // 5 minutes expiry

            return response()->json([
                'status' => 'success',
                'message' => 'Registration successful. Please check your email for OTP.',
                'user_id' => $user->id
            ]);

        } catch (\Exception $e) {
            \Log::error('Email registration error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Server error occurred',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Verify OTP and complete registration
     */
    public function verifyOTP(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'otp' => 'required|string|size:6'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $email = $request->email;
            $otp = $request->otp;

            // Check if OTP matches
            $storedOTP = \Cache::get('otp_' . $email);
            if (!$storedOTP || $storedOTP !== $otp) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid or expired OTP'
                ], 400);
            }

            // Find user
            $user = SheerappsAccount::where('email', $email)->first();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found'
                ], 404);
            }

            // Activate user
            $user->update([
                'status' => 'active',
                'email_verified_at' => Carbon::now('Asia/Kuala_Lumpur'),
                'updated_at' => Carbon::now('Asia/Kuala_Lumpur')
            ]);

            // Clear OTP from cache
            \Cache::forget('otp_' . $email);

            // Update login info
            $user->updateLoginInfo();

            // Generate token
            $token = $this->generateToken($user);

            return response()->json([
                'status' => 'success',
                'message' => 'Email verification successful',
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'status' => $user->status,
                    'referrer_id' => $user->referrer_id,
                    'referral_count' => $user->referral_count,
                    'token' => $token,
                    'loginMethod' => 'email'
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('OTP verification error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Server error occurred',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Generate unique referral code
     */
    private function generateReferralCode()
    {
        do {
            $code = strtoupper(substr(md5(uniqid()), 0, 8));
        } while (SheerappsAccount::where('referral_code', $code)->exists());

        return $code;
    }

    /**
     * Generate 6-digit OTP
     */
    private function generateOTP()
    {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Send OTP email
     */
    private function sendOTPEmail($email, $otp)
    {
        // For now, just log the OTP. In production, you'd send an actual email
        \Log::info("OTP for {$email}: {$otp}");
        
        // TODO: Implement actual email sending
        // Mail::to($email)->send(new OTPMail($otp));
    }

    /**
     * Generate authentication token
     */
    private function generateToken($user)
    {
        // Generate a simple token for now. In production, use JWT or Laravel Sanctum
        return hash('sha256', $user->id . $user->email . time() . config('app.key'));
    }
}
