<?php

use App\Models\Admin;
use App\Models\Availability;
use App\Models\Form;
use App\Models\FormField;
use App\Models\Inquiry;
use App\Models\Patron;
use App\Models\Reservation;
use App\Services\WaitlistService;
use App\Support\AvailabilityOverrideRule;
use App\Support\BookingRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

/*
| Server-side enforcement of the per-day event cap and the venue rule.
*/

uses(RefreshDatabase::class);

function capacityAdmin(): Admin
{
    return Admin::create([
        'email' => 'it@villasalud.test',
        'f_name' => 'Test',
        'l_name' => 'Admin',
        'phone' => '09170000000',
        'password' => bcrypt('secret'),
        'role' => 'super_admin',
        'mfa_method' => 'email',
        'mfa_setup_completed_at' => now(),
    ]);
}

function capacityActingAsAdmin(Admin $admin)
{
    return test()->actingAs($admin, 'admin')
        ->withSession(['mfa_verified_at' => now()]);
}

function capacityInquiry(string $date, string $venue = 'Villa I', string $time = '10:00 AM'): Inquiry
{
    static $counter = 0;
    $counter++;

    $patron = Patron::create([
        'email' => "capacity{$counter}@example.test",
        'name' => "Capacity Patron {$counter}",
        'contact_number' => '09171234567',
    ]);

    return Inquiry::create([
        'patron_id' => $patron->patron_id,
        'event_type' => 'Wedding Package',
        'theme_motif' => 'Floral',
        'venue' => $venue,
        'date' => $date,
        'time' => $time,
        'message' => 'Test booking',
        'status' => 'Pending',
        'form_data' => ['period' => str_contains($time, 'PM') ? 'PM' : 'AM'],
    ]);
}

function capacityReservation(string $date, string $venue, string $time = '10:00 AM'): Reservation
{
    $inquiry = capacityInquiry($date, $venue, $time);

    return Reservation::create([
        'inquiry_id' => $inquiry->inquiry_id,
        'patron_id' => $inquiry->patron_id,
        'date' => $date,
        'time' => $time,
        'venue' => $venue,
        'event_type' => 'Wedding Package',
        'theme_motif' => 'Floral',
        'message' => 'Filler',
        'status' => 'active',
    ]);
}

function capacityFillDay(string $date, int $count): void
{
    $venues = ['Villa I', 'Villa II', 'Elizabeth Hall', 'Private Pool'];

    for ($i = 0; $i < $count; $i++) {
        capacityReservation($date, $venues[$i % count($venues)]);
    }
}

function capacityReservationForm(): void
{
    $form = Form::create([
        'name' => 'Reservation',
        'slug' => 'reservation',
        'is_active' => true,
        'is_published' => true,
    ]);

    $fields = [
        ['name' => 'name', 'field_type' => 'text', 'required' => true],
        ['name' => 'email', 'field_type' => 'email', 'required' => true],
        ['name' => 'contact_number', 'field_type' => 'tel', 'required' => true],
        ['name' => 'date', 'field_type' => 'date', 'required' => true],
        ['name' => 'time', 'field_type' => 'time', 'required' => false],
        ['name' => 'period', 'field_type' => 'select', 'required' => false],
        ['name' => 'venue', 'field_type' => 'select', 'required' => true],
        ['name' => 'event_type', 'field_type' => 'select', 'required' => true],
        ['name' => 'theme_motif', 'field_type' => 'select', 'required' => false],
        ['name' => 'message', 'field_type' => 'textarea', 'required' => true],
    ];

    foreach ($fields as $order => $field) {
        FormField::create($field + [
            'form_id' => $form->id,
            'label' => ucfirst(str_replace('_', ' ', $field['name'])),
            'order' => $order,
            'is_active' => true,
        ]);
    }
}

it('refuses to convert an inquiry when the date is at the cap', function () {
    Mail::fake();

    $date = now()->addDays(20)->format('Y-m-d');
    capacityFillDay($date, 4);
    $inquiry = capacityInquiry($date);

    capacityActingAsAdmin(capacityAdmin())
        ->postJson("/admin/inquiries/{$inquiry->inquiry_id}/update-status", ['status' => 'Completed'])
        ->assertStatus(409)
        ->assertJson(['success' => false, 'message' => BookingRules::FULL_MESSAGE]);

    expect(Reservation::where('inquiry_id', $inquiry->inquiry_id)->exists())->toBeFalse()
        ->and($inquiry->fresh()->status)->toBe('Pending');
});

