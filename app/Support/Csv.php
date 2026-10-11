<?php

namespace App\Support;

/**
 * CSV cell helpers.
 *
 * A spreadsheet treats a cell that begins with =, +, - or @ as a formula, and a
 * leading tab or carriage return can be used to smuggle one in. Prefixing a
 * single quote keeps the value as text. This is applied to text columns only;
 * numeric columns are never passed through it.
 *
 * The prefix quote may itself be visible in tools that do not understand the
 * marker. That is an accepted trade-off: staying inert matters more than the
 * cosmetic quote.
 */
class Csv
{
    /** First characters that can make a spreadsheet evaluate a cell. */
    private const RISKY = ['=', '+', '-', '@', "\t", "\r"];

    public static function neutralize(mixed $value): string
    {
        $value = (string) $value;

        if ($value !== '' && in_array($value[0], self::RISKY, true)) {
            return "'" . $value;
        }

        return $value;
    }
}
