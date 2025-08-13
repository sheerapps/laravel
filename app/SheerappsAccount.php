<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SheerappsAccount extends Model
{
    protected $fillable = [
        'telegram_id', 'username', 'first_name', 'last_name',
        'photo_url', 'referrer_id', 'api_token'
    ];
}

