<?php

namespace App\Support;

/**
 * The money rules shared by the payment screens.
 *
 * A receipt says *what kind* of payment it is ("full" or the configured
 * downpayment), while the reservation holds the agreed total. The amount a
 * receipt is worth is therefore derived from the two together, in one place,
 * so the approval endpoint and the payment log can never disagree.
 */
final class PaymentAmount
{
    /**
     * The configured downpayment percentage (default 25).
     */
    public static function downpaymentPercent(): int
    {
        return (int) config('payments.downpayment_percent', 25);
    }

    /**
     * Amount a payment of this type represents against a reservation total.
     *
     * Returns null when the type is unknown or the total is not recorded yet,
     * which keeps the payment unvalued instead of guessing.
     */
    public static function forType(?string $type, ?string $total): ?string
    {
        if ($total === null) {
            return null;
        }

        return match ($type) {
            'full' => bcadd($total, '0', 2),
            'half' => self::downpayment($total),
            default => null,
        };
    }

    /**
     * The configured downpayment for a total, rounded half-up to centavos.
     */
    public static function downpayment(string $total): string
    {
        $totalCentavos = bcmul(bcadd($total, '0', 2), '100', 0);
        $numerator = bcmul($totalCentavos, (string) self::downpaymentPercent(), 0);
        $downpaymentCentavos = bcdiv($numerator, '100', 0);

        if (bccomp(bcmod($numerator, '100'), '50', 0) >= 0) {
            $downpaymentCentavos = bcadd($downpaymentCentavos, '1', 0);
        }

        return bcdiv($downpaymentCentavos, '100', 2);
    }
}
