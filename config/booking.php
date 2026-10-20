<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Booking time slots
    |--------------------------------------------------------------------------
    |
    | The single source of truth for the reservation form on the patron and
    | admin sides. Every label states am or pm explicitly so the period is
    | always derivable from the selected slot. Stored bookings keep whatever
    | text they already have; periodFromTimeText() still resolves old values.
    |
    */

    'time_slots' => [
        'AM' => [
            '9am-1pm',
            '10am-2pm',
            '11am-3pm',
        ],
        'PM' => [
            '4pm-8pm',
            '5pm-9pm',
            '6pm-10pm',
        ],
    ],

];
