<?php

namespace App\Providers;

use App\Support\SecurityLog;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        $this->configureRateLimiting();
    }

    /**
     * Named rate limiters for the public forms.
     *
     * The feedback form is limited per IP twice: 5 per hour and 20 per day. The
     * two windows use distinct ->by() keys on purpose. Laravel derives the cache
     * key from the limiter name plus that key, so sharing one key would make the
     * daily window reuse the hourly counter instead of counting on its own.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('feedback', function (Request $request) {
            $ip = (string) $request->ip();

            $reject = function (Request $request, array $headers) {
                SecurityLog::throttled('feedback.store', $request->ip());

                return response()
                    ->view('patron.feedback', ['rateLimited' => true], 429)
                    ->withHeaders($headers);
            };

            return [
                Limit::perHour(5)->by('feedback-hourly:' . $ip)->response($reject),
                Limit::perDay(20)->by('feedback-daily:' . $ip)->response($reject),
            ];
        });

        // Public venue availability feed for the "Venues on <date>" panel. The
        // panel is refreshed on every date or period change, so the ceiling is
        // generous but still bounded per IP.
        RateLimiter::for('venue-availability', function (Request $request) {
            return Limit::perMinute(60)->by('venue-availability:' . (string) $request->ip());
        });

        // Patron self-service pages that take a tracking code. Guessing a code
        // has to stay unattractive: the lookup is capped per IP, and the
        // controller adds its own per "code + IP" bucket that only counts
        // failed attempts (see CancellationController).
        RateLimiter::for('cancel-lookup', function (Request $request) {
            return Limit::perMinute(20)->by('cancel-lookup:' . (string) $request->ip());
        });

        RateLimiter::for('cancel-submit', function (Request $request) {
            return Limit::perMinute(20)->by('cancel-submit:' . (string) $request->ip());
        });

        RateLimiter::for('waitlist-join', function (Request $request) {
            return Limit::perHour(5)->by('waitlist-join:' . (string) $request->ip());
        });

        // Receipt uploads. The endpoint is bounded per IP, and the controller
        // adds a second bucket per "code + IP" that only counts failed
        // verifications (see Patron\PaymentController::store).
        RateLimiter::for('payment-submit', function (Request $request) {
            return Limit::perHour(10)->by('payment-submit:' . (string) $request->ip());
        });

        // Admin password reset. Both steps are already authenticated, so the
        // bucket is keyed on the admin id as well as the IP: three code
        // requests per ten minutes, ten change attempts per ten minutes. The
        // 429 body is JSON because the profile page reads it as JSON.
        RateLimiter::for('admin-password-code', function (Request $request) {
            return Limit::perMinutes(10, 3)
                ->by('admin-password-code:' . self::actorKey($request))
                ->response(fn (Request $request, array $headers) => response()->json([
                    'success' => false,
                    'message' => 'Too many verification codes requested. Please wait a few minutes and try again.',
                ], 429)->withHeaders($headers));
        });

        RateLimiter::for('admin-password-change', function (Request $request) {
            return Limit::perMinutes(10, 10)
                ->by('admin-password-change:' . self::actorKey($request))
                ->response(fn (Request $request, array $headers) => response()->json([
                    'success' => false,
                    'message' => 'Too many password change attempts. Please wait a few minutes and try again.',
                ], 429)->withHeaders($headers));
        });
    }

    /**
     * "admin id + IP" for the authenticated-admin limiters.
     */
    private static function actorKey(Request $request): string
    {
        return (string) (Auth::guard('admin')->id() ?? 'guest') . ':' . (string) $request->ip();
    }
}
