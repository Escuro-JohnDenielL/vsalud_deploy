<?php

namespace App\Services;

use App\Models\Reservation;
use App\Support\VenueSlot;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Venue occupancy: one booking per venue + date + period (AM or PM).
 *
 * Only reservations with status "active" occupy a slot. Cancelled frees the
 * slot, completed is a past event, and inquiries never occupy because they are
 * only a request at that point.
 *
 * Every write path funnels through guard(), which takes a Cache::lock on the
 * slot BEFORE opening the transaction, rechecks inside it, then releases the
 * lock after the commit. The lock makes two simultaneous requests serialise,
 * and the recheck makes the loser of that race fail closed instead of creating
 * a duplicate.
 */
class VenueOccupancyService
{
    /** Seconds the lock is held at most before it expires on its own. */
    public const LOCK_SECONDS = 5;

    /** Seconds to wait for a competing request before giving up. */
    public const LOCK_WAIT_SECONDS = 2;

    public const RESULT_CREATED = 'created';
    public const RESULT_CONFLICT = 'conflict';
    public const RESULT_BUSY = 'busy';
    public const RESULT_CAPACITY = 'capacity';

    /**
     * Cache key for one booking day. Conversions take this before their
     * transaction so the day's event count cannot move underneath them.
     */
    public function dayLockKey(string $date): string
    {
        return 'day:' . $date;
    }

    /**
     * Active reservations on a date, one per event (period irrelevant).
     */
    public function activeCountForDate(?string $date): int
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
     * Cache key for one slot. The venue is normalised so "Villa I", "villa i"
     * and " Villa  I " all lock the same slot.
     */
    public function lockKey(string $venue, string $date, string $period): string
    {
        return 'venue:' . VenueSlot::normalize($venue) . ':' . $date . ':' . strtoupper(trim($period));
    }

    /**
     * Active reservations for a date. Kept small on purpose: the period is
     * resolved in PHP so the comparison matches the unit tested rules exactly
     * (SQL cannot normalise whitespace the same way).
     *
     * @return Collection<int, Reservation>
     */
    public function activeForDate(?string $date): Collection
    {
        if (blank($date)) {
            return collect();
        }

        return Reservation::query()
            ->where('status', 'active')
            ->whereDate('date', $date)
            ->get(['reserve_id', 'venue', 'time', 'form_data']);
    }

    /**
     * Occupied periods per normalised venue name for one date.
     * Unknown periods are reported for both AM and PM.
     *
     * @return array<string, array<string, bool>> normalised venue => ['AM' => bool, 'PM' => bool]
     */
    public function occupiedPeriodsByVenue(?string $date): array
    {
        $map = [];

        foreach ($this->activeForDate($date) as $reservation) {
            $key = VenueSlot::normalize($reservation->venue);

            if ($key === '') {
                continue;
            }

            $periods = VenueSlot::occupiedPeriods(
                VenueSlot::periodFromFormData($reservation->form_data),
                $reservation->time
            );

            foreach ($periods as $period) {
                $map[$key][$period] = true;
            }
        }

        return $map;
    }

    /**
     * Is the slot already taken by an active reservation?
     */
    public function isOccupied(string $venue, string $date, string $period, ?int $ignoreReservationId = null): bool
    {
        if (! VenueSlot::isTrackable($venue)) {
            return false;
        }

        $target = VenueSlot::normalizePeriod($period);

        if ($target === null) {
            return false;
        }

        $wanted = VenueSlot::normalize($venue);

        return $this->activeForDate($date)->contains(function (Reservation $reservation) use ($wanted, $target, $ignoreReservationId) {
            if ($ignoreReservationId !== null && (int) $reservation->reserve_id === $ignoreReservationId) {
                return false;
            }

            if (VenueSlot::normalize($reservation->venue) !== $wanted) {
                return false;
            }

            $periods = VenueSlot::occupiedPeriods(
                VenueSlot::periodFromFormData($reservation->form_data),
                $reservation->time
            );

            return VenueSlot::occupies($periods, $target);
        });
    }

    /**
     * Lock the slot, recheck inside a transaction, then create.
     *
     * Returns one of the RESULT_* constants. The callback runs at most once and
     * only when the slot is still free, so a conflict never inserts a row.
     */
    public function guard(string $venue, string $date, string $period, Closure $create): string
    {
        if (! VenueSlot::isTrackable($venue)) {
            $create();

            return self::RESULT_CREATED;
        }

        $lock = Cache::lock($this->lockKey($venue, $date, $period), self::LOCK_SECONDS);
        $acquired = false;

        try {
            $acquired = $lock->block(self::LOCK_WAIT_SECONDS);
        } catch (LockTimeoutException) {
            $acquired = false;
        }

        if (! $acquired) {
            // Fail closed: the slot may or may not be free, so nothing is written.
            return self::RESULT_BUSY;
        }

        try {
            $result = DB::transaction(function () use ($venue, $date, $period, $create) {
                if ($this->isOccupied($venue, $date, $period)) {
                    return self::RESULT_CONFLICT;
                }

                $create();

                return self::RESULT_CREATED;
            });
        } finally {
            // Released after the transaction has committed (or rolled back).
            $lock->release();
        }

        return $result;
    }

    /**
     * Lock the day, then recheck the daily cap and the venue slot inside the
     * transaction before creating the reservation.
     *
     * The day lock is taken BEFORE the transaction and released after the
     * commit (or rollback). If the lock cannot be acquired the result is
     * RESULT_BUSY and nothing is written, so the caller fails closed.
     *
     * @param  int  $dailyCap  maximum active events for the date
     */
    public function guardBooking(?string $venue, ?string $date, ?string $period, int $dailyCap, Closure $create): string
    {
        $cap = max(1, $dailyCap);
        $dateKey = trim((string) $date);

        if ($dateKey === '') {
            // No date: there is no day to lock or count. Legacy rows only.
            $create();

            return self::RESULT_CREATED;
        }

        $lock = Cache::lock($this->dayLockKey($dateKey), self::LOCK_SECONDS);
        $acquired = false;

        try {
            $acquired = $lock->block(self::LOCK_WAIT_SECONDS);
        } catch (LockTimeoutException) {
            $acquired = false;
        }

        if (! $acquired) {
            // Fail closed: the day may or may not have room.
            return self::RESULT_BUSY;
        }

        try {
            $result = DB::transaction(function () use ($venue, $dateKey, $period, $cap, $create) {
                if ($this->activeCountForDate($dateKey) >= $cap) {
                    return self::RESULT_CAPACITY;
                }

                $targetPeriod = VenueSlot::normalizePeriod($period);

                if (VenueSlot::isTrackable($venue) && $targetPeriod !== null
                    && $this->isOccupied((string) $venue, $dateKey, $targetPeriod)) {
                    return self::RESULT_CONFLICT;
                }

                $create();

                return self::RESULT_CREATED;
            });
        } finally {
            // Released after the transaction has committed (or rolled back).
            $lock->release();
        }

        return $result;
    }
}
