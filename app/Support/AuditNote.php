<?php

namespace App\Support;

/**
 * The "why" that accompanies a decision an admin makes about somebody else's
 * booking: approving or denying a cancellation, and marking a payment refunded.
 *
 * Pure and static, so the rule is stated once, used by every controller that
 * needs it, and can be unit tested without a database.
 */
final class AuditNote
{
    /** Shorter notes are treated as empty: "ok" is not an explanation. */
    public const MIN_LENGTH = 5;

    /** Matches the admin_note column. */
    public const MAX_LENGTH = 1000;

    /**
     * Rules for a note that may be left blank.
     *
     * @return array<int, string>
     */
    public static function optionalRules(): array
    {
        return ['nullable', 'string', 'max:' . self::MAX_LENGTH];
    }

    /**
     * Rules for a note that has to be supplied and actually explain something.
     *
     * @return array<int, string>
     */
    public static function requiredRules(): array
    {
        return ['required', 'string', 'min:' . self::MIN_LENGTH, 'max:' . self::MAX_LENGTH];
    }

    /**
     * The note as it should read in an audit description.
     *
     * Returns an empty string when nothing was given, so callers can append it
     * unconditionally.
     */
    public static function describe(?string $note): string
    {
        $note = trim((string) $note);

        return $note === '' ? '' : ' Reason: ' . $note;
    }
}
