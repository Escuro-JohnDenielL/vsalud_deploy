<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/*
 * These cases exercise the form token only. They boot the framework (so the
 * encrypter is available) but never touch a database.
 */

it('renders a signed timestamp the controller can read back', function () {
    $token = vs_feedback_form_token();

    expect(ctype_digit(Crypt::decryptString($token)))->toBeTrue();
});

it('cannot be decrypted once tampered with', function () {
    $token = vs_feedback_form_token();

    // Flip one character inside the payload; the MAC check must reject it.
    $tampered = substr($token, 0, 10)
        . ($token[10] === 'A' ? 'B' : 'A')
        . substr($token, 11);

    expect(fn () => Crypt::decryptString($tampered))
        ->toThrow(DecryptException::class);
});
