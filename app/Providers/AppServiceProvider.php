<?php

namespace App\Providers;

use App\Support\SecurityLog;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
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
    }
}
