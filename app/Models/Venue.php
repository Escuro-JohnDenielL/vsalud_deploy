<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * A bookable venue (Villa I, Villa II, ...).
 *
 * The venue owns its photo gallery (venue_photos). Packages link to venues
 * through the package_venue pivot (a package may offer several venues), so the
 * same villa pictures are reused across every package instead of uploaded again.
 */
class Venue extends Model
{
    use SoftDeletes;

    protected $table = 'venues';

    protected $fillable = [
        'name',
        'slug',
        'capacity',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Venue $venue) {
            if (blank($venue->slug)) {
                $venue->slug = Str::slug($venue->name);
            }
        });
    }

    /**
     * Gallery photos, cover photo first.
     */
    public function photos(): HasMany
    {
        return $this->hasMany(VenuePhoto::class)->orderBy('sort_order')->orderBy('id');
    }

    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class, 'package_venue', 'venue_id', 'package_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Shape the venue for the patron gallery dialog (data-venues JSON).
     * Only photos that really exist on disk are exposed, so the carousel never
     * cycles into a 404 slide.
     */
    public function galleryPayload(): array
    {
        $photos = collect($this->photos ?? [])
            ->filter(fn ($photo) => is_string($photo->path ?? null) && \App\Support\PublicImage::exists($photo->path))
            ->map(fn ($photo) => \App\Support\PublicImage::url($photo->path))
            ->filter(fn ($url) => is_string($url) && $url !== '')
            ->values()
            ->all();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'capacity' => $this->capacity,
            'description' => $this->description,
            'photos' => $photos,
        ];
    }
}
