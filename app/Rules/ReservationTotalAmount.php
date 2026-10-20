<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Reservation total: numbers only, greater than zero, at most two decimals,
 * and no more than 99,999,999.99. Commas and spaces are ignored so "1,250.50"
 * is accepted, and the normalized value is what gets stored.
 */
class ReservationTotalAmount implements ValidationRule
{
    public const MAX = '99999999.99';

    public const MESSAGE = 'Enter a reservation total greater than 0, using numbers only and at most 2 decimal places.';

    public static function normalize(mixed $value): string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return '';
        }

        return str_replace([',', ' '], '', trim((string) $value));
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $normalized = self::normalize($value);

        if ($normalized === '' || ! preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $normalized)) {
            $fail(self::MESSAGE);
            return;
        }

        if (bccomp(bcadd($normalized, '0', 2), '0.00', 2) <= 0) {
            $fail(self::MESSAGE);
        }
    }
}
