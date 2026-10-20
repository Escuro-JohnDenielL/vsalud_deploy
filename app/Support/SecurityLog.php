<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * Writes abuse signals from the public forms to the dedicated "security" log
 * channel (daily files plus stderr, so entries survive Railway redeploys).
 *
 * Only the event name, the route and the IP are recorded. The submitted name,
 * email and message are never written, so the log carries no personal data.
 */
class SecurityLog
{
    public static function throttled(string $route, ?string $ip): void
    {
        self::write('Rate limit triggered', $route, $ip);
    }

    public static function honeypot(string $route, ?string $ip): void
    {
        self::write('Honeypot triggered', $route, $ip);
    }

    public static function staleTimestamp(string $route, ?string $ip): void
    {
        self::write('Feedback form timestamp rejected', $route, $ip);
    }

    /**
     * A personal-data form was submitted without the consent flag. Only the
     * route and time are recorded: no name, email, IP or form content.
     */
    public static function consentMissing(string $route): void
    {
        Log::channel('security')->warning('Consent flag missing', [
            'route' => $route,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    private static function write(string $event, string $route, ?string $ip): void
    {
        Log::channel('security')->warning($event, [
            'route' => $route,
            'ip' => $ip,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
