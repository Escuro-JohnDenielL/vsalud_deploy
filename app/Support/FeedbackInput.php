<?php

namespace App\Support;

/**
 * Pure input rules for the public feedback form.
 *
 * Kept free of the database and the HTTP layer so the rules can be unit tested
 * directly, and so the controller and the tests share one definition.
 */
class FeedbackInput
{
    /** Fastest a human could plausibly fill the form. */
    public const MIN_FILL_SECONDS = 5;

    /** Oldest a rendered form is still accepted (about three hours). */
    public const MAX_FILL_SECONDS = 10800;

    /**
     * Letters, marks and digits plus space, comma, period, hyphen and straight
     * or curly apostrophes. Anchored and unicode aware. The straight apostrophe
     * is written as \x27 so the pattern needs no quote escaping.
     */
    public const NAME_PATTERN = '/^[\p{L}\p{M}\p{N} ,.\x27’‘-]+$/u';

    /**
     * True when the hidden field was filled, which only a script does.
     */
    public static function honeypotTripped(?string $value): bool
    {
        return trim((string) $value) !== '';
    }

    /**
     * True when the form was filled in a plausible amount of time: not faster
     * than MIN_FILL_SECONDS, not older than MAX_FILL_SECONDS.
     */
    public static function isFillAgeAcceptable(?int $startedAt, int $now): bool
    {
        if ($startedAt === null) {
            return false;
        }

        $age = $now - $startedAt;

        return $age >= self::MIN_FILL_SECONDS && $age <= self::MAX_FILL_SECONDS;
    }

    /**
     * Remove every control character from a single-line value.
     */
    public static function cleanLine(string $value): string
    {
        return trim(preg_replace('/[\x00-\x1F\x7F]+/', '', $value) ?? '');
    }

    /**
     * Remove control characters but keep newlines in the message body.
     */
    public static function cleanMessage(string $value): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", $value);

        return trim(preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]+/', '', $value) ?? '');
    }
}
