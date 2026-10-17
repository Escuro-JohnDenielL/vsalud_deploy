<?php

use App\Mail\WaitlistSlotAvailable;
use App\Models\Admin;
use App\Models\Availability;
use App\Models\CancellationRequest;
use App\Models\Form;
use App\Models\FormField;
use App\Models\Inquiry;
use App\Models\Patron;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Waitlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/*
| End-to-end checks for the three patron journeys:
|   - joining a waitlist and being offered a freed slot
|   - requesting and deciding a cancellation
|   - inquiry -> reservation -> payment approval -> completed
*/

uses(RefreshDatabase::class);

function journeyAdmin(): Admin
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

function journeyActingAsAdmin(Admin $admin)
{
    return test()->actingAs($admin, 'admin')
        ->withSession(['mfa_verified_at' => now()]);
}

function journeyReservationForm(): Form
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
        [
            'name' => 'venue',
            'field_type' => 'select',
            'required' => true,
            'options' => ['Villa I', 'Villa II', 'Elizabeth Hall', 'Private Pool'],
        ],
        [
            'name' => 'event_type',
            'field_type' => 'select',
            'required' => true,
            'options' => ['Wedding Package', 'Birthday Package'],
        ],
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

    return $form;
}

function journeyBooking(?string $date = null): array
{
    $patron = Patron::create([
        'email' => 'maria@example.test',
        'name' => 'Maria Santos',
        'contact_number' => '09171234567',
    ]);

    $inquiry = Inquiry::create([
        'patron_id' => $patron->patron_id,
        'event_type' => 'Wedding Package',
        'theme_motif' => 'Floral',
        'venue' => 'Villa I',
        'date' => $date ?? now()->addDays(10)->format('Y-m-d'),
        'time' => '10:00 AM',
        'message' => 'Please call me',
        'status' => 'Completed',
    ]);

    $reservation = Reservation::create([
        'inquiry_id' => $inquiry->inquiry_id,
        'patron_id' => $patron->patron_id,
        'date' => $date ?? now()->addDays(10)->format('Y-m-d'),
        'time' => $inquiry->time,
        'venue' => $inquiry->venue,
        'event_type' => $inquiry->event_type,
        'theme_motif' => $inquiry->theme_motif,
        'message' => $inquiry->message,
        'status' => 'active',
    ]);

    return [$patron, $inquiry, $reservation];
}

function journeyClosedDate(): string
{
    $date = now()->addDays(20)->format('Y-m-d');

    Availability::create([
        'date' => $date,
        'status' => 'Closed',
        'is_override' => true,
    ]);

    return $date;
}

/*
|--------------------------------------------------------------------------
| Waitlist
|--------------------------------------------------------------------------
*/

it('adds a patron to the waitlist for a date that is full', function () {
    $date = journeyClosedDate();

    $this->postJson(route('waitlist.join'), [
        'patron_name' => 'Maria Santos',
        'patron_email' => 'Maria@Example.com ',
        'date' => $date,
    ])->assertOk()->assertJson(['success' => true]);

    $this->assertDatabaseHas('waitlist', [
        'patron_email' => 'maria@example.com',
        'requested_date' => $date,
        'status' => 'waiting',
    ]);
});

it('refuses a waitlist join when the date still has availability', function () {
    $this->postJson(route('waitlist.join'), [
        'patron_name' => 'Maria Santos',
        'patron_email' => 'maria@example.test',
        'date' => now()->addDays(20)->format('Y-m-d'),
    ])->assertOk()->assertJson(['success' => false]);

    expect(Waitlist::count())->toBe(0);
});

