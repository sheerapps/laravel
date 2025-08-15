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
        'login_history' => 'array',
    ];

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
}
