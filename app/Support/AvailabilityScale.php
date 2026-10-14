<?php

namespace App\Support;

/**
 * The calendar slot scale: how many venue-period slots a date has, and which
 * of the five states a booked count falls into.
 *
 * One slot is one venue for one period (AM or PM), so a date has
 * (venues x periods_per_venue) slots. The venue list is read at runtime, never
 * hardcoded. Every number and every word here comes from
 * config/availability.php so the day cue, the legend and the accessible labels
 * cannot drift apart.
 *
 * Pure and static on purpose: no database access, so the cutoffs can be unit
 * tested directly.
 */
final class AvailabilityScale
{
    public const STATES = ['Available', 'Half', 'Nearly', 'Full', 'Closed'];

    /** CSS class used by the calendar day cell and the legend swatch. */
    public const STATE_CLASSES = [
        'Available' => 'vs-calendar-day--success',
        'Half' => 'vs-calendar-day--half',
        'Nearly' => 'vs-calendar-day--nearly',
        'Full' => 'vs-calendar-day--danger',
        'Closed' => 'vs-calendar-day--neutral',
    ];

    /** Periods a single venue can be booked for. */
    public static function periods(): array
    {
        return VenueSlot::PERIODS;
    }

    /**
     * Slots for one date, from the number of bookable venues.
     */
    public static function slotCount(int $venueCount): int
    {
        return max(0, $venueCount) * self::periodsPerVenue();
    }

    /**
     * Slots for one date using the venue catalog currently in the form field.
     */
    public static function totalSlots(): int
    {
        return self::slotCount(count(VenueCatalog::names()));
    }

    public static function periodsPerVenue(): int
    {
        return max(1, (int) config('availability.periods_per_venue', 2));
    }

    public static function availableMax(): int
    {
        return max(0, (int) config('availability.available_max', 2));
    }

    public static function halfMax(): int
    {
        return max(self::availableMax(), (int) config('availability.half_max', 4));
    }

    /**
     * Words used in the accessible day labels, keyed by state.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        $labels = (array) config('availability.labels', []);

        $resolved = [];
        foreach (self::STATES as $state) {
            $resolved[$state] = (string) ($labels[$state] ?? strtolower($state));
        }

        return $resolved;
    }

    /**
     * Words used instead of a booked count for Full and Closed.
     *
     * @return array<string, string>
     */
    public static function cueWords(): array
    {
        $words = (array) config('availability.cue_words', []);

        return [
            'Full' => (string) ($words['Full'] ?? 'Full'),
            'Closed' => (string) ($words['Closed'] ?? 'Closed'),
        ];
    }

    /**
     * The state a booked count maps to.
     *
     * Available 0 to available_max
     * Half      available_max + 1 to half_max
     * Nearly    half_max + 1 up to (total - 1)
     * Full      every slot occupied
     */
    public static function stateFor(int $booked, int $totalSlots): string
    {
        $booked = max(0, $booked);
        $total = max(0, $totalSlots);

        if ($total > 0 && $booked >= $total) {
            return 'Full';
        }

        if ($booked > self::halfMax()) {
            return 'Nearly';
        }

        if ($booked > self::availableMax()) {
            return 'Half';
        }

        return 'Available';
    }

    /**
     * Legend rows, in display order, derived from the same config.
     *
     * @return array<int, array{state: string, className: string, text: string}>
     */
    public static function legendRows(int $totalSlots): array
    {
        $total = max(1, $totalSlots);
        $available = min(self::availableMax(), $total);
        $half = max($available, min(self::halfMax(), $total));

        $rows = [
            [
                'state' => 'Available',
                'className' => self::STATE_CLASSES['Available'],
                'text' => "0/{$total} to {$available}/{$total} Available",
            ],
        ];

        if ($half > $available) {
            $rows[] = [
                'state' => 'Half',
                'className' => self::STATE_CLASSES['Half'],
                'text' => ($available + 1) . "/{$total} to {$half}/{$total} Limited",
            ];
        }

        if ($total - 1 > $half) {
            $rows[] = [
                'state' => 'Nearly',
                'className' => self::STATE_CLASSES['Nearly'],
                'text' => ($half + 1) . "/{$total} to " . ($total - 1) . "/{$total} Nearly Full",
            ];
        }

        $rows[] = [
            'state' => 'Full',
            'className' => self::STATE_CLASSES['Full'],
            'text' => "{$total}/{$total} Full",
        ];

        $rows[] = [
            'state' => 'Closed',
            'className' => self::STATE_CLASSES['Closed'],
            'text' => 'Closed',
        ];

        return $rows;
    }
}
