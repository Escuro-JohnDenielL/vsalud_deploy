<?php

namespace App\Support;

/**
 * The patron consent gate for forms that collect personal data.
 *
 * Consent is a session flag set only by POST /agree. Admins entering a booking
 * on a patron's behalf are exempt: they are staff, not the data subject.
 */
class ConsentGate
{
    public const MESSAGE = 'Please read and agree to the Data Privacy Act notice before submitting.';

    /**
     * True when the submission must be refused.
     */
    public static function required(bool $isAdminSubmission, mixed $sessionFlag): bool
    {
        return ! $isAdminSubmission && $sessionFlag !== true;
    }
}
