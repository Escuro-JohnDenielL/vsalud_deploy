<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds the baseline response headers that do not depend on a per-route policy:
 * MIME sniffing is disabled, the site can only be framed by itself, referrers
 * are trimmed to the origin for cross-site requests, HSTS is sent on HTTPS,
 * and authenticated responses are kept out of the browser cache.
 *
 * The CSP is intentionally a baseline: the Blade views still use inline
 * <script> blocks and inline event handlers, so script-src has to allow
 * 'unsafe-inline'. Tightening it to a nonce is a separate change. A nonce
 * would silently do nothing while 'unsafe-inline' is present, which is why the
 * policy is written with the allowance visible instead of pretending otherwise.
 *
 * The CSP is skipped in the "local" environment, where the Vite dev server
 * serves assets from a different origin (and an IPv6 origin that browsers do
 * not accept as a CSP source). Production and testing always get the policy.
 *
 * Registered as *global* middleware (bootstrap/app.php) so responses that never
 * reach the "web" group — unmatched routes and their 404 pages — are covered.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if (! app()->environment('local') && ! $response->headers->has('Content-Security-Policy')) {
            $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());
        }

        if ($this->isAuthenticated($request)) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }

    /**
     * True when the request carries a signed-in admin (or legacy web user).
     * Checked after the response so the session middleware has already run.
     */
    private function isAuthenticated(Request $request): bool
    {
        if (! $request->hasSession()) {
            return false;
        }

        return $request->user('admin') !== null || $request->user() !== null;
    }

    /**
     * Baseline policy that matches how the views are written today. External
     * origins are limited to what the templates actually load.
     */
    private function contentSecurityPolicy(): string
    {
        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://unpkg.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://unpkg.com",
            "img-src 'self' data: blob:",
            "font-src 'self' data: https://fonts.gstatic.com https://unpkg.com",
            "connect-src 'self'",
            // The feedback page embeds a Google Maps location.
            "frame-src 'self' https://www.google.com https://maps.google.com",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
        ]);
    }
}
