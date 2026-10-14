<?php

use App\Support\PatronAudit;

/*
| The shape of a patron audit entry. Pure: the description builder is exercised
| directly, so no database and no HTTP request are involved. What the entry must
| never contain is covered by the callers, which build the summary themselves.
*/

it('marks the actor as a patron', function () {
    expect(PatronAudit::describe('feedback submitted'))
        ->toBe('Patron: feedback submitted')
        ->and(PatronAudit::PREFIX)->toBe('Patron:')
        ->and(PatronAudit::ACTOR_ID)->toBe(0);
});

it('carries the tracking code when there is one', function () {
    expect(PatronAudit::describe('booking inquiry submitted', 'VS-1A2B3C'))
        ->toBe('Patron: booking inquiry submitted [tracking VS-1A2B3C]')
        ->and(PatronAudit::describe('booking inquiry submitted', '  VS-1A2B3C  '))
        ->toBe('Patron: booking inquiry submitted [tracking VS-1A2B3C]');
});

it('leaves the tracking code out when there is none', function () {
    expect(PatronAudit::describe('feedback submitted', null))
        ->toBe('Patron: feedback submitted')
        ->and(PatronAudit::describe('feedback submitted', ''))
        ->toBe('Patron: feedback submitted')
        ->and(PatronAudit::describe('feedback submitted', '   '))
        ->toBe('Patron: feedback submitted');
});

it('keeps the description on one clean line', function () {
    expect(PatronAudit::describe('  receipt uploaded  '))
        ->toBe('Patron: receipt uploaded');
});
