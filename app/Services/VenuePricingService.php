<?php

namespace App\Services;

use App\Models\Venue;
use App\Models\VenueRate;
use App\Support\VenueSlot;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class VenuePricingService
{
    /**
     * Rates the selected venue and period can use.
     *
     * @return Collection<int, VenueRate>
     */
    public function options(?string $venueName, ?string $period): Collection
    {
        $venue = $this->findVenue($venueName);
        if (! $venue) {
            return collect();
        }

        $rates = $venue->rates;
        $hasPeriodRates = $rates->contains(fn (VenueRate $rate) => $rate->period !== null);

        if (! $hasPeriodRates) {
            return $rates->values();
        }

        $normalizedPeriod = VenueSlot::normalizePeriod($period);
        if ($normalizedPeriod === null) {
            return collect();
        }

        return $rates
            ->filter(fn (VenueRate $rate) => $rate->period === null || $rate->period === $normalizedPeriod)
            ->values();
    }

    /**
     * Resolve the server-authoritative estimate, or reject a stale/invalid
     * selection. Unpriced venues remain bookable without an estimate.
     *
     * @return array{rate_id: int, venue: string, label: string, period: ?string, amount: string}|null
     */
    public function estimate(?string $venueName, ?string $period, mixed $rateId): ?array
    {
        $venue = $this->findVenue($venueName);
        if (! $venue) {
            return null;
        }

        $options = $this->options($venue->name, $period);
        if ($venue->rates->isEmpty()) {
            if ($rateId !== null) {
                throw ValidationException::withMessages([
                    'venue_rate_id' => 'The selected venue has no configured pricing option.',
                ]);
            }

            return null;
        }

        if ($options->isEmpty()) {
            throw ValidationException::withMessages([
                'venue_rate_id' => 'Select AM or PM to see this venue’s estimate.',
            ]);
        }

        $rate = $options->first(
            fn (VenueRate $option) => $rateId !== null && (string) $option->id === (string) $rateId
        );

        if (! $rate) {
            throw ValidationException::withMessages([
                'venue_rate_id' => 'Choose a valid pricing option for the selected venue and period.',
            ]);
        }

        return [
            'rate_id' => $rate->id,
            'venue' => $venue->name,
            'label' => $rate->label,
            'period' => $rate->period,
            'amount' => (string) $rate->price,
        ];
    }

    private function findVenue(?string $venueName): ?Venue
    {
        $normalizedName = mb_strtolower(trim((string) $venueName), 'UTF-8');
        if ($normalizedName === '' || $normalizedName === 'others') {
            return null;
        }

        return Venue::active()
            ->with('rates')
            ->get()
            ->first(fn (Venue $venue) => mb_strtolower(trim($venue->name), 'UTF-8') === $normalizedName);
    }
}
