<?php

declare(strict_types=1);

namespace App\Message;

/**
 * Dispatched once an order has been paid; carries only the order id so the
 * handler reads a fresh entity when it runs on the worker.
 */
final readonly class OrderConfirmationEmail
{
    public function __construct(public int $orderId)
    {
    }
}
