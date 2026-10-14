<?php

use App\Support\IdentityMatch;

/*
| Patron identity rule: the email submitted with a tracking code must match the
| booking email after trimming and lowercasing. Pure string work, no database.
*/

it('matches an email regardless of case and surrounding space', function () {
    expect(IdentityMatch::emailMatches('Maria@Example.com', 'maria@example.com'))->toBeTrue()
        ->and(IdentityMatch::emailMatches('maria@example.com', '  MARIA@EXAMPLE.COM  '))->toBeTrue()
        ->and(IdentityMatch::emailMatches('  maria@example.com ', 'Maria@Example.com'))->toBeTrue();
});

it('rejects a different address', function () {
    expect(IdentityMatch::emailMatches('maria@example.com', 'jose@example.com'))->toBeFalse()
        ->and(IdentityMatch::emailMatches('maria@example.com', 'maria@example.com.ph'))->toBeFalse();
});

it('never matches when the booking has no email on file', function () {
    expect(IdentityMatch::emailMatches(null, 'anyone@example.com'))->toBeFalse()
        ->and(IdentityMatch::emailMatches('', ''))->toBeFalse()
        ->and(IdentityMatch::emailMatches('   ', '   '))->toBeFalse();
});

it('normalizes to a trimmed lowercase address', function () {
    expect(IdentityMatch::normalizeEmail('  A@B.CO '))->toBe('a@b.co')
        ->and(IdentityMatch::normalizeEmail(null))->toBe('');
});
