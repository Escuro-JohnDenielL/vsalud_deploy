<?php

namespace App\Support;

/**
 * The per-day event scale: how many confirmed reservations a date holds, and
 * which of the five calendar states that count falls into.
 *
 * The count is one event per ACTIVE reservation on the date, across every
 * venue and period. Pending inquiries never count. Every number and every word
 * here comes from config/availability.php, so the legend, the day cue and the
 * accessible labels cannot drift apart.
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

    /**
     * The configured daily cap (max_events_per_day).
     */
    public static function cap(): int
    {
        return max(1, (int) config('availability.max_events_per_day', 4));
    }

    /**
     * Highest count still shown as Available (0 for a cap of 4).
     */
    public static function availableMax(?int $cap = null): int
    {
        $cap = max(1, $cap ?? self::cap());

        return max(0, $cap - 3);
    }

    /**
     * First count shown as Limited (the internal state is still "Half").
     */
    public static function limitedAt(?int $cap = null): int
    {
        $cap = max(1, $cap ?? self::cap());

        return max(self::availableMax($cap) + 1, $cap - 2);
    }

    /**
     * First count shown as Nearly Full.
     */
    public static function nearlyAt(?int $cap = null): int
    {
        $cap = max(1, $cap ?? self::cap());

        return max(self::limitedAt($cap) + 1, $cap - 1);
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
     * The state an event count maps to, from the configured daily cap.
     *
     * Available 0 to cap - 3
     * Half      cap - 2          (displayed as "Limited")
     * Nearly    cap - 1          (displayed as "Nearly Full")
     * Full      cap and above
     */
    public static function stateFor(int $events, ?int $cap = null): string
    {
        $events = max(0, $events);
        $cap = max(1, $cap ?? self::cap());

        if ($events >= $cap) {
            return 'Full';
        }

        if ($events >= self::nearlyAt($cap)) {
            return 'Nearly';
        }

        if ($events >= self::limitedAt($cap)) {
            return 'Half';
        }

        return 'Available';
    }

    /**
     * Legend rows, in display order, derived from the same config.
     *
     * @return array<int, array{state: string, className: string, text: string}>
     */
    public static function legendRows(?int $cap = null): array
    {
        $cap = max(1, $cap ?? self::cap());
        $labels = self::labels();
        $available = self::availableMax($cap);
        $limited = self::limitedAt($cap);
        $nearly = self::nearlyAt($cap);

        return [
            [
                'state' => 'Available',
                'className' => self::STATE_CLASSES['Available'],
                'text' => "0/{$cap} to {$available}/{$cap} " . self::word($labels, 'Available'),
            ],
            [
                'state' => 'Half',
                'className' => self::STATE_CLASSES['Half'],
                'text' => "{$limited}/{$cap} " . self::word($labels, 'Half'),
            ],
            [
                'state' => 'Nearly',
                'className' => self::STATE_CLASSES['Nearly'],
                'text' => "{$nearly}/{$cap} " . self::word($labels, 'Nearly'),
            ],
            [
                'state' => 'Full',
                'className' => self::STATE_CLASSES['Full'],
                'text' => "{$cap}/{$cap} " . self::word($labels, 'Full'),
            ],
            [
                'state' => 'Closed',
                'className' => self::STATE_CLASSES['Closed'],
                'text' => self::word($labels, 'Closed'),
            ],
        ];
    }

    /**
     * Capitalised display word for one state ("nearly full" => "Nearly Full").
     */
    private static function word(array $labels, string $state): string
    {
        return ucwords(strtolower((string) ($labels[$state] ?? $state)));
    }
}
