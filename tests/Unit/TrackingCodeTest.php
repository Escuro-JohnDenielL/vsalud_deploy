<?php

use App\Support\TrackingCode;

/*
| Tracking code format and the uniqueness retry. The generator is random, so
| the assertions are about the shape and about the retry loop, which is driven
| by a stub instead of the database.
*/

it('generates the VS- plus six uppercase hex format', function () {
    for ($i = 0; $i < 200; $i++) {
        expect(TrackingCode::random())->toMatch(TrackingCode::PATTERN);
    }
});

it('accepts the existing code format and rejects anything else', function () {
    expect(TrackingCode::isValid('VS-224D44'))->toBeTrue()
        ->and(TrackingCode::isValid('VS-ABCDEF'))->toBeTrue()
        ->and(TrackingCode::isValid('vs-224d44'))->toBeFalse()
        ->and(TrackingCode::isValid('VS-224D4'))->toBeFalse()
        ->and(TrackingCode::isValid('RSV-000123'))->toBeFalse()
        ->and(TrackingCode::isValid(null))->toBeFalse();
});

it('keeps asking until the code is unused', function () {
    $seen = [];

    $code = TrackingCode::unique(function (string $candidate) use (&$seen) {
        $seen[] = $candidate;

        // The first two candidates are already taken.
        return count($seen) <= 2;
    });

    expect($seen)->toHaveCount(3)
        ->and($code)->toMatch(TrackingCode::PATTERN)
        ->and($seen[0])->not->toBe($seen[1]);
});

it('gives up instead of looping forever', function () {
    expect(fn () => TrackingCode::unique(fn () => true, 5))
        ->toThrow(RuntimeException::class);
});
