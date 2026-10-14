<?php

namespace App\Services;

use App\Models\Availability;
use App\Models\Reservation;
use App\Support\AvailabilityScale;
use App\Support\VenueCatalog;
use App\Support\VenueSlot;

/**
 * Calendar availability for a date, counted in venue-period slots.
 *
 * A date has (venues x AM/PM) slots. A slot is occupied when an active
 * reservation holds that venue for that period. Only reservations with status
 * "active" count: cancelled frees the slot, completed is a past event, and
 * inquiries never occupy because they are still just a request.
 *
 * An admin override on the date always wins over the computed count.
 */
class AvailabilityService
{
    public function __construct(private VenueOccupancyService $occupancy)
    {
    }

    /**
     * Slots available on a date: bookable venues x periods per venue.
     */
    public function totalSlots(): int
    {
        return AvailabilityScale::slotCount(count(VenueCatalog::names()));
    }

    /**
     * Occupied slots on a date, counted over the bookable venues only so the
     * count and the venue panel can never disagree.
     */
    public function bookedSlots(?string $date): int
    {
        if (blank($date)) {
            return 0;
        }

        $occupancy = $this->occupancy->occupiedPeriodsByVenue((string) $date);
        $booked = 0;

        foreach (VenueCatalog::names() as $name) {
            $periods = $occupancy[VenueSlot::normalize($name)] ?? [];

            foreach (AvailabilityScale::periods() as $period) {
                if (! empty($periods[$period])) {
                    $booked++;
                }
            }
        }

        return $booked;
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
     * The state and booked count for one date.
     *
     * @return array{state: string, booked: int}
     */
    public function stateFor(string $date): array
    {
        $booked = $this->bookedSlots($date);

        $override = Availability::query()
            ->whereDate('date', $date)
            ->where('is_override', true)
            ->first();

        return [
            'state' => $override ? (string) $override->status : AvailabilityScale::stateFor($booked, $this->totalSlots()),
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
            'slots_total' => $this->totalSlots(),
            'labels' => AvailabilityScale::labels(),
            'cue_words' => AvailabilityScale::cueWords(),
            'dates' => $states,
        ];
    }
}
