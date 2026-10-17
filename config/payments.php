<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Downpayment percentage
    |--------------------------------------------------------------------------
    |
    | The share of the reservation total that the "half" receipt represents.
    | The patron form labels it as the downpayment, and the payment log uses it
    | to decide when "Downpayment received" turns into "Fully paid".
    |
    */

    'downpayment_percent' => (int) env('PAYMENT_DOWNPAYMENT_PERCENT', 25),

];
