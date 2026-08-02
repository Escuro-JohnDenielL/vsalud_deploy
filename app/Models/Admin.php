<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Admin extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'admin';
    protected $primaryKey = 'admin_id';

    public $timestamps = true;

    protected $fillable = [
        'email',
        'f_name',
        'l_name',
        'phone',
        'password',
        'profile_picture',
        'role',
        'mfa_secret',
        'mfa_method',
        'mfa_setup_completed_at',
        'last_login_ip',
        'last_login_at',
        'password_reset_code',
        'password_reset_code_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'mfa_secret',
    ];

    protected $casts = [
        'mfa_setup_completed_at' => 'datetime',
        'mfa_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password_reset_code_expires_at' => 'datetime',
    ];

    // Add accessor for full name
    public function getNameAttribute()
    {
        return trim($this->f_name . ' ' . $this->l_name);
    }

    // Add accessor for username (derived from email)
    public function getUsernameAttribute()
    {
        return explode('@', $this->email)[0];
    }

    // Override getAuthIdentifierName to use admin_id
    public function getAuthIdentifierName()
    {
        return 'admin_id';
    }

    /**
     * Check if MFA is enabled for this admin.
     */
    public function hasMfaEnabled(): bool
    {
        return $this->mfa_method !== null && $this->mfa_setup_completed_at !== null;
    }

    /**
     * Get the MFA method display name.
     */
    public function getMfaMethodDisplayAttribute(): string
    {
        return match ($this->mfa_method) {
            'email' => 'Email OTP',
            'authenticator' => 'Authenticator App',
            default => 'Not configured',
        };
    }

    /**
     * Trusted devices for this admin.
     */
    public function trustedDevices()
    {
        return $this->hasMany(AdminTrustedDevice::class, 'admin_id', 'admin_id');
    }

    /**
     * Get active (non-expired) trusted devices.
     */
    public function activeTrustedDevices()
    {
        return $this->trustedDevices()->where('expires_at', '>', now());
    }
}