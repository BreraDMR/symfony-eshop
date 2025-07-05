<?php

declare(strict_types=1);

namespace App\Payment;

use App\Entity\Order;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * A gateway that does not talk to any external service. It sends the customer
 * to a local page that simulates the hosted payment screen, so the whole
 * checkout flow can be demonstrated and tested without API keys.
 */
class FakePaymentGateway implements PaymentGatewayInterface
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function initiate(Order $order, string $returnUrl, string $cancelUrl): PaymentInitiation
    {
        $redirectUrl = $this->urlGenerator->generate(
            'app_checkout_simulate',
            ['reference' => $order->getReference()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        return new PaymentInitiation($redirectUrl, 'FAKE-'.$order->getReference());
    }
}
