<?php

use App\Models\Inquiry;
use App\Models\Patron;
use App\Models\Reservation;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;

/*
| The calendar payload feeds both Make Reservation calendars. Only confirmed
| bookings (active reservations) appear; a pending inquiry does not. Confirming
| adds a reservation row for the same booking, which must not be counted twice.
*/

uses(RefreshDatabase::class);

function calendarPatronInquiry(string $date, string $status = 'Pending', string $time = '10:00 AM'): Inquiry
{
    static $counter = 0;
    $counter++;

    $patron = Patron::create([
        'email' => "calendar{$counter}@example.test",
        'name' => "Calendar Patron {$counter}",
        'contact_number' => '09171234567',
    ]);

    return Inquiry::create([
        'patron_id' => $patron->patron_id,
        'event_type' => 'Wedding Package',
        'theme_motif' => 'Floral',
        'venue' => 'Villa I',
        'date' => $date,
        'time' => $time,
        'message' => 'Calendar payload test',
        'status' => $status,
        'form_data' => ['period' => 'AM'],
    ]);
}

function calendarPayloadDates(): array
{
    return app(AvailabilityService::class)->calendarPayload()['dates'];
}

it('does not show an unconfirmed inquiry on the calendar', function () {
    $date = now()->addDays(30)->format('Y-m-d');
    calendarPatronInquiry($date);

    expect(calendarPayloadDates())->not->toHaveKey($date);
});

it('counts a confirmed booking once, not twice', function () {
    $date = now()->addDays(31)->format('Y-m-d');
    $inquiry = calendarPatronInquiry($date, 'Completed');

    Reservation::create([
        'inquiry_id' => $inquiry->inquiry_id,
        'patron_id' => $inquiry->patron_id,
        'date' => $inquiry->date,
        'time' => $inquiry->time,
        'venue' => $inquiry->venue,
        'event_type' => $inquiry->event_type,
        'theme_motif' => $inquiry->theme_motif,
        'message' => $inquiry->message,
        'status' => 'active',
    ]);

    $dates = calendarPayloadDates();

    expect($dates[$date]['booked'])->toBe(1)
        ->and($dates[$date]['occupancy'])->toBe(['villa i' => ['AM' => true]]);
});

it('leaves a cancelled booking off the calendar', function () {
    $date = now()->addDays(32)->format('Y-m-d');
    calendarPatronInquiry($date, 'Cancelled');

    expect(calendarPayloadDates())->not->toHaveKey($date);
});
