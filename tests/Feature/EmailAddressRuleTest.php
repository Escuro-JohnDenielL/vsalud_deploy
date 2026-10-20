<?php

use App\Rules\EmailAddress;
use Illuminate\Support\Facades\Validator;

/*
| Public-form email rule. No database.
*/

function emailPasses(string $value): bool
{
    return Validator::make(
        ['email' => $value],
        ['email' => ['required', 'string', new EmailAddress()]]
    )->passes();
}

it('accepts normal addresses with a real looking TLD', function () {
    expect(emailPasses('name@gmail.com'))->toBeTrue();
    expect(emailPasses('first.last+tag@sub.example.ph'))->toBeTrue();
});

it('rejects addresses without a dot, short TLDs and overlong values', function () {
    expect(emailPasses('name@localhost'))->toBeFalse();
    expect(emailPasses('name@gmail.c'))->toBeFalse();
    expect(emailPasses('name@@gmail.com'))->toBeFalse();
    expect(emailPasses(str_repeat('a', 250) . '@gmail.com'))->toBeFalse();
});
