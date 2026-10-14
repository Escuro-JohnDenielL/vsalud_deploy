<?php

namespace App\Support;

use RuntimeException;

/**
 * Patron tracking codes: "VS-" followed by six uppercase hex characters.
 *
 * The format is unchanged from the codes already in the database, but the value
 * is drawn from random_bytes instead of uniqid(), which is time based and
 * predictable. Callers always ask for a code that no row uses yet.
 */
class TrackingCode
{
    /** The shape every code must have. */
    public const PATTERN = '/^VS-[0-9A-F]{6}$/';

    /** How many collisions are tolerated before giving up. */
    public const MAX_ATTEMPTS = 20;

    /**
     * A new random code, not yet checked against anything.
     */
    public static function random(): string
    {
        return 'VS-' . strtoupper(bin2hex(random_bytes(3)));
    }

    /**
     * True when the value looks like a tracking code.
     */
    public static function isValid(?string $code): bool
    {
        return is_string($code) && preg_match(self::PATTERN, $code) === 1;
    }

    /**
     * A code that $exists() reports as unused.
     *
     * @param  callable(string):bool  $exists
     */
    public static function unique(callable $exists, int $maxAttempts = self::MAX_ATTEMPTS): string
    {
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $code = self::random();

            if (! $exists($code)) {
                return $code;
            }
        }

        throw new RuntimeException('Could not generate an unused tracking code.');
    }
}
