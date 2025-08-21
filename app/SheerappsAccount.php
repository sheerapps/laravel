<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class SheerappsAccount extends Model
{
    protected $fillable = [
        'telegram_id', 'name', 'username', 'email', 'password', 'photo_url', 'api_token', 
        'referrer_id', 'status', 'last_login_at', 'last_ip_address', 'login_history', 'referral_code',
        'loginMethod', 'email_verified_at'
    ];

    protected $hidden = [
        'api_token', 'password',
    ];

    protected $casts = [
        'last_login_at' => 'datetime',
        'login_history' => 'array',
        'email_verified_at' => 'datetime',
    ];

    /**
     * Boot method to generate referral code when creating
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->referral_code)) {
                $model->referral_code = $model->generateUniqueReferralCode();
            }
            
            // Set default login method if not specified
            if (empty($model->loginMethod)) {
                $model->loginMethod = 'telegram';
            }
            
            // Set timezone to Malaysia Kuala Lumpur for timestamps
            $malaysiaTime = Carbon::now('Asia/Kuala_Lumpur');
            $model->created_at = $malaysiaTime;
            $model->updated_at = $malaysiaTime;
        });
        
        static::updating(function ($model) {
            // Update timestamp to Malaysia timezone
            $model->updated_at = Carbon::now('Asia/Kuala_Lumpur');
        });
    }

    /**
     * Get current Malaysia time
     */
    public static function getMalaysiaTime()
    {
        return Carbon::now('Asia/Kuala_Lumpur');
    }

    /**
     * Get the referrer user
     */
    public function referrer()
    {
        return $this->belongsTo(SheerappsAccount::class, 'referrer_id');
    }

    /**
     * Get all users referred by this user
     */
    public function referrals()
    {
        return $this->hasMany(SheerappsAccount::class, 'referrer_id');
    }

    /**
     * Generate a unique referral code
     */
    public function generateUniqueReferralCode()
    {
        $attempts = 0;
        $maxAttempts = 100;
        
        do {
            $attempts++;
            
            // Generate a more user-friendly code (6-8 characters, alphanumeric)
            $code = strtoupper(substr(md5(uniqid() . time() . rand(1000, 9999)), 0, 8));
            
            // Ensure it's not too similar to existing codes
            $similarCodes = static::where('referral_code', 'LIKE', substr($code, 0, 4) . '%')->count();
            
            // If we've tried too many times, use a different approach
            if ($attempts > $maxAttempts) {
                $code = 'REF' . strtoupper(substr(md5(uniqid() . microtime()), 0, 5));
            }
            
        } while (static::where('referral_code', $code)->exists() && $attempts <= $maxAttempts);
        
        // If still not unique, add timestamp
        if (static::where('referral_code', $code)->exists()) {
            $code = $code . substr(time(), -2);
        }
        
        return $code;
    }

    /**
     * Generate a new API token
     */
    public function generateApiToken()
    {
        $this->api_token = bin2hex(random_bytes(32));
        $this->save();
        return $this->api_token;
    }

    /**
     * Revoke API token
     */
    public function revokeApiToken()
    {
        $this->api_token = null;
        $this->save();
    }

    /**
     * Update login information
     */
    public function updateLoginInfo($ipAddress = null, $customTime = null)
    {
        // Use custom time if provided, otherwise use current time in Malaysia timezone
        $loginTime = $customTime ?: Carbon::now('Asia/Kuala_Lumpur');
        
        $this->last_login_at = $loginTime;
        $this->last_ip_address = $ipAddress;
        
        // Add to login history (keep last 10 entries)
        $history = $this->login_history ?? [];
        $history[] = [
            'timestamp' => $loginTime->toISOString(),
            'ip_address' => $ipAddress,
            'timezone' => 'Asia/Kuala_Lumpur',
            'method' => $this->loginMethod ?? 'telegram'
        ];
        
        // Keep only last 10 entries
        if (count($history) > 10) {
            $history = array_slice($history, -10);
        }
        
        $this->login_history = $history;
        $this->save();
    }

    /**
     * Check if user is active
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Get referral count
     */
    public function getReferralCount()
    {
        return $this->referrals()->count();
    }

    /**
     * Get referral chain (all users in referral tree)
     */
    public function getReferralChain()
    {
        $chain = collect();
        $current = $this;
        
        while ($current->referrer) {
            $chain->push($current->referrer);
            $current = $current->referrer;
        }
        
        return $chain;
    }

    /**
     * Check if user registered with email
     */
    public function isEmailUser()
    {
        return $this->loginMethod === 'email';
    }

    /**
     * Check if user registered with Telegram
     */
    public function isTelegramUser()
    {
        return $this->loginMethod === 'telegram';
    }

    /**
     * Check if email is verified
     */
    public function isEmailVerified()
    {
        return !is_null($this->email_verified_at);
    }
}
