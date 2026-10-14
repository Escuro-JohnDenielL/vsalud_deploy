<?php

use App\Support\BookingRules;

/*
| Date gates for booking requests.
| Pure rules only: no database, and "today" is always passed in so the result
| never depends on the machine's clock or timezone.
*/

it('treats a date before today as past', function () {
    $today = '2026-10-15';

    expect(BookingRules::isPastDate('2026-10-14', $today))->toBeTrue()
        ->and(BookingRules::isPastDate('2026-01-01', $today))->toBeTrue()
        ->and(BookingRules::isPastDate('2025-12-31', $today))->toBeTrue();
});

it('allows today and any later date', function () {
    $today = '2026-10-15';

    expect(BookingRules::isPastDate('2026-10-15', $today))->toBeFalse()
        ->and(BookingRules::isPastDate('2026-10-16', $today))->toBeFalse()
        ->and(BookingRules::isPastDate('2027-03-01', $today))->toBeFalse();
});

it('reads a datetime value by its date', function () {
    $today = '2026-10-15';

    expect(BookingRules::isPastDate('2026-10-15 23:30:00', $today))->toBeFalse()
        ->and(BookingRules::isPastDate('2026-10-14 23:30:00', $today))->toBeTrue();
});

it('does not treat a missing or unreadable date as past', function () {
    expect(BookingRules::isPastDate(null, '2026-10-15'))->toBeFalse()
        ->and(BookingRules::isPastDate('', '2026-10-15'))->toBeFalse()
        ->and(BookingRules::isPastDate('   ', '2026-10-15'))->toBeFalse()
        ->and(BookingRules::isPastDate('not a date', '2026-10-15'))->toBeFalse();
});

it('normalises a date to Y-m-d', function () {
    expect(BookingRules::parse('2026-10-15'))->toBe('2026-10-15')
        ->and(BookingRules::parse('2026-10-15 08:00:00'))->toBe('2026-10-15')
        ->and(BookingRules::parse(''))->toBeNull()
        ->and(BookingRules::parse(null))->toBeNull()
        ->and(BookingRules::parse('nonsense'))->toBeNull();
});

it('uses Asia/Manila for today', function () {
    expect(BookingRules::today())->toMatch('/^\d{4}-\d{2}-\d{2}$/')
        ->and(BookingRules::TIMEZONE)->toBe('Asia/Manila');
});
