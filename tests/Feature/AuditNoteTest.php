<?php

use App\Support\AuditNote;
use Illuminate\Support\Facades\Validator;

/*
| The reason requirement for cancelling/refunding decisions, and the way a
| reason is folded into an audit description. Run from the Feature suite so the
| validator factory is available, but nothing here touches the database.
*/

function auditNoteFails(array $rules, mixed $note): bool
{
    return Validator::make(['admin_note' => $note], ['admin_note' => $rules])->fails();
}

it('requires a reason when the note is mandatory', function () {
    $rules = AuditNote::requiredRules();

    expect(auditNoteFails($rules, null))->toBeTrue()
        ->and(auditNoteFails($rules, ''))->toBeTrue()
        ->and(auditNoteFails($rules, '    '))->toBeTrue();
});

it('treats a too-short reason as no reason at all', function () {
    $rules = AuditNote::requiredRules();

    expect(auditNoteFails($rules, 'ok'))->toBeTrue()
        ->and(auditNoteFails($rules, 'no'))->toBeTrue()
        ->and(auditNoteFails($rules, 'nope'))->toBeTrue();
});

it('accepts a reason of five characters or more', function () {
    $rules = AuditNote::requiredRules();

    expect(auditNoteFails($rules, 'fraud'))->toBeFalse()
        ->and(auditNoteFails($rules, 'Duplicate booking, paid twice'))->toBeFalse();
});

it('caps the reason at the column length', function () {
    $rules = AuditNote::requiredRules();

    expect(auditNoteFails($rules, str_repeat('a', AuditNote::MAX_LENGTH)))->toBeFalse()
        ->and(auditNoteFails($rules, str_repeat('a', AuditNote::MAX_LENGTH + 1)))->toBeTrue();
});

it('leaves the note optional where it always was', function () {
    $rules = AuditNote::optionalRules();

    expect(auditNoteFails($rules, null))->toBeFalse()
        ->and(auditNoteFails($rules, ''))->toBeFalse()
        ->and(auditNoteFails($rules, 'confirmed by phone'))->toBeFalse();
});

it('writes the reason into the audit description', function () {
    expect(AuditNote::describe('Duplicate booking'))
        ->toBe(' Reason: Duplicate booking')
        ->and(AuditNote::describe('  spaced  '))->toBe(' Reason: spaced')
        ->and(AuditNote::describe(null))->toBe('')
        ->and(AuditNote::describe('   '))->toBe('');
});
