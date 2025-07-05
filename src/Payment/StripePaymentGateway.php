<?php

declare(strict_types=1);

namespace App\Payment;

use App\Entity\Order;
use Stripe\StripeClient;

/**
 * Real payment gateway backed by Stripe Checkout. Creates a hosted checkout
 * session for the order and returns its URL. Runs against Stripe test keys.
 */
class StripePaymentGateway implements PaymentGatewayInterface
{
    public function __construct(private readonly string $secretKey)
    {
    }

    public function initiate(Order $order, string $returnUrl, string $cancelUrl): PaymentInitiation
    {
        if ($this->secretKey === '') {
            throw new \RuntimeException('Stripe secret key is not configured (STRIPE_SECRET_KEY).');
        }

        $stripe = new StripeClient($this->secretKey);

        $lineItems = [];
        foreach ($order->getItems() as $item) {
            $lineItems[] = [
                'quantity' => $item->getQuantity(),
                'price_data' => [
                    'currency' => strtolower($item->getUnitPrice()->currency()),
                    'unit_amount' => $item->getUnitPrice()->amount(),
                    'product_data' => ['name' => $item->getProductName()],
                ],
            ];
        }

        $session = $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'line_items' => $lineItems,
            'customer_email' => $order->getCustomerEmail(),
            'client_reference_id' => $order->getReference(),
            'success_url' => $returnUrl.(str_contains($returnUrl, '?') ? '&' : '?').'session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cancelUrl,
        ]);

        return new PaymentInitiation($session->url, $session->id);
    }

    /**
     * Reconciles a checkout session on return from Stripe: returns true if the
     * session has been paid.
     */
    public function isSessionPaid(string $sessionId): bool
    {
        $stripe = new StripeClient($this->secretKey);
        $session = $stripe->checkout->sessions->retrieve($sessionId);

        return $session->payment_status === 'paid';
    }
}
