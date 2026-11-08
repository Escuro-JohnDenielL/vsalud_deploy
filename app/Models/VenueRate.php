<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VenueRate extends Model
{
    protected $fillable = [
        'venue_id',
        'label',
        'period',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }
}
