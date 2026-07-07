<?php

declare(strict_types=1);

namespace App\Inventory;

use App\Entity\Order;

/**
 * Applies stock movements for an order. Stock is only taken once payment
 * succeeds, so abandoned checkouts never hold inventory.
 */
class StockManager
{
    /**
     * @throws InsufficientStockException if any line can no longer be fulfilled
     */
    public function reduceForOrder(Order $order): void
    {
        foreach ($order->getItems() as $item) {
            $product = $item->getProduct();

            // The product may have been deleted after the order was placed;
            // the line keeps its price snapshot, but there is nothing to adjust.
            if ($product === null) {
                continue;
            }

            $product->reduceStock($item->getQuantity());
        }
    }
}
