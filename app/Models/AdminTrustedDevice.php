<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminTrustedDevice extends Model
{
    protected $table = 'admin_trusted_devices';

    protected $fillable = [
        'admin_id',
        'device_token',
        'device_name',
        'ip_address',
        'user_agent',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'admin_id');
    }

    /**
     * Check if this device trust is still valid.
     */
    public function isValid(): bool
    {
        return $this->expires_at->isFuture();
    }
}
