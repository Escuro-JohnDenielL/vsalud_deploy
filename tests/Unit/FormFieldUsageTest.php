<?php

use App\Support\FormFieldUsage;

/*
| The pattern used to decide whether a Form Builder field is still referenced by
| saved records. Pure string work, no database.
*/

it('looks for a quoted key so a prefix does not match', function () {
    expect(FormFieldUsage::jsonKeyPattern('name'))->toBe('%"name"%')
        ->and(FormFieldUsage::jsonKeyPattern('name'))->not->toBe(FormFieldUsage::jsonKeyPattern('full_name'));
});

it('escapes an underscore so it cannot act as a wildcard', function () {
    expect(FormFieldUsage::jsonKeyPattern('my_field'))->toBe('%"my\\_field"%');
});

it('escapes a percent sign so it cannot act as a wildcard', function () {
    expect(FormFieldUsage::jsonKeyPattern('50%off'))->toBe('%"50\\%off"%');
});

it('escapes a backslash', function () {
    expect(FormFieldUsage::jsonKeyPattern('a\\b'))->toBe('%"a\\\\b"%');
});
