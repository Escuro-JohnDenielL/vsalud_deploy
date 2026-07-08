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
    ];

    protected $hidden = [
        'password',
        'remember_token',
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
}