<?php

declare(strict_types=1);

namespace App\Inventory;

/**
 * Raised when an order asks for more units than a product has in stock,
 * e.g. two customers race for the last item.
 */
final class InsufficientStockException extends \RuntimeException
{
    public static function forProduct(string $productName, int $available, int $requested): self
    {
        return new self(sprintf(
            'Not enough stock for "%s": %d requested but only %d available.',
            $productName,
            $requested,
            $available,
        ));
    }
}
