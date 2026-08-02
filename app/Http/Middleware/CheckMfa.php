<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMfa
{
    /**
     * Number of hours an MFA verification is valid for.
     */
    const MFA_SESSION_HOURS = 6;

    /**
     * Handle an incoming request.
     *
     * After the admin is authenticated via 'auth:admin', this middleware checks
     * if MFA has been verified within the last 6 hours.
     *
     * If MFA verification has expired → redirect to challenge page.
     * If MFA is not set up yet → redirect to the setup page (first-time).
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Admin|null $admin */
        $admin = $request->user('admin');

        if (!$admin) {
            return $next($request);
        }

        // If the admin has MFA enabled, check if verified within the last 6 hours
        if ($admin->hasMfaEnabled()) {
            $mfaVerifiedAt = session('mfa_verified_at');

            // Check session first, then fall back to DB (persists across logouts)
            if (!$mfaVerifiedAt) {
                $mfaVerifiedAt = $admin->mfa_verified_at;
            }

            if ($mfaVerifiedAt && now()->diffInHours($mfaVerifiedAt) < self::MFA_SESSION_HOURS) {
                // Restore to session if it came from DB
                session(['mfa_verified_at' => $mfaVerifiedAt]);
                return $next($request);
            }

            // MFA expired or not set — redirect to challenge
            session()->forget('mfa_verified_at');
            session(['mfa_redirect_intended' => $request->fullUrl()]);

            return redirect()->route('admin.mfa.challenge');
        }

        // If the admin hasn't set up MFA yet and we're not on the setup page
        if (!$request->routeIs('admin.mfa.setup') && !$request->routeIs('admin.mfa.setup.submit')) {
            session(['mfa_redirect_intended' => $request->fullUrl()]);
            return redirect()->route('admin.mfa.setup');
        }

        return $next($request);
    }

    /**
     * Get the remaining time before MFA re-verification is needed.
     * Returns a human-readable string or null if no MFA session is active.
     */
    public static function getRemainingTime(): ?string
    {
        $mfaVerifiedAt = session('mfa_verified_at');
        if (!$mfaVerifiedAt) {
            return null;
        }

        $elapsed = now()->diffInHours($mfaVerifiedAt);
        $remaining = self::MFA_SESSION_HOURS - $elapsed;

        if ($remaining <= 0) {
            return null;
        }

        $hours = floor($remaining);
        $minutes = floor(($remaining - $hours) * 60);

        if ($hours > 0) {
            return "{$hours}h {$minutes}m remaining";
        }

        return "{$minutes}m remaining";
    }
}
