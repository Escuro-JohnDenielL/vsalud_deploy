<?php

namespace Database\Seeders;

use App\Models\Venue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The bookable venues already used by the reservation form. Insert-only so a
 * redeploy never overwrites names/capacities an admin edited.
 */
class VenueSeeder extends Seeder
{
    public function run(): void
    {
        $venues = [
            ['name' => 'Villa I', 'capacity' => 200, 'sort_order' => 1],
            ['name' => 'Villa II', 'capacity' => 300, 'sort_order' => 2],
            ['name' => 'Elizabeth Hall', 'capacity' => null, 'sort_order' => 3],
            ['name' => 'Private Pool', 'capacity' => 50, 'sort_order' => 4],
            ['name' => 'Sabrina Hall', 'capacity' => 200, 'sort_order' => 5],
        ];

        foreach ($venues as $venue) {
            $model = Venue::withTrashed()->firstOrCreate(
                ['name' => $venue['name']],
                [
                    'slug' => Str::slug($venue['name']),
                    'capacity' => $venue['capacity'],
                    'sort_order' => $venue['sort_order'],
                    'is_active' => true,
                ]
            );

            if ($model->trashed()) {
                continue;
            }

            foreach (config('venue_pricing.defaults.' . $model->name, []) as $rate) {
                $query = $model->rates()->where('label', $rate['label']);
                $rate['period'] === null
                    ? $query->whereNull('period')
                    : $query->where('period', $rate['period']);

                if (! $query->exists()) {
                    $model->rates()->create($rate);
                }
            }
        }
    }
}
