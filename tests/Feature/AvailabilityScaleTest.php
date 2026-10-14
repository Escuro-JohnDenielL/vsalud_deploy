<?php

use App\Support\AvailabilityScale;

/*
| Calendar slot scale: venue-period slots and the five states.
| No database, but the values come from config/availability.php.
*/

it('maps booked slots onto the five states with the default cutoffs', function () {
    config([
        'availability.periods_per_venue' => 2,
        'availability.available_max' => 2,
        'availability.half_max' => 4,
    ]);

    expect(AvailabilityScale::stateFor(0, 8))->toBe('Available')
        ->and(AvailabilityScale::stateFor(1, 8))->toBe('Available')
        ->and(AvailabilityScale::stateFor(2, 8))->toBe('Available')
        ->and(AvailabilityScale::stateFor(3, 8))->toBe('Half')
        ->and(AvailabilityScale::stateFor(4, 8))->toBe('Half')
        ->and(AvailabilityScale::stateFor(5, 8))->toBe('Nearly')
        ->and(AvailabilityScale::stateFor(7, 8))->toBe('Nearly')
        ->and(AvailabilityScale::stateFor(8, 8))->toBe('Full');
});

it('takes the cutoffs from config', function () {
    config([
        'availability.available_max' => 0,
        'availability.half_max' => 1,
    ]);

    expect(AvailabilityScale::stateFor(0, 6))->toBe('Available')
        ->and(AvailabilityScale::stateFor(1, 6))->toBe('Half')
        ->and(AvailabilityScale::stateFor(2, 6))->toBe('Nearly')
        ->and(AvailabilityScale::stateFor(5, 6))->toBe('Nearly')
        ->and(AvailabilityScale::stateFor(6, 6))->toBe('Full');
});

it('calculates the slot total from the venue count and periods per venue', function () {
    config(['availability.periods_per_venue' => 2]);

    expect(AvailabilityScale::slotCount(4))->toBe(8)
        ->and(AvailabilityScale::slotCount(1))->toBe(2)
        ->and(AvailabilityScale::slotCount(0))->toBe(0)
        ->and(AvailabilityScale::slotCount(-3))->toBe(0);

    config(['availability.periods_per_venue' => 3]);

    expect(AvailabilityScale::slotCount(4))->toBe(12);
});

it('builds the legend rows from the same config', function () {
    config([
        'availability.available_max' => 2,
        'availability.half_max' => 4,
    ]);

    $rows = collect(AvailabilityScale::legendRows(8));

    expect($rows->pluck('text')->all())->toBe([
        '0/8 to 2/8 Available',
        '3/8 to 4/8 Limited',
        '5/8 to 7/8 Nearly Full',
        '8/8 Full',
        'Closed',
    ])->and($rows->pluck('state')->all())->toBe([
        'Available',
        'Half',
        'Nearly',
        'Full',
        'Closed',
    ])->and($rows->pluck('className')->first())->toBe('vs-calendar-day--success');
});

it('reads the label wording from config', function () {
    $labels = AvailabilityScale::labels();

    expect($labels)->toHaveKeys(['Available', 'Half', 'Nearly', 'Full', 'Closed'])
        ->and($labels['Half'])->toBe('limited')
        ->and(AvailabilityScale::cueWords()['Full'])->toBe('Full')
        ->and(AvailabilityScale::cueWords()['Closed'])->toBe('Closed');
});
