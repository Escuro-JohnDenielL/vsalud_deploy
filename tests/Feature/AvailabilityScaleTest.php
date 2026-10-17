<?php

use App\Support\AvailabilityScale;

/*
| Calendar event scale: active reservations per day and the five states.
| No database, but the values come from config/availability.php.
*/

it('maps event counts onto the five states at the default cap', function () {
    config(['availability.max_events_per_day' => 4]);

    expect(AvailabilityScale::stateFor(0))->toBe('Available')
        ->and(AvailabilityScale::stateFor(1))->toBe('Available')
        ->and(AvailabilityScale::stateFor(2))->toBe('Half')
        ->and(AvailabilityScale::stateFor(3))->toBe('Nearly')
        ->and(AvailabilityScale::stateFor(4))->toBe('Full')
        ->and(AvailabilityScale::stateFor(5))->toBe('Full');
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

it('reads the label wording from config', function () {
    $labels = AvailabilityScale::labels();

    expect($labels)->toHaveKeys(['Available', 'Half', 'Nearly', 'Full', 'Closed'])
        ->and($labels['Half'])->toBe('limited')
        ->and(AvailabilityScale::cueWords()['Full'])->toBe('Full')
        ->and(AvailabilityScale::cueWords()['Closed'])->toBe('Closed');
});

