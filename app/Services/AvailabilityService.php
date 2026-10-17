<?php

namespace App\Services;

use App\Models\Availability;
use App\Models\Reservation;
use App\Support\AvailabilityScale;
use App\Support\VenueCatalog;
use Illuminate\Support\Carbon;

/**
 * Calendar availability for a date, counted in events.
 *
 * A day's count is the number of ACTIVE reservations on that date, across all
 * venues and periods. Pending inquiries never count, and a cancelled
 * reservation frees its place. The venue rule (one booking per venue + date +
 * period) is enforced separately by VenueOccupancyService.
 *
 * An admin override on the date always wins over the computed count.
 */
class AvailabilityService
{
    /**
     * The configured daily cap (max_events_per_day).
     */
    public function cap(): int
    {
        return AvailabilityScale::cap();
    }

    /**
     * Active reservations on a date. One reservation is one event, even when
     * its period cannot be determined.
     */
    public function activeEventCount(?string $date): int
    {
        if (blank($date)) {
            return 0;
        }

        return (int) Reservation::query()
            ->where('status', 'active')
            ->where('date', '>=', $date)
            ->where('date', '<', Carbon::parse($date)->addDay()->toDateString())
            ->count();
    }

    /**
     * True when the date has reached the daily cap.
     */
    public function isAtCapacity(?string $date): bool
    {
        return $this->activeEventCount($date) >= $this->cap();
    }

    /**
     * An admin override of "Closed" blocks every venue on that date.
     */
    public function isClosed(?string $date): bool
    {
        if (blank($date)) {
            return false;
        }

        return Availability::query()
            ->where('date', '>=', $date)
            ->where('date', '<', Carbon::parse($date)->addDay()->toDateString())
            ->where('is_override', true)
            ->where('status', 'Closed')
            ->exists();
    }

    /**
     * The state and event count for one date.
     *
     * @return array{state: string, booked: int}
     */
    public function stateFor(string $date): array
    {
        $booked = $this->activeEventCount($date);

        $override = Availability::query()
            ->where('date', '>=', $date)
            ->where('date', '<', Carbon::parse($date)->addDay()->toDateString())
            ->where('is_override', true)
            ->first();

        return [
            'state' => $override ? (string) $override->status : AvailabilityScale::stateFor($booked),
            'booked' => $booked,
        ];
    }

    /**
     * Everything the calendars need in one payload: the slot total, the wording
     * for the cues and accessible labels, and the state of every date that has
     * a booking or an override.
     *
     * @return array<string, mixed>
     */
    public function calendarPayload(): array
    {
        // One query for every active booking, grouped in PHP, so the same rows
        // feed both the per-day state and the per-venue flags that the
        // "Venues on <date>" panel needs. With this payload the panel renders
        // without a second request when the date or period changes.
        $bookingsByDate = Reservation::query()
            ->where('status', 'active')
            ->whereNotNull('date')
            ->get(['reserve_id', 'date', 'venue', 'time', 'form_data'])
            ->groupBy(fn (Reservation $reservation) => Carbon::parse($reservation->date)->format('Y-m-d'));

        $overrides = Availability::query()
            ->where('is_override', true)
            ->whereNotNull('date')
            ->get()
            ->keyBy(fn (Availability $override) => $override->date->format('Y-m-d'));

        $occupancy = app(VenueOccupancyService::class);
        $states = [];

        foreach ($overrides as $date => $override) {
            $reservations = $bookingsByDate->get($date, collect());

            $states[$date] = [
                'state' => (string) $override->status,
                'booked' => $reservations->count(),
                'closed' => (string) $override->status === 'Closed',
                'occupancy' => $occupancy->occupancyFor($reservations),
            ];
        }

        foreach ($bookingsByDate as $date => $reservations) {
            if (isset($states[$date])) {
                continue;
            }

            $states[$date] = [
                'state' => AvailabilityScale::stateFor($reservations->count()),
                'booked' => $reservations->count(),
                'closed' => false,
                'occupancy' => $occupancy->occupancyFor($reservations),
            ];
        }

        ksort($states);

        return [
            'daily_cap' => $this->cap(),
            'labels' => AvailabilityScale::labels(),
            'cue_words' => AvailabilityScale::cueWords(),
            'venues' => VenueCatalog::names(),
            'dates' => $states,
        ];
    }
}
