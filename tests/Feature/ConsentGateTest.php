<?php

use App\Support\ConsentGate;
use Illuminate\Support\Facades\DB;

/*
| Consent is a session flag set only by POST /agree. The refusal path runs
| before any model access, so it is testable with no database at all.
*/

it('refuses a patron submission without the consent flag and touches no table', function () {
    $queries = 0;
    DB::listen(function () use (&$queries) {
        $queries++;
    });

    $response = $this->post(route('patron.mreserve.submit'), []);

    $response->assertRedirect(route('patron.mreserve'));
    $response->assertSessionHas('consent_error', ConsentGate::MESSAGE);
    expect($queries)->toBe(0);
});

it('returns 403 JSON for an AJAX submission without the flag', function () {
    $response = $this->postJson(route('patron.mreserve.submit'), []);

    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => ConsentGate::MESSAGE,
        ]);
});

it('still gates a patron request that claims to be an admin', function () {
    $response = $this->post(route('patron.mreserve.submit'), [
        'created_by_type' => 'admin',
    ]);

    $response->assertRedirect(route('patron.mreserve'));
    $response->assertSessionHas('consent_error', ConsentGate::MESSAGE);
});

it('lets a consented patron and an admin past the gate', function () {
    expect(ConsentGate::required(false, true))->toBeFalse();
    expect(ConsentGate::required(true, false))->toBeFalse();
    expect(ConsentGate::required(false, false))->toBeTrue();
    expect(ConsentGate::required(false, null))->toBeTrue();
});
