<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Shared limits for the reminder commands.
 */
class ReminderPolicy
{
    /** Hard cap on emails per command run. */
    public const MAX_PER_RUN = 10;

    /**
     * Start of the cutoff day, or null when the setting is blank/invalid
     * (in which case no created_at cutoff is applied).
     */
    public static function since(): ?Carbon
    {
        $value = config('reminders.since');

        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
