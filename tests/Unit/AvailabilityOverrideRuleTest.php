<?php

use App\Support\AvailabilityOverrideRule;

/*
| The admin override decision table. Pure: the booking count and the cap are
| passed in, nothing reads the database or the clock.
*/

it('refuses to override a booked date to Available', function () {
    // Cap is 2, so two or more bookings already mean the date is not free.
    expect(AvailabilityOverrideRule::evaluate('Available', 2, 2))->toBe(AvailabilityOverrideRule::AVAILABLE_AT_CAP)
        ->and(AvailabilityOverrideRule::evaluate('Available', 3, 2))->toBe(AvailabilityOverrideRule::AVAILABLE_AT_CAP)
        ->and(AvailabilityOverrideRule::evaluate('Available', 8, 2))->toBe(AvailabilityOverrideRule::AVAILABLE_AT_CAP);
});

it('allows Available while the date is still under the cap', function () {
    expect(AvailabilityOverrideRule::evaluate('Available', 0, 2))->toBe(AvailabilityOverrideRule::OK)
        ->and(AvailabilityOverrideRule::evaluate('Available', 1, 2))->toBe(AvailabilityOverrideRule::OK);
});

it('allows closing a date that holds nothing', function () {
    expect(AvailabilityOverrideRule::evaluate('Closed', 0, 2))->toBe(AvailabilityOverrideRule::OK);
});

it('requires a real reason to close a date that still has reservations', function () {
    expect(AvailabilityOverrideRule::evaluate('Closed', 3, 2))->toBe(AvailabilityOverrideRule::CLOSED_NEEDS_REASON)
        ->and(AvailabilityOverrideRule::evaluate('Closed', 3, 2, ''))->toBe(AvailabilityOverrideRule::CLOSED_NEEDS_REASON)
        ->and(AvailabilityOverrideRule::evaluate('Closed', 3, 2, '    '))->toBe(AvailabilityOverrideRule::CLOSED_NEEDS_REASON)
        ->and(AvailabilityOverrideRule::evaluate('Closed', 3, 2, 'maintenance'))->toBe(AvailabilityOverrideRule::CLOSED_NEEDS_CONFIRMATION);
});

it('requires the reservation count to be confirmed', function () {
    $reason = 'Hall repairs';

    expect(AvailabilityOverrideRule::evaluate('Closed', 3, 2, $reason, null))->toBe(AvailabilityOverrideRule::CLOSED_NEEDS_CONFIRMATION)
        ->and(AvailabilityOverrideRule::evaluate('Closed', 3, 2, $reason, 0))->toBe(AvailabilityOverrideRule::CLOSED_NEEDS_CONFIRMATION)
        ->and(AvailabilityOverrideRule::evaluate('Closed', 3, 2, $reason, 2))->toBe(AvailabilityOverrideRule::CLOSED_NEEDS_CONFIRMATION)
        ->and(AvailabilityOverrideRule::evaluate('Closed', 3, 2, $reason, 3))->toBe(AvailabilityOverrideRule::OK);
});

it('checks the reason before the confirmation', function () {
    // Both are wrong: the admin is told about the reason first, which is the
    // field that has to be filled in on screen.
    expect(AvailabilityOverrideRule::evaluate('Closed', 2, 2, '', null))
        ->toBe(AvailabilityOverrideRule::CLOSED_NEEDS_REASON);
});

it('leaves the other states alone', function () {
    foreach (['Half', 'Nearly', 'Full'] as $status) {
        expect(AvailabilityOverrideRule::evaluate($status, 9, 2))->toBe(AvailabilityOverrideRule::OK);
    }
});

it('treats a bookable cap of zero as a cap of one', function () {
    expect(AvailabilityOverrideRule::evaluate('Available', 1, 0))->toBe(AvailabilityOverrideRule::AVAILABLE_AT_CAP)
        ->and(AvailabilityOverrideRule::evaluate('Available', 0, 0))->toBe(AvailabilityOverrideRule::OK);
});

it('never counts a negative booking number', function () {
    expect(AvailabilityOverrideRule::evaluate('Closed', -4, 2))->toBe(AvailabilityOverrideRule::OK);
});

it('explains every refusal with a sentence', function () {
    foreach ([
        AvailabilityOverrideRule::AVAILABLE_AT_CAP,
        AvailabilityOverrideRule::CLOSED_NEEDS_REASON,
        AvailabilityOverrideRule::CLOSED_NEEDS_CONFIRMATION,
    ] as $code) {
        $message = AvailabilityOverrideRule::message($code, 3, 2);

        expect($message)->toBeString()->not->toBe('');
    }
});
