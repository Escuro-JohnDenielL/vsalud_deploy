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
 * Registered as *global* middleware (bootstrap/app.php) so responses that never
 * reach the "web" group — unmatched routes and their 404 pages — are covered.
 */
class SecurityHeaders
{
    /** Local <script src> origin the Vite dev server sets while `public/hot` exists. */
    private const VITE_HOT_FILE = 'hot';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if (! $response->headers->has('Content-Security-Policy')) {
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
        $script = ["'self'", "'unsafe-inline'", 'https://unpkg.com'];
        $style = ["'self'", "'unsafe-inline'", 'https://fonts.googleapis.com', 'https://unpkg.com'];
        $font = ["'self'", 'data:', 'https://fonts.gstatic.com', 'https://unpkg.com'];
        $connect = ["'self'"];

        if ($devOrigin = $this->viteDevOrigin()) {
            $script[] = $devOrigin;
            $style[] = $devOrigin;
            $connect[] = $devOrigin;
            $connect[] = str_replace('http://', 'ws://', $devOrigin);
        }

        return implode('; ', [
            "default-src 'self'",
            'script-src '.implode(' ', array_unique($script)),
            'style-src '.implode(' ', array_unique($style)),
            "img-src 'self' data: blob:",
            'font-src '.implode(' ', array_unique($font)),
            'connect-src '.implode(' ', array_unique($connect)),
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
        ]);
    }

    /**
     * Origin of the Vite dev server, if the app is running `npm run dev`.
     * Returns null in production, where public/hot is not shipped.
     */
    private function viteDevOrigin(): ?string
    {
        $hot = public_path(self::VITE_HOT_FILE);

        if (! is_file($hot)) {
            return null;
        }

        $url = trim((string) file_get_contents($hot));
        $parts = parse_url($url);

        if (! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        return $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
    }
}