it('does not queue the same patron twice for one date', function () {
    $date = journeyClosedDate();
    $payload = [
        'patron_name' => 'Maria Santos',
        'patron_email' => 'maria@example.test',
        'date' => $date,
    ];

    $this->postJson(route('waitlist.join'), $payload)->assertJson(['success' => true]);
    $this->postJson(route('waitlist.join'), [
        'patron_name' => 'Maria Santos',
        'patron_email' => 'MARIA@example.test',
        'date' => $date,
    ])->assertOk()->assertJson(['success' => false]);

    expect(Waitlist::count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Cancellation request
|--------------------------------------------------------------------------
*/

it('looks up an owned reservation by code and booking email', function () {
    [$patron, $inquiry] = journeyBooking();

    $this->postJson(route('cancel-reservation.lookup'), [
        'tracking_code' => $inquiry->tracking_code,
        'email' => $patron->email,
    ])->assertOk()
        ->assertJson([
            'success' => true,
            'reservation' => [
                'venue' => 'Villa I',
                'package' => 'Wedding Package',
            ],
        ]);
});

it('answers a wrong email with the generic failure', function () {
    [, $inquiry] = journeyBooking();

    $this->postJson(route('cancel-reservation.lookup'), [
        'tracking_code' => $inquiry->tracking_code,
        'email' => 'someone-else@example.test',
    ])->assertOk()->assertJson(['success' => false]);
});

it('creates one pending cancellation and refuses a duplicate', function () {
    [$patron, $inquiry, $reservation] = journeyBooking();

    $payload = [
        'tracking_code' => $inquiry->tracking_code,
        'patron_email' => $patron->email,
        'reason' => 'Schedule conflict',
    ];

    $this->postJson(route('cancel-reservation.submit'), $payload)
        ->assertOk()->assertJson(['success' => true]);

    $this->postJson(route('cancel-reservation.submit'), $payload)
        ->assertOk()->assertJson(['success' => false]);

    expect(CancellationRequest::where('reserve_id', $reservation->reserve_id)->count())->toBe(1)
        ->and(CancellationRequest::first()->status)->toBe('pending');
});

it('cancels the reservation when an admin approves the request', function () {
    Mail::fake();

    [, $inquiry, $reservation] = journeyBooking();
    $request = CancellationRequest::create([
        'reserve_id' => $reservation->reserve_id,
        'patron_email' => 'maria@example.test',
        'reason' => 'Schedule conflict',
        'status' => 'pending',
    ]);

    $admin = journeyAdmin();

    journeyActingAsAdmin($admin)
        ->postJson(route('admin.cancellations.approve', $request->id), [
            'admin_note' => 'Patron confirmed the conflict by phone',
        ])
        ->assertOk()->assertJson(['success' => true]);

    expect($reservation->fresh()->status)->toBe('cancelled')
        ->and($inquiry->fresh()->status)->toBe('Cancelled')
        ->and($request->fresh()->status)->toBe('approved');
});

it('offers a freed slot to the next patron on the waitlist', function () {
    Mail::fake();

    $date = now()->addDays(10)->format('Y-m-d');
    [, , $reservation] = journeyBooking($date);

    Waitlist::create([
        'patron_name' => 'Waiting Patron',
        'patron_email' => 'waiting@example.test',
        'requested_date' => $date,
        'status' => 'waiting',
    ]);

    $request = CancellationRequest::create([
        'reserve_id' => $reservation->reserve_id,
        'patron_email' => 'maria@example.test',
        'status' => 'pending',
    ]);

    journeyActingAsAdmin(journeyAdmin())
        ->postJson(route('admin.cancellations.approve', $request->id), [
            'admin_note' => 'Patron could not attend',
        ])
        ->assertOk()->assertJson(['success' => true]);

    Mail::assertSent(WaitlistSlotAvailable::class, function (WaitlistSlotAvailable $mail) {
        return $mail->hasTo('waiting@example.test');
    });

    expect(Waitlist::first()->status)->toBe('notified');
});

it('leaves the reservation active when an admin denies the request', function () {
    Mail::fake();

    [, $inquiry, $reservation] = journeyBooking();
    $request = CancellationRequest::create([
        'reserve_id' => $reservation->reserve_id,
        'patron_email' => 'maria@example.test',
        'status' => 'pending',
    ]);

    journeyActingAsAdmin(journeyAdmin())
        ->postJson(route('admin.cancellations.deny', $request->id), [
            'admin_note' => 'Event is within the non-refundable window',
        ])
        ->assertOk()->assertJson(['success' => true]);

    expect($reservation->fresh()->status)->toBe('active')
        ->and($inquiry->fresh()->status)->toBe('Completed')
        ->and($request->fresh()->status)->toBe('denied');
});

/*
|--------------------------------------------------------------------------
| Inquiry -> reservation -> payment -> completed
|--------------------------------------------------------------------------
*/

it('shows the tracking code once after an inquiry is submitted', function () {
    Mail::fake();
    journeyReservationForm();

    $this->post('/mreserve', [
        'name' => 'Ana Cruz',
        'email' => 'ana@example.test',
        'contact_number' => '09179876543',
        'date' => now()->addDays(45)->format('Y-m-d'),
        'time' => '10:00 AM',
        'period' => 'AM',
        'venue' => 'Villa I',
        'event_type' => 'Wedding Package',
        'theme_motif' => 'Floral',
        'message' => 'Please call me',
    ])->assertRedirect(route('patron.home'));

    $inquiry = Inquiry::firstOrFail();

    $this->get(route('patron.home'))
        ->assertOk()
        ->assertSee('trackingCodeModal', false)
        ->assertSee('display:flex', false)
        ->assertSee($inquiry->tracking_code);

    // The flash lasts one view, so a later visit no longer shows the code.
    $this->get(route('patron.home'))->assertDontSee($inquiry->tracking_code);
});

it('runs a full inquiry to completed reservation journey', function () {
    Mail::fake();
    Storage::fake('public');

    journeyReservationForm();

    $date = now()->addDays(30)->format('Y-m-d');

    $this->post('/mreserve', [
        'name' => 'Ana Cruz',
        'email' => 'ana@example.test',
        'contact_number' => '09179876543',
        'date' => $date,
        'time' => '10:00 AM',
        'period' => 'AM',
        'venue' => 'Villa I',
        'event_type' => 'Wedding Package',
        'theme_motif' => 'Floral',
        'message' => 'Please call me',
    ])->assertRedirectContains(route('patron.home'));

    $inquiry = Inquiry::firstOrFail();
    $patron = Patron::where('email', 'ana@example.test')->firstOrFail();

    expect($inquiry->status)->toBe('Pending')
        ->and($inquiry->tracking_code)->not->toBeEmpty();

    $admin = journeyAdmin();

    journeyActingAsAdmin($admin)
        ->postJson("/admin/inquiries/{$inquiry->inquiry_id}/update-status", ['status' => 'Completed'])
        ->assertOk()->assertJson(['success' => true]);

    $reservation = Reservation::where('inquiry_id', $inquiry->inquiry_id)->firstOrFail();

    expect($reservation->status)->toBe('active')
        ->and($inquiry->fresh()->status)->toBe('Completed');

    journeyActingAsAdmin($admin)
        ->patchJson("/admin/reservations/{$reservation->reserve_id}/total", ['total_amount' => '50000'])
        ->assertOk()->assertJson(['success' => true]);

    $this->post(route('payment.store'), [
        'full_name' => 'Ana Cruz',
        'payment_type' => 'full',
        'payment_method' => 'gcash',
        'email' => $patron->email,
        'reservation_code' => $inquiry->tracking_code,
        'receipt' => UploadedFile::fake()->image('receipt.jpg'),
    ], ['Accept' => 'application/json'])
        ->assertOk()->assertJson(['success' => true]);

    $payment = Payment::where('inquiry_id', $inquiry->inquiry_id)->firstOrFail();

    expect($payment->status)->toBe('pending')
        ->and($payment->receipt_path)->not->toBeEmpty();

    journeyActingAsAdmin($admin)
        ->patchJson("/admin/payments/{$payment->payment_id}/status", ['status' => 'approved'])
        ->assertOk()->assertJson(['success' => true]);

    $payment->refresh();

    expect($payment->status)->toBe('approved')
        ->and($payment->amount)->toBe('50000.00');

    journeyActingAsAdmin($admin)
        ->patchJson("/admin/reservations/{$reservation->reserve_id}/status", ['status' => 'completed'])
        ->assertOk()->assertJson(['success' => true]);

    expect($reservation->fresh()->status)->toBe('completed');

    journeyActingAsAdmin($admin)
        ->get(route('admin.payment-logs'))
        ->assertOk()
        ->assertSee('Fully paid')
        ->assertSee('50,000.00');
});

it('records a 25 percent downpayment and allows the balance to be paid later', function () {
    Mail::fake();
    Storage::fake('public');

    [, $inquiry, $reservation] = journeyBooking();
    $reservation->update(['total_amount' => '40000.00']);

    $admin = journeyAdmin();

    $submit = function () use ($inquiry) {
        return $this->post(route('payment.store'), [
            'full_name' => 'Maria Santos',
            'payment_type' => 'half',
            'payment_method' => 'bank',
            'email' => 'maria@example.test',
            'reservation_code' => $inquiry->tracking_code,
            'receipt' => UploadedFile::fake()->image('receipt.jpg'),
        ], ['Accept' => 'application/json']);
    };

    $submit()->assertOk()->assertJson(['success' => true]);

    $first = Payment::latest('payment_id')->firstOrFail();

    journeyActingAsAdmin($admin)
        ->patchJson("/admin/payments/{$first->payment_id}/status", ['status' => 'approved'])
        ->assertOk()->assertJson(['success' => true]);

    expect($first->fresh()->amount)->toBe('10000.00');

    // After the downpayment the patron still owes 30,000.00, so a second
    // receipt has to be accepted.
    $submit()->assertOk()->assertJson(['success' => true]);

    $second = Payment::where('payment_id', '!=', $first->payment_id)->firstOrFail();

    journeyActingAsAdmin($admin)
        ->patchJson("/admin/payments/{$second->payment_id}/status", ['status' => 'approved'])
        ->assertOk()->assertJson(['success' => true]);

    $approved = Payment::where('inquiry_id', $inquiry->inquiry_id)
        ->where('status', 'approved')
        ->sum('amount');

    expect(bcadd((string) $approved, '0', 2))->toBe('20000.00');
});

it('reconciles an approved payment when the total is set afterwards', function () {
    Mail::fake();
    Storage::fake('public');

    [, $inquiry, $reservation] = journeyBooking();

    $admin = journeyAdmin();

    // No total has been agreed yet, but the office can still accept a receipt.
    $this->post(route('payment.store'), [
        'full_name' => 'Maria Santos',
        'payment_type' => 'full',
        'payment_method' => 'cash',
        'email' => 'maria@example.test',
        'reservation_code' => $inquiry->tracking_code,
        'receipt' => UploadedFile::fake()->image('receipt.jpg'),
    ], ['Accept' => 'application/json'])->assertOk()->assertJson(['success' => true]);

    $payment = Payment::firstOrFail();

    journeyActingAsAdmin($admin)
        ->patchJson("/admin/payments/{$payment->payment_id}/status", ['status' => 'approved'])
        ->assertOk()->assertJson(['success' => true]);

    expect($payment->fresh()->amount)->toBeNull();

    journeyActingAsAdmin($admin)
        ->patchJson("/admin/reservations/{$reservation->reserve_id}/total", ['total_amount' => '30000'])
        ->assertStatus(409)
        ->assertJson(['needs_confirmation' => true, 'affected_count' => 1]);

    expect($payment->fresh()->amount)->toBeNull();

    journeyActingAsAdmin($admin)
        ->patchJson("/admin/reservations/{$reservation->reserve_id}/total", [
            'total_amount' => '30000',
            'confirm' => true,
        ])
        ->assertOk()->assertJson(['success' => true]);

    expect($payment->fresh()->amount)->toBe('30000.00');
});
