<?php

declare(strict_types=1);

namespace App\Payment;

/**
 * Picks the active payment gateway based on configuration. Defaults to the
 * fake gateway so the application runs without any external credentials.
 */
class PaymentGatewayFactory
{
    public function __construct(
        private readonly FakePaymentGateway $fake,
        private readonly StripePaymentGateway $stripe,
        private readonly string $gateway,
    ) {
    }

    public function create(): PaymentGatewayInterface
    {
        return strtolower($this->gateway) === 'stripe' ? $this->stripe : $this->fake;
    }
}
