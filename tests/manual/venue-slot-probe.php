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
 * Refuses to run unless APP_ENV is "local" AND the database host is localhost or
 * 127.0.0.1, so it cannot be pointed at a shared database by accident.
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

    fwrite(STDOUT, "environment check: APP_ENV={$env} db_connection={$connection} db_host={$host}\n");
}

$args = array_slice($argv, 1);

if (($args[0] ?? '') === '--check') {
    assertLocalEnvironment();
    exit(0);
}

assertLocalEnvironment();

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
    "pid=%d started=%.4f elapsed_ms=%d outcome=%s active_rows=%d\n",
    getmypid(),
    $started,
    $elapsed,
    $result,
    $rows
));
