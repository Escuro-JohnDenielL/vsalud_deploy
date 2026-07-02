<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuestConsent extends Model
{
    protected $fillable = [
        'guest_token',
        'label',
        'email',
        'consented_at',
        'expires_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'consented_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}
