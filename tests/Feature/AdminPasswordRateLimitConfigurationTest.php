<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

/**
 * The ceilings and the JSON body of the admin password-reset limiters. No
 * database and no HTTP, so these run when the migration history cannot.
 */
beforeEach(function () {
    Cache::flush();
});

/** The limiter callback may return one Limit or a list of them. */
function adminPasswordLimits(string $name): array
{
    $limits = RateLimiter::limiter($name)(Request::create('/admin/profile/send-reset-code', 'POST'));

    return is_array($limits) ? $limits : [$limits];
}

it('allows three reset codes per admin and IP per ten minutes', function () {
    $limit = adminPasswordLimits('admin-password-code')[0];

    expect($limit->maxAttempts)->toBe(3)
        ->and($limit->decaySeconds)->toBe(600)
        ->and($limit->key)->toContain('admin-password-code:');
});

it('allows ten password change attempts per admin and IP per ten minutes', function () {
    $limit = adminPasswordLimits('admin-password-change')[0];

    expect($limit->maxAttempts)->toBe(10)
        ->and($limit->decaySeconds)->toBe(600)
        ->and($limit->key)->toContain('admin-password-change:');
});

it('answers a throttled reset with a JSON body the profile page can read', function () {
    foreach (['admin-password-code', 'admin-password-change'] as $name) {
        $limit = adminPasswordLimits($name)[0];

        expect($limit->responseCallback)->not->toBeNull();

        $response = ($limit->responseCallback)(Request::create('/admin/profile/send-reset-code', 'POST'), []);

        expect($response->getStatusCode())->toBe(429)
            ->and(json_decode($response->getContent(), true))
            ->toHaveKey('success', false)
            ->toHaveKey('message');
    }
});

it('keys the admin buckets per IP as well as per admin', function () {
    $sameAdminOtherIp = RateLimiter::limiter('admin-password-code')(
        Request::create('/admin/profile/send-reset-code', 'POST', [], [], [], ['REMOTE_ADDR' => '198.51.100.7'])
    );

    expect($sameAdminOtherIp->key)->toBe('admin-password-code:guest:198.51.100.7');
});
