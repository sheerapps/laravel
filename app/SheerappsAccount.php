<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SheerappsAccount extends Model
{
    protected $fillable = [
        'telegram_id', 'name', 'username', 'photo_url', 'api_token', 'referrer_id'
    ];
}
