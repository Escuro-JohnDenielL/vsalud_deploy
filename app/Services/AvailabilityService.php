<?php

namespace App\Services;

use App\Models\Availability;
use App\Models\Reservation;
use App\Support\AvailabilityScale;

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
            ->whereDate('date', $date)
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
            ->whereDate('date', $date)
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
            ->whereDate('date', $date)
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
        $dates = [];

        foreach (Availability::query()->where('is_override', true)->get() as $override) {
            if ($override->date !== null) {
                $dates[$override->date->format('Y-m-d')] = null;
            }
        }

        $reservationDates = Reservation::query()
            ->where('status', 'active')
            ->whereNotNull('date')
            ->distinct()
            ->pluck('date');

        foreach ($reservationDates as $date) {
            $key = $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : (string) $date;

            if ($key !== '') {
                $dates[$key] = null;
            }
        }

        ksort($dates);

        $states = [];

        foreach (array_keys($dates) as $date) {
            $states[$date] = $this->stateFor($date);
        }

        return [
            'daily_cap' => $this->cap(),
            'labels' => AvailabilityScale::labels(),
            'cue_words' => AvailabilityScale::cueWords(),
            'dates' => $states,
        ];
    }
}
