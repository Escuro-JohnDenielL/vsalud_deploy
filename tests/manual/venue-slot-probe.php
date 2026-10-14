<?php

/**
 * One-shot probe for the venue slot lock. Manual verification only.
 *
 * Attempts a guarded create for a single venue + date + period and prints the
 * outcome. Run two of these in parallel (see verify-venue-slot-conflict.sh) to
 * confirm that exactly one of them wins the slot.
 *
 * It attaches a probe reservation to an existing inquiry (the reservation table
 * requires an inquiry) and uses a probe venue name, so real bookings are never
 * touched. Clean up with:
 *
 *   php tests/manual/venue-slot-probe.php --cleanup "Probe Venue X" 2099-12-31
 *
 * Two conditions are enforced before anything runs:
 *
 *   1. APP_ENV must be "local" AND the database host must be localhost or
 *      127.0.0.1. This cannot be pointed at a shared database by accident.
 *   2. Run it with CACHE_STORE=database, and against a local COPY of the
 *      database that already has the cache and cache_locks tables. Only then
 *      does the lock exercise VenueOccupancyService the same way a single
 *      production container does: acquire an entry in cache_locks, recheck
 *      inside the transaction, release after the commit. With the default
 *      "file" store the lock is a file lock instead and cache_locks is never
 *      touched, so the run would not prove the cache_locks path. The store in
 *      use is always printed, and a warning is printed when it is not
 *      "database".
 *
 *   CACHE_STORE=database php tests/manual/venue-slot-probe.php "Probe Venue X" 2099-12-31 AM
 */

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Inquiry;
use App\Models\Reservation;
use App\Services\VenueOccupancyService;

/**
 * Hard stop unless this is a local environment talking to a local database.
 */
function assertLocalEnvironment(): void
{
    $env = (string) app()->environment();

    if ($env !== 'local') {
        fwrite(STDERR, "Refusing to run: APP_ENV is '{$env}', expected 'local'.\n");
        exit(1);
    }

    $connection = (string) config('database.default');
    $host = (string) config("database.connections.{$connection}.host", '');

    if (! in_array($host, ['localhost', '127.0.0.1'], true)) {
        fwrite(STDERR, "Refusing to run: database host is '{$host}', expected localhost or 127.0.0.1.\n");
        exit(1);
    }

    fwrite(STDOUT, "environment check: APP_ENV={$env} db_connection={$connection} db_host={$host} cache_store=" . cacheStoreName() . "\n");
}

/**
 * The cache store in use. "database" means the lock writes to cache_locks.
 */
function cacheStoreName(): string
{
    return (string) config('cache.default');
}

/**
 * Says so when the store is not the database one, because then the run does not
 * exercise the cache_locks path.
 */
function warnUnlessDatabaseCache(): void
{
    $store = cacheStoreName();

    if ($store !== 'database') {
        fwrite(STDERR, "WARNING: cache store is '{$store}', not 'database'.\n");
        fwrite(STDERR, "         Re-run with CACHE_STORE=database against a local copy that has the cache and cache_locks tables,\n");
        fwrite(STDERR, "         otherwise the parallel run proves the file lock only.\n");
    }
}

$args = array_slice($argv, 1);

if (($args[0] ?? '') === '--check') {
    assertLocalEnvironment();
    warnUnlessDatabaseCache();
    exit(0);
}

assertLocalEnvironment();
warnUnlessDatabaseCache();

if (($args[0] ?? '') === '--cleanup') {
    $venue = $args[1] ?? 'Probe Venue X';
    $date = $args[2] ?? '2099-12-31';

    $deleted = Reservation::where('venue', $venue)->whereDate('date', $date)->delete();
    fwrite(STDOUT, "deleted={$deleted}\n");
    exit(0);
}

[$venue, $date, $period] = array_pad($args, 3, null);

if (! $venue || ! $date || ! $period) {
    fwrite(STDERR, "usage: php tests/manual/venue-slot-probe.php <venue> <date> <period>\n");
    exit(2);
}

$inquiry = Inquiry::query()->latest('inquiry_id')->first();

if (! $inquiry) {
    fwrite(STDERR, "No inquiry found to attach the probe reservation to.\n");
    exit(3);
}

$started = microtime(true);

$result = app(VenueOccupancyService::class)->guard($venue, $date, $period, function () use ($inquiry, $venue, $date, $period) {
    Reservation::create([
        'inquiry_id'  => $inquiry->inquiry_id,
        'patron_id'   => $inquiry->patron_id,
        'admin_id'    => $inquiry->admin_id,
        'venue'       => $venue,
        'date'        => $date,
        'time'        => $period === 'AM' ? '9am-1pm' : '4pm-8pm',
        'status'      => 'active',
        'form_data'   => ['period' => $period],
    ]);
});

$elapsed = (int) round((microtime(true) - $started) * 1000);
$rows = Reservation::where('venue', $venue)->whereDate('date', $date)->where('status', 'active')->count();

fwrite(STDOUT, sprintf(
    "pid=%d started=%.4f elapsed_ms=%d outcome=%s active_rows=%d cache_store=%s\n",
    getmypid(),
    $started,
    $elapsed,
    $result,
    $rows,
    cacheStoreName()
));
