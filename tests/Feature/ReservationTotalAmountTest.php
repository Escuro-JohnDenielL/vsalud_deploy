<?php

use App\Rules\ReservationTotalAmount;
use Illuminate\Support\Facades\Validator;

/*
| Reservation total rule. Pure validation, no database.
*/

function totalPasses(mixed $value): bool
{
    return Validator::make(
        ['total_amount' => $value],
        ['total_amount' => [new ReservationTotalAmount()]]
    )->passes();
}

it('accepts positive amounts with up to two decimals, commas ignored', function () {
    expect(totalPasses('1250.50'))->toBeTrue();
    expect(totalPasses('1,250.50'))->toBeTrue();
    expect(totalPasses(' 1250 '))->toBeTrue();
    expect(totalPasses('99999999.99'))->toBeTrue();
    expect(ReservationTotalAmount::normalize('1,250.50'))->toBe('1250.50');
});

it('rejects zero, negatives, letters, too many decimals and over the maximum', function () {
    expect(totalPasses('0'))->toBeFalse();
    expect(totalPasses('0.00'))->toBeFalse();
    expect(totalPasses('-5'))->toBeFalse();
    expect(totalPasses('abc'))->toBeFalse();
    expect(totalPasses('12.345'))->toBeFalse();
    expect(totalPasses('100000000'))->toBeFalse();
});
