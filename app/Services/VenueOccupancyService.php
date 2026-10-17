<?php

namespace App\Services;

use App\Models\Inquiry;
use App\Models\Reservation;
use App\Support\VenueCatalog;
use App\Support\VenueSlot;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Venue occupancy: one booking per venue + date + period (AM or PM).
 *
 * An active reservation occupies its slot. An open inquiry (Pending or In
 * Progress) also holds the slot, so a second patron cannot submit the same
 * venue + date + period while the first is still waiting for confirmation.
 * Cancelled frees the slot; Completed inquiries are covered by their
 * reservation row, so they are not counted again.
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

    /** Inquiry statuses that still hold a venue slot. */
    public const OPEN_INQUIRY_STATUSES = ['Pending', 'In Progress'];

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
            ->where('date', '>=', $date)
            ->where('date', '<', Carbon::parse($date)->addDay()->toDateString())
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
            ->where('date', '>=', $date)
            ->where('date', '<', Carbon::parse($date)->addDay()->toDateString())
            ->get(['reserve_id', 'venue', 'time', 'form_data']);
    }

    /**
     * Open inquiries for a date, optionally ignoring one inquiry (the row being
     * converted, which must not block itself).
     *
     * @return Collection<int, Inquiry>
     */
    public function openInquiriesForDate(?string $date, ?int $ignoreInquiryId = null): Collection
    {
        if (blank($date)) {
            return collect();
        }

        return Inquiry::query()
            ->whereIn('status', self::OPEN_INQUIRY_STATUSES)
            ->where('date', '>=', $date)
            ->where('date', '<', Carbon::parse($date)->addDay()->toDateString())
            ->when($ignoreInquiryId !== null, fn ($query) => $query->where('inquiry_id', '!=', $ignoreInquiryId))
            ->get(['inquiry_id', 'venue', 'time', 'form_data', 'date', 'status']);
    }

    /**
     * Occupied periods per normalised venue name for one date.
     * Unknown periods are reported for both AM and PM.
     *
     * @return array<string, array<string, bool>> normalised venue => ['AM' => bool, 'PM' => bool]
     */
    public function occupiedPeriodsByVenue(?string $date): array
    {
        return $this->occupancyFor(
            $this->activeForDate($date)->concat($this->openInquiriesForDate($date))
        );
    }

    /**
     * Occupied periods per normalised venue name for a set of reservations.
     *
     * Takes the rows as an argument so callers that already fetched the day's
     * reservations (the calendar prefetch, the panel endpoint) do not query
     * again.
     *
     * @param  Collection<int, Reservation|Inquiry>  $rows
     * @return array<string, array<string, bool>>
     */
    public function occupancyFor(Collection $rows): array
    {
        $map = [];

        foreach ($rows as $reservation) {
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
     * The complete "Venues on &lt;date&gt;" payload: every bookable venue with its
     * AM/PM occupied flags.
     *
     * @param  Collection<int, Reservation>  $reservations  Active reservations for the date.
     * @return array{closed: bool, count: int, venues: array<int, array{name: string, occupied: array{AM: bool, PM: bool}}>}
     */
    public function panelPayload(bool $closed, Collection $reservations, ?Collection $inquiries = null): array
    {
        $occupancy = $this->occupancyFor($reservations->concat($inquiries ?? collect()));

        $venues = [];

        foreach (VenueCatalog::names() as $name) {
            $key = VenueSlot::normalize($name);

            $venues[] = [
                'name' => $name,
                'occupied' => [
                    'AM' => (bool) ($occupancy[$key]['AM'] ?? false),
                    'PM' => (bool) ($occupancy[$key]['PM'] ?? false),
                ],
            ];
        }

        return [
            'closed' => $closed,
            'count' => $reservations->count(),
            'venues' => $venues,
        ];
    }

    /**
     * Is the slot already taken by an active reservation or an open inquiry?
     */
    public function isOccupied(
        string $venue,
        string $date,
        string $period,
        ?int $ignoreReservationId = null,
        ?int $ignoreInquiryId = null
    ): bool {
        if (! VenueSlot::isTrackable($venue)) {
            return false;
        }

        $target = VenueSlot::normalizePeriod($period);

        if ($target === null) {
            return false;
        }

        $wanted = VenueSlot::normalize($venue);

        $matches = function ($row) use ($wanted, $target): bool {
            if (VenueSlot::normalize($row->venue) !== $wanted) {
                return false;
            }

            $periods = VenueSlot::occupiedPeriods(
                VenueSlot::periodFromFormData($row->form_data),
                $row->time
            );

            return VenueSlot::occupies($periods, $target);
        };

        $reservationTaken = $this->activeForDate($date)->contains(function (Reservation $reservation) use ($matches, $ignoreReservationId) {
            if ($ignoreReservationId !== null && (int) $reservation->reserve_id === $ignoreReservationId) {
                return false;
            }

            return $matches($reservation);
        });

        if ($reservationTaken) {
            return true;
        }

        return $this->openInquiriesForDate($date, $ignoreInquiryId)
            ->contains(fn (Inquiry $inquiry) => $matches($inquiry));
    }

    /**
     * Lock the slot, recheck inside a transaction, then create.
     *
     * Returns one of the RESULT_* constants. The callback runs at most once and
     * only when the slot is still free, so a conflict never inserts a row.
     */
    public function guard(string $venue, string $date, string $period, Closure $create, ?int $ignoreInquiryId = null): string
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
            $result = DB::transaction(function () use ($venue, $date, $period, $create, $ignoreInquiryId) {
                if ($this->isOccupied($venue, $date, $period, null, $ignoreInquiryId)) {
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
    public function guardBooking(?string $venue, ?string $date, ?string $period, int $dailyCap, Closure $create, ?int $ignoreInquiryId = null): string
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
            $result = DB::transaction(function () use ($venue, $dateKey, $period, $cap, $create, $ignoreInquiryId) {
                if ($this->activeCountForDate($dateKey) >= $cap) {
                    return self::RESULT_CAPACITY;
                }

                $targetPeriod = VenueSlot::normalizePeriod($period);

                if (VenueSlot::isTrackable($venue) && $targetPeriod !== null
                    && $this->isOccupied((string) $venue, $dateKey, $targetPeriod, null, $ignoreInquiryId)) {
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
