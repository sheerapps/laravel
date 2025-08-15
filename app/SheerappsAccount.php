<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class SheerappsAccount extends Model
{
    protected $fillable = [
        'telegram_id', 'name', 'username', 'photo_url', 'api_token', 
        'referrer_id', 'status', 'last_login_at', 'last_ip_address', 'login_history'
    ];

    protected $hidden = [
        'api_token',
    ];

    protected $casts = [
        'last_login_at' => 'datetime',
        'login_history' => 'array', // This will still work with TEXT field
    ];

    // Valid status values
    const STATUS_ACTIVE = 'active';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_BANNED = 'banned';

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
    public function updateLoginInfo($ipAddress = null)
    {
        $this->last_login_at = Carbon::now();
        $this->last_ip_address = $ipAddress;
        
        // Add to login history (keep last 10 entries)
        $history = $this->login_history ?? [];
        $history[] = [
            'timestamp' => Carbon::now()->toISOString(),
            'ip_address' => $ipAddress,
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
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if status is valid
     */
    public function isValidStatus($status)
    {
        return in_array($status, [self::STATUS_ACTIVE, self::STATUS_SUSPENDED, self::STATUS_BANNED]);
    }

    /**
     * Set status with validation
     */
    public function setStatus($status)
    {
        if ($this->isValidStatus($status)) {
            $this->status = $status;
            $this->save();
            return true;
        }
        return false;
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
     * Accessor for login_history to ensure it's always an array
     */
    public function getLoginHistoryAttribute($value)
    {
        if (empty($value)) {
            return [];
        }
        
        // Try to decode JSON, fallback to empty array if it fails
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Mutator for login_history to ensure it's stored as JSON string
     */
    public function setLoginHistoryAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['login_history'] = json_encode($value);
        } else {
            $this->attributes['login_history'] = $value;
        }
    }

    /**
     * Boot method to set default status
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->status)) {
                $model->status = self::STATUS_ACTIVE;
            }
        });
    }
}
