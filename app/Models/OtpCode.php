<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    protected $fillable = [
        'phone',
        'code',
        'expires_at',
        'used',
        'attempts'
    ];


    protected $casts = [
        'expires_at' => 'datetime',
        'used' => 'boolean'
    ];


    
}
