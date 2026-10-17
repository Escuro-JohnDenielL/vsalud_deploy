<?php

namespace App\Services;

use App\Models\Availability;
use App\Models\Reservation;
use App\Support\AvailabilityScale;
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
        // Two queries in total. The previous version ran stateFor() for every
        // date in the calendar, i.e. two extra queries per booked date, on an
        // endpoint that is fetched on every Make Reservation page load.
        $bookedByDate = Reservation::query()
            ->where('status', 'active')
            ->whereNotNull('date')
            ->select('date')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $overrides = Availability::query()
            ->where('is_override', true)
            ->whereNotNull('date')
            ->get()
            ->keyBy(fn (Availability $override) => $override->date->format('Y-m-d'));

        $states = [];

        foreach ($overrides as $date => $override) {
            $states[$date] = [
                'state' => (string) $override->status,
                'booked' => (int) ($bookedByDate[$date] ?? 0),
            ];
        }

        foreach ($bookedByDate as $date => $booked) {
            if (isset($states[$date])) {
                continue;
            }

            $states[$date] = [
                'state' => AvailabilityScale::stateFor((int) $booked),
                'booked' => (int) $booked,
            ];
        }

        ksort($states);

        return [
            'daily_cap' => $this->cap(),
            'labels' => AvailabilityScale::labels(),
            'cue_words' => AvailabilityScale::cueWords(),
            'dates' => $states,
        ];
    }
}
