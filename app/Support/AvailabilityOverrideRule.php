<?php

namespace App\Support;

/**
 * Rules for an admin override on a single calendar date.
 *
 * An override always wins over the computed slot count, which is what makes the
 * two directions dangerous: an override cannot be allowed to advertise a date as
 * Available when it is already fully committed, and closing a date that still
 * holds bookings has to be a deliberate, explained decision.
 *
 * Pure and static on purpose: no database, no request. The counts are passed in,
 * so the decision table can be unit tested directly.
 */
final class AvailabilityOverrideRule
{
    /** The override may be saved. */
    public const OK = 'ok';

    /** "Available" refused because the date is already at or above the cap. */
    public const AVAILABLE_AT_CAP = 'available_at_cap';

    /** Closing a date that still has reservations needs a written reason. */
    public const CLOSED_NEEDS_REASON = 'closed_needs_reason';

    /** Closing a date that still has reservations needs the count confirmed. */
    public const CLOSED_NEEDS_CONFIRMATION = 'closed_needs_confirmation';

    /** Shortest reason accepted when a closed date still has reservations. */
    public const MIN_REASON_LENGTH = 5;

    /**
     * Decide whether an override may be saved.
     *
     * @param  string  $status             one of AvailabilityScale::STATES
     * @param  int     $activeReservations active (non-cancelled) bookings on the date
     * @param  int     $dailyCap           the configured "still Available" ceiling
     * @param  string|null  $reason        text the admin typed for the audit log
     * @param  int|null $confirmedCount    the count the admin confirmed on screen
     * @return string one of the constants above
     */
    public static function evaluate(
        string $status,
        int $activeReservations,
        int $dailyCap,
        ?string $reason = null,
        ?int $confirmedCount = null
    ): string {
        $activeReservations = max(0, $activeReservations);
        $cap = max(1, $dailyCap);

        // An override is not a way to un-book a full date. Closing always wins,
        // but "Available" has to stay honest about the bookings already held.
        if ($status === 'Available' && $activeReservations >= $cap) {
            return self::AVAILABLE_AT_CAP;
        }

        if ($status === 'Closed' && $activeReservations > 0) {
            if (mb_strlen(trim((string) $reason)) < self::MIN_REASON_LENGTH) {
                return self::CLOSED_NEEDS_REASON;
            }

            if ($confirmedCount !== $activeReservations) {
                return self::CLOSED_NEEDS_CONFIRMATION;
            }
        }

        return self::OK;
    }

    /**
     * The sentence shown to the admin for a refused override.
     */
    public static function message(string $code, int $activeReservations, int $dailyCap): string
    {
        $count = max(0, $activeReservations);
        $cap = max(1, $dailyCap);

        return match ($code) {
            self::AVAILABLE_AT_CAP => "This date already holds {$count} active booking(s), at or above the daily limit of {$cap}, so it cannot be overridden to Available.",
            self::CLOSED_NEEDS_REASON => 'A reason of at least ' . self::MIN_REASON_LENGTH . ' characters is required to close a date that still has reservations.',
            self::CLOSED_NEEDS_CONFIRMATION => "This date holds {$count} active reservation(s). Confirm the count to close it. Closing does not cancel them.",
            default => 'The override could not be saved.',
        };
    }
}
