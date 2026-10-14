<?php

namespace App\Support;

use App\Models\FormField;
use Illuminate\Support\Facades\Cache;

/**
 * The list of bookable venues.
 *
 * The reservation form field is the source of truth so the panel matches the
 * dropdown the patron actually sees. config('villasalud.venues') is only a
 * fallback for when that field is missing (for example before seeding).
 */
final class VenueCatalog
{
    private const CACHE_KEY = 'venue_catalog:reservation';
    private const CACHE_SECONDS = 300;

    /**
     * @return array<int, string>
     */
    public static function names(): array
    {
        $names = Cache::remember(self::CACHE_KEY, self::CACHE_SECONDS, function () {
            $field = FormField::query()
                ->where('name', 'venue')
                ->whereHas('form', fn ($query) => $query->where('slug', 'reservation'))
                ->orderBy('order')
                ->first();

            $names = [];

            foreach ((array) ($field?->options ?? []) as $option) {
                $value = is_array($option) ? ($option['value'] ?? null) : $option;

                if (is_string($value) && VenueSlot::isTrackable($value)) {
                    $names[] = trim($value);
                }
            }

            return array_values(array_unique($names));
        });

        return is_array($names) && $names !== [] ? $names : self::fallback();
    }

    /**
     * @return array<int, string>
     */
    private static function fallback(): array
    {
        return array_values(array_filter(
            array_map('strval', (array) config('villasalud.venues', [])),
            fn (string $venue) => VenueSlot::isTrackable($venue)
        ));
    }
}
