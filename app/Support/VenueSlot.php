<?php

namespace App\Support;

/**
 * Pure helpers for the venue booking unit: venue + date + period (AM or PM).
 *
 * No database access lives here on purpose, so the conflict rules can be unit
 * tested without booting the app's data layer. Everything is static and does
 * not touch the container.
 */
final class VenueSlot
{
    /** Message shown when a venue was taken between the check and the insert. */
    public const CONFLICT_MESSAGE = 'That venue was just booked for that date. Please choose another.';

    /** Message shown when a conversion has no determinable period. */
    public const PERIOD_REQUIRED_MESSAGE = 'Select a period (AM or PM) before confirming this reservation.';

    /** Message shown when the booking lock could not be acquired. */
    public const TRY_AGAIN_MESSAGE = 'We could not confirm that slot just now. Please try again.';

    public const PERIODS = ['AM', 'PM'];

    /**
     * Canonical form used for every comparison: trimmed, inner whitespace
     * collapsed, lowercased. "Villa I", "villa i" and " Villa  I " all match.
     */
    public static function normalize(?string $venue): string
    {
        $collapsed = preg_replace('/\s+/u', ' ', trim((string) $venue));

        return mb_strtolower($collapsed ?? '', 'UTF-8');
    }

    /**
     * True when the value identifies a real venue. Blank values and the
     * "Others" free-text choice are exempt from conflict checks because they
     * cannot be matched reliably.
     */
    public static function isTrackable(?string $venue): bool
    {
        $normalized = self::normalize($venue);

        return $normalized !== '' && $normalized !== 'others';
    }

    /** Uppercases and validates a period, or returns null when unrecognised. */
    public static function normalizePeriod(?string $period): ?string
    {
        $value = strtoupper(trim((string) $period));

        return in_array($value, self::PERIODS, true) ? $value : null;
    }

    /**
     * Period stored on the dynamic booking form. Accepts the decoded array
     * (the model cast) or a raw JSON string.
     */
    public static function periodFromFormData(mixed $formData): ?string
    {
        if (is_string($formData)) {
            $decoded = json_decode($formData, true);
            $formData = is_array($decoded) ? $decoded : null;
        }

        if (! is_array($formData)) {
            return null;
        }

        $raw = $formData['period'] ?? null;

        return is_string($raw) ? self::normalizePeriod($raw) : null;
    }

    /**
     * Derives the period from the first hour in the slot text.
     *
     * An explicit am/pm marker wins (so "2pm-4pm" is PM even though 2 < 12).
     * Without a marker the hour is read as 24-hour time: before 12 is AM,
     * otherwise PM. Legacy rows such as "21:04:00" resolve this way. Returns
     * null when no leading hour can be found.
     */
    public static function periodFromTimeText(?string $time): ?string
    {
        $text = trim((string) $time);

        if ($text === '') {
            return null;
        }

        if (! preg_match('/(\d{1,2})(?::(\d{2}))?\s*(am|pm|a\.m\.|p\.m\.)?/i', $text, $matches)) {
            return null;
        }

        $hour = (int) $matches[1];

        if ($hour > 23) {
            return null;
        }

        $meridiem = strtolower(str_replace('.', '', $matches[3] ?? ''));

        if ($meridiem === 'am') {
            return 'AM';
        }

        if ($meridiem === 'pm') {
            return 'PM';
        }

        return $hour < 12 ? 'AM' : 'PM';
    }

    /**
     * Periods an EXISTING row occupies. A row whose period cannot be worked out
     * is treated as occupying both periods, so it can never be double booked by
     * accident.
     *
     * @return array<int, string>
     */
    public static function occupiedPeriods(?string $period, ?string $timeText): array
    {
        $resolved = self::normalizePeriod($period) ?? self::periodFromTimeText($timeText);

        return $resolved !== null ? [$resolved] : self::PERIODS;
    }

    /**
     * The conflict predicate: does a row occupying $occupiedPeriods block a
     * booking in $targetPeriod?
     *
     * @param  array<int, string>  $occupiedPeriods
     */
    public static function occupies(array $occupiedPeriods, ?string $targetPeriod): bool
    {
        $target = self::normalizePeriod($targetPeriod);

        if ($target === null) {
            return false;
        }

        foreach ($occupiedPeriods as $period) {
            if (self::normalizePeriod($period) === $target) {
                return true;
            }
        }

        return false;
    }
}
