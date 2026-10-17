<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Per-day event cap
    |--------------------------------------------------------------------------
    |
    | A day's count is the number of ACTIVE reservations on that date across
    | all venues and periods. Pending inquiries do not count. A reservation
    | with an undetermined period still counts as one event.
    |
    | The five calendar states derive from this cap (cap = 4 by default):
    |
    |   Available  0 to cap - 3   (0/4 to 1/4)
    |   Half       cap - 2        (2/4, displayed as "Limited")
    |   Nearly     cap - 1        (3/4, displayed as "Nearly Full")
    |   Full       cap and above  (4/4)
    |   Closed     admin override, which always wins
    |
    | The value is volatile: set AVAILABILITY_MAX_EVENTS_PER_DAY to change it.
    |
    */

    'max_events_per_day' => (int) env('AVAILABILITY_MAX_EVENTS_PER_DAY', 4),

    /*
    | Wording for the calendar states. "labels" drives the legend, the day cue
    | and the accessible day labels on both calendars.
    */

    'labels' => [
        'Available' => 'available',
        'Half' => 'limited',
        'Nearly' => 'nearly full',
        'Full' => 'full',
        'Closed' => 'closed',
    ],

    'cue_words' => [
        'Full' => 'Full',
        'Closed' => 'Closed',
    ],

];
