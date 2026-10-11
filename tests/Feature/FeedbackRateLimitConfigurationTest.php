<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\RateLimiter;

/**
 * These cases do not touch the database, so they run even while the migration
 * history cannot be replayed (see the note on the two MySQL-only migrations).
 * The database-backed behaviour lives in PublicFeedbackSecurityTest.
 */
beforeEach(function () {
    Cache::flush();
});

it('defines the feedback hourly and daily windows with separate counters', function () {
    $limits = RateLimiter::limiter('feedback')(Request::create('/feedback', 'POST'));

    // Distinct ->by() keys are what stops the daily window from reusing the
    // hourly counter; identical keys would share one store entry.
    expect($limits)->toHaveCount(2)
        ->and($limits[0]->key)->not->toBe($limits[1]->key)
        ->and($limits[0]->decaySeconds)->toBe(3600)
        ->and($limits[1]->decaySeconds)->toBe(86400);
});

it('short circuits a honeypot submission with the success response', function () {
    $this->from('/feedback')
        ->post('/feedback', [
            'name' => 'Bot',
            'email' => 'bot@example.test',
            'message' => 'spam',
            'form_started_at' => Crypt::encryptString((string) (now()->timestamp - 10)),
            'hp_ref' => 'filled-by-a-bot',
        ])
        ->assertRedirect('/feedback')
        ->assertSessionHas('success');
});
