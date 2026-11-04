<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'inclusions',
    ];

    protected $casts = [
        'inclusions' => 'array',
        'price' => 'decimal:2'
    ];

    /**
     * Venues this package can be held in (normally one, sometimes more).
     *
     * The venue owns its photo gallery, so a package stores only the link and
     * the same villa photos are reused by every package that lists that venue.
     */
    public function venues(): BelongsToMany
    {
        return $this->belongsToMany(Venue::class, 'package_venue', 'package_id', 'venue_id')
            ->orderBy('venues.sort_order')
            ->orderBy('venues.name');
    }
}