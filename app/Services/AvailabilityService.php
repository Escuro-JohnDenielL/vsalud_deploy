<?php

namespace App\Services;

use App\Models\Availability;
use App\Models\Inquiry;
use App\Models\Reservation;
use App\Support\AvailabilityScale;
use App\Support\VenueCatalog;
use Illuminate\Support\Carbon;

/**
 * Calendar availability for a date, counted in events.
 *
 * A day's count is the number of BOOKINGS on that date, across all venues and
 * periods. A booking is either a confirmed (active) reservation or an open
 * inquiry that is still waiting for confirmation, so a reservation submitted
 * from the booking form is visible on the calendar immediately. A cancelled
 * booking frees its place. A booking that was confirmed appears in both tables
 * and is counted once.
 *
 * Venue occupancy (AM/PM flags) and the daily cap are still derived from
 * ACTIVE reservations only: a pending inquiry never blocks a venue slot.
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
        // One query for every active reservation, grouped in PHP, so the same
        // rows feed both the per-venue flags that the "Venues on <date>" panel
        // needs and the confirmed-booking count. With this payload the panel
        // renders without a second request when the date or period changes.
        $bookingsByDate = Reservation::query()
            ->where('status', 'active')
            ->whereNotNull('date')
            ->get(['reserve_id', 'date', 'venue', 'time', 'form_data'])
            ->groupBy(fn (Reservation $reservation) => Carbon::parse($reservation->date)->format('Y-m-d'));

        // Every booking starts life as an inquiry. Pending and in-progress
        // inquiries have no reservation row yet, so they are counted from the
        // inquiry table; a confirmed booking has both rows and must not count
        // twice (the two counts are max()ed per date, never added). A cancelled
        // inquiry only counts if it still has an active reservation.
        $bookingCounts = Inquiry::query()
            ->whereNotNull('date')
            ->where(function ($query) {
                $query->where('status', '!=', 'Cancelled')
                    ->orWhereHas('reservation', fn ($reservation) => $reservation->where('status', 'active'));
            })
            ->get(['inquiry_id', 'date'])
            ->groupBy(fn (Inquiry $inquiry) => $inquiry->date->format('Y-m-d'))
            ->map->count();

        $overrides = Availability::query()
            ->where('is_override', true)
            ->whereNotNull('date')
            ->get()
            ->keyBy(fn (Availability $override) => $override->date->format('Y-m-d'));

        $occupancy = app(VenueOccupancyService::class);
        $states = [];

        $dates = $overrides->keys()
            ->merge($bookingCounts->keys())
            ->merge($bookingsByDate->keys())
            ->unique();

        foreach ($dates as $date) {
            $date = (string) $date;
            $override = $overrides->get($date);
            $reservations = $bookingsByDate->get($date, collect());

            // A booking has one inquiry. A confirmed booking also has a
            // reservation, so the larger of the two counts is the real number.
            $booked = max($reservations->count(), (int) ($bookingCounts[$date] ?? 0));

            $states[$date] = [
                'state' => $override ? (string) $override->status : AvailabilityScale::stateFor($booked),
                'booked' => $booked,
                'closed' => $override ? (string) $override->status === 'Closed' : false,
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
