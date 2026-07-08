<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Maximum Inquiries Per Day
    |--------------------------------------------------------------------------
    |
    | This value determines the maximum number of inquiries (bookings) allowed
    | per calendar date before the system auto-tags it as "Full".
    |
    */

    'max_inquiries' => env('AVAILABILITY_MAX_INQUIRIES', 4),

];
