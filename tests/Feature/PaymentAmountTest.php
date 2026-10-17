<?php

use App\Support\PaymentAmount;

/*
| The downpayment percentage lives in config/payments.php. Default is 25 and
| the env override changes the derived amount without touching any code.
*/

it('derives the downpayment from the configured percentage', function () {
    config(['payments.downpayment_percent' => 25]);

    expect(PaymentAmount::downpaymentPercent())->toBe(25)
        ->and(PaymentAmount::forType('half', '40000.00'))->toBe('10000.00')
        ->and(PaymentAmount::forType('full', '40000.00'))->toBe('40000.00');
});

it('honours a different configured percentage', function () {
    config(['payments.downpayment_percent' => 30]);

    expect(PaymentAmount::downpaymentPercent())->toBe(30)
        ->and(PaymentAmount::forType('half', '40000.00'))->toBe('12000.00');
});
