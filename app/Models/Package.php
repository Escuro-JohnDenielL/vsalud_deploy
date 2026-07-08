<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends Model
{
    use SoftDeletes;

    protected $table = 'packages';

    protected $fillable = [
        'name',
        'description',
        'price',
        'image_path',
        'image_2_path', 
        'image_3_path',
        'inclusions'
    ];

    protected $casts = [
        'inclusions' => 'array',
        'price' => 'decimal:2'
    ];
}