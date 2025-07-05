<?php

declare(strict_types=1);

namespace App\Payment;

use App\Entity\Order;

/**
 * A payment gateway starts a payment for an order and tells us where to send
 * the customer next. Concrete gateways (Stripe, or the fake one used for local
 * development and tests) implement this contract, so the checkout flow does not
 * depend on any specific provider.
 */
interface PaymentGatewayInterface
{
    public function initiate(Order $order, string $returnUrl, string $cancelUrl): PaymentInitiation;
}
