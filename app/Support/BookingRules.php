<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Date rules shared by every path that stores a booking request.
 *
 * Pure and static so the rules can be tested without a database. The caller
 * supplies "today" so the comparison never depends on the server default
 * timezone; the app keeps UTC and business dates are Asia/Manila.
 */
final class BookingRules
{
    /** A Closed date is refused for everyone, including admins. */
    public const CLOSED_MESSAGE = 'This date is closed. Please choose another available date.';

    /** The day already holds the configured number of active events. */
    public const FULL_MESSAGE = 'That date is fully booked. Please choose another.';

    /** Past dates are refused for patron submissions only. */
    public const PAST_DATE_MESSAGE = 'Please select a date from today onwards.';

    /** The business timezone used for "today". */
    public const TIMEZONE = 'Asia/Manila';

    /** Today's date in the business timezone, as Y-m-d. */
    public static function today(): string
    {
        return Carbon::now(self::TIMEZONE)->toDateString();
    }

    /**
     * True when the value is a real date that falls before $today.
     *
     * Unparseable values return false: the form validation already rejects
     * those, and this rule must not turn a bad input into a silent rejection.
     */
    public static function isPastDate(?string $date, ?string $today = null): bool
    {
        $parsed = self::parse($date);

        if ($parsed === null) {
            return false;
        }

        return $parsed < ($today ?? self::today());
    }

    /**
     * Normalises a date value to Y-m-d, or null when it cannot be read.
     */
    public static function parse(?string $date): ?string
    {
        if ($date === null || trim($date) === '') {
            return null;
        }

        try {
            return Carbon::parse($date)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
