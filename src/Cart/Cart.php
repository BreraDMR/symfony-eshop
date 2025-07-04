<?php

declare(strict_types=1);

namespace App\Cart;

use App\ValueObject\Money;

/**
 * Read model of the shopping cart. Built by the CartService from the
 * quantities kept in the session and the current product data.
 */
final class Cart
{
    /**
     * @param CartItem[] $items
     */
    public function __construct(
        private readonly array $items,
        private readonly string $currency = 'CZK',
    ) {
    }

    /**
     * @return CartItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getTotal(): Money
    {
        $total = new Money(0, $this->currency);

        foreach ($this->items as $item) {
            $total = $total->add($item->getSubtotal());
        }

        return $total;
    }

    public function getTotalQuantity(): int
    {
        return array_sum(array_map(static fn (CartItem $item) => $item->getQuantity(), $this->items));
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }
}
