<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds the three baseline response headers that do not require a policy to be
 * written: MIME sniffing is disabled, the site can only be framed by itself,
 * and referrers are trimmed to the origin for cross-site requests.
 *
 * Deliberately no Content-Security-Policy and no HSTS — both would need a
 * deliberate policy/rollout and are out of scope here.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        return $response;
    }
}
