<?php

use App\Support\VenueSlot;

/*
| Venue booking conflicts: venue + date + period.
| Pure rules only, no database, so these run anywhere.
*/

it('normalises venue names for comparison', function () {
    expect(VenueSlot::normalize('  Villa   I '))->toBe('villa i')
        ->and(VenueSlot::normalize('villa i'))->toBe('villa i')
        ->and(VenueSlot::normalize('VILLA I'))->toBe('villa i')
        ->and(VenueSlot::normalize('Elizabeth Hall'))->toBe('elizabeth hall')
        ->and(VenueSlot::normalize(null))->toBe('')
        ->and(VenueSlot::normalize('   '))->toBe('');
});

it('treats blank and "Others" venues as exempt from conflicts', function () {
    expect(VenueSlot::isTrackable('Villa I'))->toBeTrue()
        ->and(VenueSlot::isTrackable(' vIlla ii '))->toBeTrue()
        ->and(VenueSlot::isTrackable('Others'))->toBeFalse()
        ->and(VenueSlot::isTrackable(' others '))->toBeFalse()
        ->and(VenueSlot::isTrackable(''))->toBeFalse()
        ->and(VenueSlot::isTrackable(null))->toBeFalse();
});

it('normalises a period and rejects anything else', function () {
    expect(VenueSlot::normalizePeriod('am'))->toBe('AM')
        ->and(VenueSlot::normalizePeriod(' PM '))->toBe('PM')
        ->and(VenueSlot::normalizePeriod('Morning'))->toBeNull()
        ->and(VenueSlot::normalizePeriod(null))->toBeNull();
});

it('reads the period from the booking form data', function () {
    expect(VenueSlot::periodFromFormData(['period' => 'AM']))->toBe('AM')
        ->and(VenueSlot::periodFromFormData(['period' => 'pm']))->toBe('PM')
        ->and(VenueSlot::periodFromFormData('{"period":"PM","venue":"Villa I"}'))->toBe('PM')
        ->and(VenueSlot::periodFromFormData(['venue' => 'Villa I']))->toBeNull()
        ->and(VenueSlot::periodFromFormData('not json'))->toBeNull()
        ->and(VenueSlot::periodFromFormData(null))->toBeNull();
});

it('derives the period from the start of the slot text', function () {
    // Explicit am/pm wins, so 2pm is PM even though 2 is less than 12.
    expect(VenueSlot::periodFromTimeText('9am–1pm'))->toBe('AM')
        ->and(VenueSlot::periodFromTimeText('2pm–4pm'))->toBe('PM')
        ->and(VenueSlot::periodFromTimeText('10am–12pm'))->toBe('AM')
        ->and(VenueSlot::periodFromTimeText('12pm–2pm'))->toBe('PM')
        // Legacy 24-hour values stored on the reservation row.
        ->and(VenueSlot::periodFromTimeText('09:00:00'))->toBe('AM')
        ->and(VenueSlot::periodFromTimeText('21:04:00'))->toBe('PM')
        ->and(VenueSlot::periodFromTimeText('12:00:00'))->toBe('PM')
        ->and(VenueSlot::periodFromTimeText('00:39:00'))->toBe('AM')
        // Unusable values.
        ->and(VenueSlot::periodFromTimeText(''))->toBeNull()
        ->and(VenueSlot::periodFromTimeText('noon'))->toBeNull()
        ->and(VenueSlot::periodFromTimeText('99:00'))->toBeNull()
        ->and(VenueSlot::periodFromTimeText(null))->toBeNull();
});

it('treats an existing row with no determinable period as occupying both', function () {
    expect(VenueSlot::occupiedPeriods('AM', '5pm–9pm'))->toBe(['AM'])
        ->and(VenueSlot::occupiedPeriods(null, '21:04:00'))->toBe(['PM'])
        ->and(VenueSlot::occupiedPeriods(null, '9am–1pm'))->toBe(['AM'])
        ->and(VenueSlot::occupiedPeriods(null, 'TBD'))->toBe(['AM', 'PM'])
        ->and(VenueSlot::occupiedPeriods(null, null))->toBe(['AM', 'PM']);
});

it('blocks a slot only when the periods overlap', function () {
    expect(VenueSlot::occupies(['AM', 'PM'], 'AM'))->toBeTrue()
        ->and(VenueSlot::occupies(['AM', 'PM'], 'pm'))->toBeTrue()
        ->and(VenueSlot::occupies(['AM'], 'AM'))->toBeTrue()
        ->and(VenueSlot::occupies(['AM'], 'PM'))->toBeFalse()
        ->and(VenueSlot::occupies(['PM'], 'AM'))->toBeFalse()
        ->and(VenueSlot::occupies([], 'AM'))->toBeFalse()
        ->and(VenueSlot::occupies(['AM'], null))->toBeFalse()
        ->and(VenueSlot::occupies(['AM'], 'Morning'))->toBeFalse();
});
