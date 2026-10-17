<?php

use App\Support\AvailabilityScale;

/*
| Calendar event scale: active reservations per day and the five states.
| No database, but the values come from config/availability.php.
*/

it('maps 0 to 5 events onto the five states at the default cap', function () {
    config(['availability.max_events_per_day' => 4]);

    expect(AvailabilityScale::stateFor(0))->toBe('Available')
        ->and(AvailabilityScale::stateFor(1))->toBe('Available')
        ->and(AvailabilityScale::stateFor(2))->toBe('Half')
        ->and(AvailabilityScale::stateFor(3))->toBe('Nearly')
        ->and(AvailabilityScale::stateFor(4))->toBe('Full')
        ->and(AvailabilityScale::stateFor(5))->toBe('Full');
});

it('reads the daily cap from config and rescales the states', function () {
    config(['availability.max_events_per_day' => 5]);

    expect(AvailabilityScale::cap())->toBe(5)
        ->and(AvailabilityScale::stateFor(0))->toBe('Available')
        ->and(AvailabilityScale::stateFor(2))->toBe('Available')
        ->and(AvailabilityScale::stateFor(3))->toBe('Half')
        ->and(AvailabilityScale::stateFor(4))->toBe('Nearly')
        ->and(AvailabilityScale::stateFor(5))->toBe('Full');

    config(['availability.max_events_per_day' => 3]);

    expect(AvailabilityScale::cap())->toBe(3)
        ->and(AvailabilityScale::stateFor(0))->toBe('Available')
        ->and(AvailabilityScale::stateFor(1))->toBe('Half')
        ->and(AvailabilityScale::stateFor(2))->toBe('Nearly')
        ->and(AvailabilityScale::stateFor(3))->toBe('Full');
});

it('builds the exact legend from the configured cap', function () {
    config(['availability.max_events_per_day' => 4]);

    $rows = collect(AvailabilityScale::legendRows());

    expect($rows->pluck('text')->all())->toBe([
        '0/4 to 1/4 Available',
        '2/4 Limited',
        '3/4 Nearly Full',
        '4/4 Full',
        'Closed',
    ])->and($rows->pluck('state')->all())->toBe([
        'Available',
        'Half',
        'Nearly',
        'Full',
        'Closed',
    ])->and($rows->pluck('className')->first())->toBe('vs-calendar-day--success');
});

it('builds the legend from a non-default cap', function () {
    config(['availability.max_events_per_day' => 5]);

    expect(collect(AvailabilityScale::legendRows())->pluck('text')->all())->toBe([
        '0/5 to 2/5 Available',
        '3/5 Limited',
        '4/5 Nearly Full',
        '5/5 Full',
        'Closed',
    ]);
});

it('reads the label wording from config', function () {
    $labels = AvailabilityScale::labels();

    expect($labels)->toHaveKeys(['Available', 'Half', 'Nearly', 'Full', 'Closed'])
        ->and($labels['Half'])->toBe('limited')
        ->and(AvailabilityScale::cueWords()['Full'])->toBe('Full')
        ->and(AvailabilityScale::cueWords()['Closed'])->toBe('Closed');
});

