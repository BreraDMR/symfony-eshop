<?php

declare(strict_types=1);

namespace App\Payment;

/**
 * The result of starting a payment: where to send the customer next and the
 * provider-side reference we can use to reconcile the payment later.
 */
final class PaymentInitiation
{
    public function __construct(
        public readonly string $redirectUrl,
        public readonly string $reference,
    ) {
    }
}
