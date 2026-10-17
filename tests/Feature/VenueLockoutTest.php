<?php

use App\Models\Form;
use App\Models\FormField;
use App\Models\Inquiry;
use App\Support\VenueSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

/*
| An open inquiry holds its venue + date + period. A second patron cannot
| submit the same slot, the venue panel reports the period as occupied, and
| cancelling the inquiry releases it.
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

function lockoutBooking(string $date, string $period = 'AM'): array
{
    return [
        'name' => 'Slot Patron',
        'email' => 'slot-' . uniqid() . '@example.test',
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

it('blocks a second open inquiry for the same venue, date and period', function () {
    Mail::fake();
    lockoutReservationForm();

    $date = now()->addDays(5)->format('Y-m-d');

    $this->post('/mreserve', lockoutBooking($date, 'AM'))->assertRedirect();

    $this->postJson('/mreserve', lockoutBooking($date, 'AM'), ['Accept' => 'application/json'])
        ->assertStatus(409)
        ->assertJson(['success' => false, 'message' => VenueSlot::CONFLICT_MESSAGE]);

    expect(Inquiry::whereDate('date', $date)->count())->toBe(1);
});

it('allows the other period on the same date', function () {
    Mail::fake();
    lockoutReservationForm();

    $date = now()->addDays(6)->format('Y-m-d');

    $this->post('/mreserve', lockoutBooking($date, 'AM'))->assertRedirect();
    $this->post('/mreserve', lockoutBooking($date, 'PM'))->assertRedirect();

    expect(Inquiry::whereDate('date', $date)->count())->toBe(2);
});

it('reports an open inquiry as occupying the venue for its period only', function () {
    lockoutReservationForm();

    $date = now()->addDays(7)->format('Y-m-d');
    $this->post('/mreserve', lockoutBooking($date, 'AM'))->assertRedirect();

    $response = $this->getJson('/venue-availability?date=' . $date)->assertOk();

    $villa = collect($response->json('venues'))->firstWhere('name', 'Villa I');

    expect($villa['occupied'])->toBe(['AM' => true, 'PM' => false]);
});

it('releases the slot when the inquiry is cancelled', function () {
    Mail::fake();
    lockoutReservationForm();

    $date = now()->addDays(8)->format('Y-m-d');
    $this->post('/mreserve', lockoutBooking($date, 'AM'))->assertRedirect();

    Inquiry::whereDate('date', $date)->firstOrFail()->update(['status' => 'Cancelled']);

    $this->post('/mreserve', lockoutBooking($date, 'AM'))->assertRedirect();

    expect(Inquiry::whereDate('date', $date)->count())->toBe(2);
});
