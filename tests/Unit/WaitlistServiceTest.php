<?php

use App\Services\WaitlistService;

/*
| The waitlist email rule and the lazy expiry window. Pure: no database, no
| HTTP. The database-backed ordering, offer and expiry behaviour is exercised
| from the pages, since there is no test database here.
*/

it('normalizes a waitlist email to trimmed lowercase', function () {
    expect(WaitlistService::normalizeEmail('  Maria@Example.COM '))->toBe('maria@example.com')
        ->and(WaitlistService::normalizeEmail(null))->toBe('')
        ->and(WaitlistService::normalizeEmail('already@lower.test'))->toBe('already@lower.test');
});

it('treats case and spacing variants as the same patron', function () {
    // This is what stops one patron holding two places on the same date.
    $first = WaitlistService::normalizeEmail('Maria@Example.com');
    $second = WaitlistService::normalizeEmail(' maria@EXAMPLE.com  ');

    expect($first)->toBe($second);
});

it('keeps genuinely different addresses apart', function () {
    expect(WaitlistService::normalizeEmail('maria@example.com'))
        ->not->toBe(WaitlistService::normalizeEmail('maria@example.com.ph'))
        ->and(WaitlistService::normalizeEmail('maria@example.com'))
        ->not->toBe(WaitlistService::normalizeEmail('maria+2@example.com'));
});

it('holds the lazy expiry sweep back for five minutes', function () {
    expect(WaitlistService::EXPIRY_GUARD_SECONDS)->toBe(300)
        ->and(WaitlistService::EXPIRY_GUARD_KEY)->toBe('waitlist:expiry-sweep');
});
