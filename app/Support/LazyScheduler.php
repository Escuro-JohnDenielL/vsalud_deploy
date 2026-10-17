<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Runs the daily reminder commands from web traffic when no scheduler process
 * exists in production.
 *
 * Each command is attempted at most once per hour, and Cache::add doubles as
 * the guard: only the request that creates the key runs the command, so two
 * concurrent dashboard/calendar loads cannot start it twice. A failure is
 * logged and never reaches the response.
 */
class LazyScheduler
{
    /** Commands that are safe to run from a web request. */
    public const TASKS = [
        'app:send-event-reminders',
        'app:send-payment-reminders',
    ];

    /** Minimum seconds between two attempts of the same command. */
    public const INTERVAL_SECONDS = 3600;

    public function runDueTasks(): void
    {
        foreach (self::TASKS as $command) {
            try {
                // add() returns false when another request already claimed
                // this hour, so the command is skipped instead of duplicated.
                if (! Cache::add('lazy-task:' . $command, true, self::INTERVAL_SECONDS)) {
                    continue;
                }

                Artisan::call($command);
            } catch (\Throwable $e) {
                Log::warning('Lazy scheduler: ' . $command . ' failed: ' . $e->getMessage());
            }
        }
    }
}
