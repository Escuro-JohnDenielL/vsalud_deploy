<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'is_published',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_published' => 'boolean',
    ];

    public function fields()
    {
        return $this->hasMany(FormField::class)->orderBy('order');
    }

    public function activeFields()
    {
        return $this->hasMany(FormField::class)
            ->where('is_active', true)
            ->orderBy('order');
    }

    public function publishedFields()
    {
        return $this->hasMany(FormField::class)
            ->where('is_active', true)
            ->whereHas('form', fn($q) => $q->where('is_published', true))
            ->orderBy('order');
    }
}
