<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Availability extends Model
{
    protected $table = 'availabilities';

    protected $fillable = ['date', 'status', 'is_override'];

    protected $casts = [
        'date' => 'date',
        'is_override' => 'boolean',
    ];
}
