<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Check for expired waitlist notifications every minute
Schedule::command('app:check-waitlist-expirations')->everyMinute();

// Send event reminders for reservations 7 days away
Schedule::command('app:send-event-reminders')->daily();

// Send payment reminders for reservations 3+ days old with pending payment
Schedule::command('app:send-payment-reminders')->daily();
