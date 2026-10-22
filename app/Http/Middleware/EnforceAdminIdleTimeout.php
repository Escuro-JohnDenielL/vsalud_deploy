<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs an admin out after a period of inactivity.
 *
 * Laravel's session lifetime (config/session.php) is a single idle limit shared
 * by every visitor. The admin panel needs a shorter one, because unattended
 * workstations would otherwise stay authenticated for the full session
 * lifetime. The timestamp lives in the session, so it follows the session
 * rather than the tab.
 *
 * Configure with ADMIN_IDLE_MINUTES (default 30). Set it to 0 to disable and
 * fall back to the framework session lifetime.
 */
class EnforceAdminIdleTimeout
{
    private const SESSION_KEY = 'admin_last_activity_at';

    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('admin');

        if (! $guard->check()) {
            return $next($request);
        }

        $minutes = (int) config('villasalud.admin_idle_minutes', 30);
        $lastActivity = $request->session()->get(self::SESSION_KEY);

        if ($minutes > 0 && is_numeric($lastActivity) && (now()->timestamp - (int) $lastActivity) > $minutes * 60) {
            $guard->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "Session expired after {$minutes} minutes of inactivity.",
                ], 401);
            }

            return redirect()->route('admin.login')->withErrors([
                'email' => "Session expired after {$minutes} minutes of inactivity. Please sign in again.",
            ]);
        }

        $request->session()->put(self::SESSION_KEY, now()->timestamp);

        return $next($request);
    }
}
