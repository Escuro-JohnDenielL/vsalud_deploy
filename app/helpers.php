<?php

use App\Models\PaymentSetting;

if (! function_exists('payment_setting')) {
    /**
     * Get a payment setting value by key.
     */
    function payment_setting(string $key, mixed $default = null): mixed
    {
        return PaymentSetting::get($key, $default);
    }
}
