<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Reminder cutoff
    |--------------------------------------------------------------------------
    |
    | Reminder commands ignore reservations created before this date. It should
    | be set to the deployment date so historical data does not suddenly produce
    | a burst of mail. Set REMINDERS_SINCE explicitly in production.
    |
    */

    'since' => env('REMINDERS_SINCE', '2026-10-18'),

    /*
    |--------------------------------------------------------------------------
    | Lazy web trigger
    |--------------------------------------------------------------------------
    |
    | Production does not start a scheduler process. When this is true, the
    | reminder commands are also attempted (at most once per hour) from the
    | calendar endpoint and the admin dashboard. Default: off.
    |
    */

    'lazy_enabled' => env('REMINDERS_LAZY_ENABLED', false),

];
