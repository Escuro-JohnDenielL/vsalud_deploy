<?php

use App\Support\VenueSlot;

/*
| Booking time slots live in config/booking.php. Every label must include an
| explicit am or pm, and the old admin slot texts must keep resolving.
| No database is used.
*/

it('resolves every configured slot to its period', function () {
    $slots = config('booking.time_slots');

    expect($slots)->toBeArray()->not->toBeEmpty();

    foreach ($slots as $period => $labels) {
        expect($labels)->not->toBeEmpty();

        foreach ($labels as $label) {
            expect($label)->toMatch('/(am|pm)/i');
            expect(VenueSlot::periodFromTimeText($label))->toBe($period);
        }
    }
});

it('still resolves the old admin slot texts', function () {
    $oldAdminSlots = [
        '9am–11am' => 'AM',
        '10am–12pm' => 'AM',
        '11am–1pm' => 'AM',
        '2pm–4pm' => 'PM',
        '3pm–5pm' => 'PM',
        '4pm–6pm' => 'PM',
        '9am-11am' => 'AM',
        '2pm-4pm' => 'PM',
    ];

    foreach ($oldAdminSlots as $label => $period) {
        expect(VenueSlot::periodFromTimeText($label))->toBe($period);
    }
});

it('keeps resolving legacy 24-hour time text', function () {
    expect(VenueSlot::periodFromTimeText('21:04:00'))->toBe('PM');
    expect(VenueSlot::periodFromTimeText('09:30:00'))->toBe('AM');
});
