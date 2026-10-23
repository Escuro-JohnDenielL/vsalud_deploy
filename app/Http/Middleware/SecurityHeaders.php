<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds the baseline response headers that do not depend on a per-route policy:
 * MIME sniffing is disabled, referrers are trimmed to the origin for cross-site
 * requests, HSTS is sent on HTTPS, and authenticated responses are kept out of
 * the browser cache.
 *
 * Framing is decided per request. Public read-only pages may be embedded (the
 * capstone demo page on Google Sites embeds the landing page), while the admin
 * area stays same-origin only so a framed copy cannot drive an authenticated
 * action (clickjacking).
 *
 * X-Frame-Options has no allow-list — ALLOW-FROM was dropped by every modern
 * browser — so it is removed on frameable responses and the CSP frame-ancestors
 * directive below becomes the single source of truth for those pages.
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
    /**
     * Origins allowed to frame the public pages when the deployment does not
     * configure config('villasalud.embed.allowed_ancestors'). Google Sites serves
     * an embed either straight from sites.google.com or through one of Google's
     * opaque content hosts, so both shapes are listed.
     */
    private const DEFAULT_FRAME_ANCESTORS = [
        'https://sites.google.com',
        'https://*.google.com',
        'https://*.googleusercontent.com',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $frameable = $this->isFrameable($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        if ($frameable) {
            $response->headers->remove('X-Frame-Options');
        } else {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        }

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if (! app()->environment('local') && ! $response->headers->has('Content-Security-Policy')) {
            $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy($frameable));
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
     * Public, read-only responses may be embedded. The whole admin area —
     * login, MFA, panel — stays same-origin only.
     *
     * The decision deliberately ignores the session. Deciding on "is this
     * visitor signed in" made the same URL answer differently per browser: a
     * person who had signed into the admin panel in that browser got
     * SAMEORIGIN on the landing page, so the Google Sites embed worked in a
     * clean profile and failed for them. Every authenticated surface in this
     * app lives under /admin, so the path check covers the clickjacking risk on
     * its own.
     *
     * Form submissions are not frameable either: the session cookie is
     * SameSite=Lax, so a cross-site iframe would post without it and fail the
     * CSRF check anyway.
     */
    private function isFrameable(Request $request): bool
    {
        return $request->isMethodCacheable() && ! $request->is('admin', 'admin/*');
    }

    /**
     * Baseline policy that matches how the views are written today. External
     * origins are limited to what the templates actually load.
     */
    private function contentSecurityPolicy(bool $frameable = false): string
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
            'frame-ancestors ' . ($frameable ? $this->frameAncestors() : "'self'"),
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
        ]);
    }

    /**
     * frame-ancestors allow-list: this site plus the configured embed hosts.
     */
    private function frameAncestors(): string
    {
        $configured = config('villasalud.embed.allowed_ancestors');
        $configured = is_array($configured) ? $configured : self::DEFAULT_FRAME_ANCESTORS;

        $origins = array_unique(array_map(
            static fn ($origin) => trim((string) $origin),
            array_merge(["'self'"], $configured)
        ));

        $origins = array_values(array_filter($origins, static fn ($origin) => $origin !== ''));

        return $origins === [] ? "'self'" : implode(' ', $origins);
    }
}
