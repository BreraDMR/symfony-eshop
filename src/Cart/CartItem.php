<?php

declare(strict_types=1);

namespace App\Cart;

use App\Entity\Product;
use App\ValueObject\Money;

/**
 * A single line in the cart: a product and how many of it.
 */
final class CartItem
{
    public function __construct(
        private readonly Product $product,
        private readonly int $quantity,
    ) {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Cart item quantity must be at least 1.');
        }
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getSubtotal(): Money
    {
        return $this->product->getPrice()->multiply($this->quantity);
    }
}
