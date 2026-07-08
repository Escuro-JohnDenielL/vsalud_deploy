<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormField extends Model
{
    protected $fillable = [
        'form_id',
        'field_type',
        'label',
        'name',
        'placeholder',
        'required',
        'order',
        'options',
        'validation_rules',
        'has_other_option',
        'is_fixed',
        'is_active',
    ];

    protected $casts = [
        'options' => 'array',
        'validation_rules' => 'array',
        'required' => 'boolean',
        'has_other_option' => 'boolean',
        'is_fixed' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }
}
