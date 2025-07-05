<?php

declare(strict_types=1);

namespace App\Order;

use App\Cart\Cart;
use App\Dto\CheckoutDetails;
use App\Entity\Order;
use App\Entity\OrderItem;

/**
 * Turns a cart plus the customer details into a persistable Order,
 * snapshotting product names and prices as order lines.
 */
class OrderFactory
{
    public function __construct(private readonly OrderReferenceGenerator $referenceGenerator)
    {
    }

    public function createFromCart(Cart $cart, CheckoutDetails $details): Order
    {
        if ($cart->isEmpty()) {
            throw new \DomainException('Cannot create an order from an empty cart.');
        }

        $order = new Order(
            $this->referenceGenerator->generate(),
            $details->customerEmail,
            $details->customerName,
            $details->shippingAddress,
        );

        foreach ($cart->getItems() as $cartItem) {
            $order->addItem(new OrderItem($cartItem->getProduct(), $cartItem->getQuantity()));
        }

        return $order;
    }
}
