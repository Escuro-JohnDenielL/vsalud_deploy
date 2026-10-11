<?php

use App\Models\PaymentSetting;

if (! function_exists('payment_setting')) {
    /**
     * Get a payment setting value by key.
     */
    function payment_setting(string $key, mixed $default = null): mixed
    {
        return PaymentSetting::get($key, $default);
    }
}

if (! function_exists('vs_feedback_form_token')) {
    /**
     * Encrypted start time rendered into the public feedback form and checked
     * server-side. A script cannot decrypt it to fake an older time, and a
     * stale or replayed form can be rejected by age.
     */
    function vs_feedback_form_token(): string
    {
        return \Illuminate\Support\Facades\Crypt::encryptString((string) now()->timestamp);
    }
}

if (! function_exists('vs_date')) {
    /**
     * Format a date as "Oct 10, 2026" (presentation only; never mutates the value).
     */
    function vs_date(mixed $value, string $fallback = 'None'): string
    {
        $date = vs_parse_datetime($value);

        return $date ? $date->format('M j, Y') : $fallback;
    }
}

if (! function_exists('vs_time')) {
    /**
     * Format a time as "12:08 PM".
     */
    function vs_time(mixed $value, string $fallback = 'None'): string
    {
        $date = vs_parse_datetime($value);

        return $date ? $date->format('g:i A') : $fallback;
    }
}

if (! function_exists('vs_datetime')) {
    /**
     * Format a date and time as "Oct 10, 2026, 12:08 PM". The time is dropped
     * when it is exactly midnight (for date-only values stored as datetimes).
     */
    function vs_datetime(mixed $value, string $fallback = 'None'): string
    {
        $date = vs_parse_datetime($value);

        if (! $date) {
            return $fallback;
        }

        if ($date->format('H:i:s') === '00:00:00') {
            return $date->format('M j, Y');
        }

        return $date->format('M j, Y, g:i A');
    }
}

if (! function_exists('vs_parse_datetime')) {
    /**
     * Internal helper: coerce a value into a Carbon instance, or null.
     */
    function vs_parse_datetime(mixed $value): ?\Carbon\CarbonInterface
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \Carbon\CarbonInterface) {
            return $value;
        }

        try {
            return \Carbon\Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
