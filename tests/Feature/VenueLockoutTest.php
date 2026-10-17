<?php

use App\Models\Form;
use App\Models\FormField;
use App\Models\Inquiry;
use App\Models\Reservation;
use App\Support\VenueSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

/*
| Only an admin-confirmed booking (an active reservation) occupies a venue
| slot. Pending inquiries may repeat the same slot; a new inquiry is refused
| only once a reservation exists for that venue + date + period.
*/

uses(RefreshDatabase::class);

function lockoutReservationForm(): void
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
        ['name' => 'period', 'field_type' => 'select', 'required' => false, 'options' => ['AM', 'PM']],
        ['name' => 'venue', 'field_type' => 'select', 'required' => true, 'options' => ['Villa I', 'Villa II', 'Elizabeth Hall', 'Private Pool']],
        ['name' => 'event_type', 'field_type' => 'select', 'required' => true, 'options' => ['Wedding Package', 'Birthday Package']],
        ['name' => 'theme_motif', 'field_type' => 'select', 'required' => false, 'options' => ['Floral', 'Modern']],
        ['name' => 'message', 'field_type' => 'textarea', 'required' => false],
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

function lockoutBooking(string $date, string $period = 'AM', string $email = 'slot@example.test'): array
{
    return [
        'name' => 'Slot Patron',
        'email' => $email,
        'contact_number' => '09179876543',
        'date' => $date,
        'time' => $period === 'AM' ? '10:00 AM' : '4:00 PM',
        'period' => $period,
        'venue' => 'Villa I',
        'event_type' => 'Wedding Package',
        'theme_motif' => 'Floral',
        'message' => 'Slot lockout test',
    ];
}

it('allows duplicate inquiries while none is confirmed', function () {
    Mail::fake();
    lockoutReservationForm();

    $date = now()->addDays(5)->format('Y-m-d');

    $this->post('/mreserve', lockoutBooking($date, 'AM', 'first@example.test'))->assertRedirect();
    $this->post('/mreserve', lockoutBooking($date, 'AM', 'second@example.test'))->assertRedirect();

    expect(Inquiry::whereDate('date', $date)->count())->toBe(2);
});

it('reports venues as available while only inquiries exist', function () {
    lockoutReservationForm();

    $date = now()->addDays(6)->format('Y-m-d');
    $this->post('/mreserve', lockoutBooking($date))->assertRedirect();

    $response = $this->getJson('/venue-availability?date=' . $date)->assertOk();
    $villa = collect($response->json('venues'))->firstWhere('name', 'Villa I');

    expect($villa['occupied'])->toBe(['AM' => false, 'PM' => false]);
});

it('refuses a new inquiry once the slot has a confirmed reservation', function () {
    Mail::fake();
    lockoutReservationForm();

    $date = now()->addDays(7)->format('Y-m-d');
    $this->post('/mreserve', lockoutBooking($date, 'AM', 'confirmed@example.test'))->assertRedirect();

    $inquiry = Inquiry::whereDate('date', $date)->firstOrFail();

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
        'form_data' => $inquiry->form_data,
    ]);

    $this->postJson('/mreserve', lockoutBooking($date, 'AM', 'late@example.test'), ['Accept' => 'application/json'])
        ->assertStatus(409)
        ->assertJson(['success' => false, 'message' => VenueSlot::CONFLICT_MESSAGE]);

    expect(Inquiry::whereDate('date', $date)->count())->toBe(1);
});

it('releases the slot when the confirmed reservation is cancelled', function () {
    Mail::fake();
    lockoutReservationForm();

    $date = now()->addDays(8)->format('Y-m-d');
    $this->post('/mreserve', lockoutBooking($date, 'AM', 'cancel@example.test'))->assertRedirect();

    $inquiry = Inquiry::whereDate('date', $date)->firstOrFail();
    $reservation = Reservation::create([
        'inquiry_id' => $inquiry->inquiry_id,
        'patron_id' => $inquiry->patron_id,
        'date' => $inquiry->date,
        'time' => $inquiry->time,
        'venue' => $inquiry->venue,
        'event_type' => $inquiry->event_type,
        'theme_motif' => $inquiry->theme_motif,
        'message' => $inquiry->message,
        'status' => 'active',
        'form_data' => $inquiry->form_data,
    ]);

    $reservation->update(['status' => 'cancelled']);

    $this->post('/mreserve', lockoutBooking($date, 'AM', 'replacement@example.test'))->assertRedirect();

    expect(Inquiry::whereDate('date', $date)->count())->toBe(2);
});
