<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

/**
 * The ceilings on the patron self-service routes, asserted from the limiter
 * definitions only. No database and no HTTP, so these run even when the
 * migration history cannot be replayed.
 */
beforeEach(function () {
    Cache::flush();
});

/** The limiter callback may return one Limit or a list of them. */
function limitsFor(string $name, string $path = '/x'): array
{
    $limits = RateLimiter::limiter($name)(Request::create($path, 'POST'));

    return is_array($limits) ? $limits : [$limits];
}

it('caps the cancellation lookup per IP and per minute', function () {
    $limits = limitsFor('cancel-lookup', '/cancel-reservation/lookup');

    expect($limits)->toHaveCount(1)
        ->and($limits[0]->maxAttempts)->toBe(20)
        ->and($limits[0]->decaySeconds)->toBe(60)
        ->and($limits[0]->key)->toContain('cancel-lookup:');
});

it('caps the cancellation submit per IP and per minute', function () {
    $limits = limitsFor('cancel-submit', '/cancel-reservation/submit');

    expect($limits)->toHaveCount(1)
        ->and($limits[0]->maxAttempts)->toBe(20)
        ->and($limits[0]->decaySeconds)->toBe(60)
        ->and($limits[0]->key)->toContain('cancel-submit:');
});

it('caps waitlist joins at five per IP per hour', function () {
    $limits = limitsFor('waitlist-join', '/waitlist/join');

    expect($limits)->toHaveCount(1)
        ->and($limits[0]->maxAttempts)->toBe(5)
        ->and($limits[0]->decaySeconds)->toBe(3600)
        ->and($limits[0]->key)->toContain('waitlist-join:');
});

it('caps receipt uploads at ten per IP per hour', function () {
    $limits = limitsFor('payment-submit', '/submit-payment');

    expect($limits)->toHaveCount(1)
        ->and($limits[0]->maxAttempts)->toBe(10)
        ->and($limits[0]->decaySeconds)->toBe(3600)
        ->and($limits[0]->key)->toContain('payment-submit:');
});

it('keys the per-IP buckets on the IP, not on a shared key', function () {
    $one = limitsFor('payment-submit')[0]->key;
    $other = RateLimiter::limiter('payment-submit')(
        Request::create('/submit-payment', 'POST', [], [], [], ['REMOTE_ADDR' => '203.0.113.9'])
    );

    expect($other->key)->not->toBe($one)
        ->and($other->key)->toContain('203.0.113.9');
});