it('converts while the date is under the cap', function () {
    Mail::fake();

    $date = now()->addDays(21)->format('Y-m-d');
    capacityFillDay($date, 3);
    $inquiry = capacityInquiry($date, 'Villa I', '4:00 PM');

    capacityActingAsAdmin(capacityAdmin())
        ->postJson("/admin/inquiries/{$inquiry->inquiry_id}/update-status", ['status' => 'Completed'])
        ->assertOk()
        ->assertJson(['success' => true]);

    expect(Reservation::where('inquiry_id', $inquiry->inquiry_id)->where('status', 'active')->exists())->toBeTrue();
});

it('still refuses a second booking for the same venue, date and period', function () {
    Mail::fake();

    $date = now()->addDays(22)->format('Y-m-d');
    capacityReservation($date, 'Villa I', '10:00 AM');
    $inquiry = capacityInquiry($date, 'Villa I', '10:00 AM');

    capacityActingAsAdmin(capacityAdmin())
        ->postJson("/admin/inquiries/{$inquiry->inquiry_id}/update-status", ['status' => 'Completed'])
        ->assertStatus(409);

    expect(Reservation::where('inquiry_id', $inquiry->inquiry_id)->exists())->toBeFalse();
});

it('rejects inquiry creation when the date is at the cap', function () {
    Mail::fake();
    capacityReservationForm();

    $date = now()->addDays(23)->format('Y-m-d');
    capacityFillDay($date, 4);

    $this->postJson('/mreserve', [
        'name' => 'New Patron',
        'email' => 'new-patron@example.test',
        'contact_number' => '09179876543',
        'date' => $date,
        'time' => '10:00 AM',
        'period' => 'AM',
        'venue' => 'Villa II',
        'event_type' => 'Wedding Package',
        'theme_motif' => 'Floral',
        'message' => 'Please call me',
    ], ['Accept' => 'application/json'])
        ->assertStatus(409)
        ->assertJson(['success' => false, 'message' => BookingRules::FULL_MESSAGE]);

    expect(Inquiry::whereDate('date', $date)->count())->toBe(4);
});

it('counts open inquiries when enforcing the daily cap', function () {
    Mail::fake();
    capacityReservationForm();

    $date = now()->addDays(25)->format('Y-m-d');

    foreach (['Villa I', 'Villa II', 'Elizabeth Hall', 'Private Pool'] as $venue) {
        capacityInquiry($date, $venue, '10:00 AM');
    }

    // The fifth event asks for a different venue and period, so only the day
    // cap can refuse it.
    $this->postJson('/mreserve', [
        'name' => 'Fifth Patron',
        'email' => 'fifth-patron@example.test',
        'contact_number' => '09179876543',
        'date' => $date,
        'time' => '4:00 PM',
        'period' => 'PM',
        'venue' => 'Villa I',
        'event_type' => 'Wedding Package',
        'theme_motif' => 'Floral',
        'message' => 'Please call me',
    ], ['Accept' => 'application/json'])
        ->assertStatus(409)
        ->assertJson(['success' => false, 'message' => BookingRules::FULL_MESSAGE]);

    expect(Inquiry::whereDate('date', $date)->count())->toBe(4);
});

it('refuses to mark a date Available when open inquiries fill it', function () {
    $date = now()->addDays(26)->format('Y-m-d');

    foreach (['Villa I', 'Villa II', 'Elizabeth Hall', 'Private Pool'] as $venue) {
        capacityInquiry($date, $venue, '10:00 AM');
    }

    capacityActingAsAdmin(capacityAdmin())
        ->postJson(route('admin.availability.toggle'), [
            'date' => $date,
            'status' => 'Available',
        ])
        ->assertStatus(422)
        ->assertJson(['code' => AvailabilityOverrideRule::AVAILABLE_AT_CAP]);

    expect(Availability::whereDate('date', $date)->where('status', 'Available')->exists())->toBeFalse();
});

it('treats a capped date as full for the waitlist gate', function () {
    $date = now()->addDays(24)->format('Y-m-d');
    capacityFillDay($date, 4);

    expect(app(WaitlistService::class)->isDateFull($date))->toBeTrue();
});
