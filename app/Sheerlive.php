<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Sheerlive extends Model
{
    protected $table = "sheerlive";
    protected $fillable = ["id","type","data","updated_at"];
}
