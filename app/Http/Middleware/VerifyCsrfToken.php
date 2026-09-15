<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Auth;

class VerifyCsrfToken extends ValidateCsrfToken
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];

    /**
     * Handle an incoming request.
     *
     * Logging out is idempotent, so a stale CSRF token must not block it with a
     * confusing "419 Page Expired" screen.
     *
     * A stale token is normal whenever the logout form was rendered before the
     * session changed, for example when the admin:
     *   - has an older admin tab open while logging in again in another tab
     *     (every login regenerates the session, so older tabs keep the old token);
     *   - switched accounts (e.g. IT/super admin -> a newly created admin
     *     account) and then clicks "Logout" on the page from the first login;
     *   - double-clicks "Logout" or presses Back and re-submits the form
     *     (logout itself already invalidated the session and its token).
     *
     * In that case we complete the logout and send the admin to the login page
     * instead of aborting with 419. CSRF protection is unchanged for every
     * other request.
     */
    public function handle($request, Closure $next)
    {
        if ($this->isLogoutRequest($request) && ! $this->tokensMatch($request)) {
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('admin.login')
                ->with('success', 'Your previous session had already ended. Please log in again.');
        }

        return parent::handle($request, $next);
    }

    /**
     * Determine whether the request is a POST to the admin logout route.
     */
    protected function isLogoutRequest($request): bool
    {
        return $request->isMethod('POST') && $request->is('admin/logout');
    }
}
