<?php

use App\Models\Admin;
use App\Models\Inquiry;
use App\Models\Patron;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;

/*
| A late reservation total must not silently value approved receipts: the
| admin sees the affected payments and confirms them explicitly.
*/

uses(RefreshDatabase::class);

function totalAdmin(): Admin
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

function totalActingAsAdmin(Admin $admin)
{
    return test()->actingAs($admin, 'admin')
        ->withSession(['mfa_verified_at' => now()]);
}

function totalBooking(?string $paymentAmount = null): array
{
    $patron = Patron::create([
        'email' => 'total@example.test',
        'name' => 'Total Patron',
        'contact_number' => '09171234567',
    ]);

    $inquiry = Inquiry::create([
        'patron_id' => $patron->patron_id,
        'event_type' => 'Wedding Package',
        'theme_motif' => 'Floral',
        'venue' => 'Villa I',
        'date' => now()->addDays(30)->format('Y-m-d'),
        'time' => '10:00 AM',
        'message' => 'Total test',
        'status' => 'Completed',
    ]);

    $reservation = Reservation::create([
        'inquiry_id' => $inquiry->inquiry_id,
        'patron_id' => $patron->patron_id,
        'date' => $inquiry->date,
        'time' => $inquiry->time,
        'venue' => $inquiry->venue,
        'event_type' => $inquiry->event_type,
        'theme_motif' => $inquiry->theme_motif,
        'message' => $inquiry->message,
        'status' => 'active',
    ]);

    $payment = Payment::create([
        'full_name' => 'Total Patron',
        'payment_type' => 'half',
        'payment_method' => 'gcash',
        'tracking_code' => 'PAY-TOTAL-1',
        'reservation_code' => $inquiry->tracking_code,
        'inquiry_id' => $inquiry->inquiry_id,
        'receipt_path' => 'receipts/test.jpg',
        'email' => $patron->email,
        'status' => 'approved',
        'amount' => $paymentAmount,
    ]);

    return [$reservation, $payment];
}

it('asks for confirmation before valuing approved payments', function () {
    [$reservation, $payment] = totalBooking();

    $response = totalActingAsAdmin(totalAdmin())
        ->patchJson("/admin/reservations/{$reservation->reserve_id}/total", [
            'total_amount' => '40000',
        ]);

    $response->assertStatus(409)->assertJson([
        'success' => false,
        'needs_confirmation' => true,
        'affected_count' => 1,
    ]);

    expect($response->json('amounts.0.payment_id'))->toBe($payment->payment_id)
        ->and($response->json('amounts.0.amount'))->toBe('10000.00')
        ->and($payment->fresh()->amount)->toBeNull()
        ->and($reservation->fresh()->total_amount)->toBeNull();
});

it('applies the values only when confirm is set', function () {
    [$reservation, $payment] = totalBooking();

    totalActingAsAdmin(totalAdmin())
        ->patchJson("/admin/reservations/{$reservation->reserve_id}/total", [
            'total_amount' => '40000',
            'confirm' => true,
        ])
        ->assertOk()
        ->assertJson(['success' => true]);

    expect($payment->fresh()->amount)->toBe('10000.00')
        ->and($reservation->fresh()->total_amount)->toBe('40000.00');
});

it('saves a total normally when every approved payment already has an amount', function () {
    [$reservation, $payment] = totalBooking('10000.00');

    totalActingAsAdmin(totalAdmin())
        ->patchJson("/admin/reservations/{$reservation->reserve_id}/total", [
            'total_amount' => '40000',
        ])
        ->assertOk()
        ->assertJson(['success' => true]);

    expect($payment->fresh()->amount)->toBe('10000.00')
        ->and($reservation->fresh()->total_amount)->toBe('40000.00');
});
