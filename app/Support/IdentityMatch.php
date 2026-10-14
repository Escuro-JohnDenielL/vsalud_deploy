<?php

namespace App\Support;

/**
 * Patron identity comparison.
 *
 * A patron has no account, so ownership of a reservation is proved by knowing
 * the tracking code and the email address the booking was submitted with. Both
 * sides of that comparison go through this class, so every entry point uses the
 * same rule (trimmed, case-insensitive) and the rule can be unit tested without
 * a database or an HTTP request.
 */
class IdentityMatch
{
    /**
     * Normalized form of an email address: trimmed and lowercased.
     */
    public static function normalizeEmail(?string $email): string
    {
        return mb_strtolower(trim((string) $email));
    }

    /**
     * True when the submitted email identifies the reservation owner.
     *
     * An empty expected value never matches, so a reservation with no email on
     * file cannot be claimed by sending a blank address.
     */
    public static function emailMatches(?string $expected, ?string $submitted): bool
    {
        $expected = self::normalizeEmail($expected);

        if ($expected === '') {
            return false;
        }

        return $expected === self::normalizeEmail($submitted);
    }
}
