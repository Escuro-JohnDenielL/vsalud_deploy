<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Calendar slot scale
    |--------------------------------------------------------------------------
    |
    | One slot is one venue for one period (AM or PM), so a date has
    | (venues x periods_per_venue) slots. The venue list is read at runtime from
    | the reservation form field and is never hardcoded here.
    |
    | Booked slots on a date map to the five calendar states:
    |
    |   Available  0 to available_max
    |   Half       available_max + 1 to half_max
    |   Nearly     half_max + 1 to (total - 1)
    |   Full       every slot occupied
    |   Closed     set by an admin override only, which always wins
    |
    | These values and the words below drive the day cue, the legend and the
    | accessible labels on both the patron and the admin calendar.
    |
    */

    'periods_per_venue' => (int) env('AVAILABILITY_PERIODS_PER_VENUE', 2),
    'available_max' => (int) env('AVAILABILITY_AVAILABLE_MAX', 2),
    'half_max' => (int) env('AVAILABILITY_HALF_MAX', 4),

    /*
    | Wording for the calendar states. "labels" goes into the accessible day
    | labels; "cue_words" is used by the two states that show a word instead of
    | a booked count.
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

    /*
    |--------------------------------------------------------------------------
    | Legacy: maximum inquiries per day
    |--------------------------------------------------------------------------
    |
    | Kept so an existing environment variable does not break, but nothing reads
    | it any more. The calendar now counts occupied venue-period slots; see the
    | slot scale above.
    |
    */

    'max_inquiries' => env('AVAILABILITY_MAX_INQUIRIES', 4),

];
