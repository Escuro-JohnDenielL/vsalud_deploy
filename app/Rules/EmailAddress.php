<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Public-form email address: RFC-valid, at most 254 characters, a domain with
 * a dot, and a TLD of at least two letters. No DNS lookup: PHP's resolver is
 * blocking and has no timeout, so a dead domain could add seconds to a submit.
 */
class EmailAddress implements ValidationRule
{
    public const MESSAGE = 'Enter a valid email address, for example name@gmail.com';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail(self::MESSAGE);
            return;
        }

        $email = trim($value);

        if ($email === '' || strlen($email) > 254) {
            $fail(self::MESSAGE);
            return;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)
            || ! preg_match('/^[^@\s]+@[^@\s]+\.[A-Za-z]{2,}$/', $email)) {
            $fail(self::MESSAGE);
        }
    }
}
