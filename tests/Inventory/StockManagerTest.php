<?php

declare(strict_types=1);

namespace App\Tests\Inventory;

use App\Entity\Category;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Inventory\InsufficientStockException;
use App\Inventory\StockManager;
use App\ValueObject\Money;
use PHPUnit\Framework\TestCase;

final class StockManagerTest extends TestCase
{
    public function testReduceForOrderTakesQuantityFromEachProduct(): void
    {
        $category = new Category('Coffee', 'coffee');
        $coffee = $this->product('Beans', 'beans', 10, $category);
        $tea = $this->product('Leaves', 'leaves', 5, $category);

        $order = new Order('REF-1', 'jan@example.com', 'Jan', 'Praha');
        $order->addItem(new OrderItem($coffee, 3));
        $order->addItem(new OrderItem($tea, 5));

        (new StockManager())->reduceForOrder($order);

        self::assertSame(7, $coffee->getStock());
        self::assertSame(0, $tea->getStock());
    }

    public function testReduceForOrderThrowsWhenStockRanOut(): void
    {
        $category = new Category('Coffee', 'coffee');
        $coffee = $this->product('Beans', 'beans', 2, $category);

        $order = new Order('REF-2', 'jan@example.com', 'Jan', 'Praha');
        $order->addItem(new OrderItem($coffee, 3));

        $this->expectException(InsufficientStockException::class);

        (new StockManager())->reduceForOrder($order);
    }

    private function product(string $name, string $slug, int $stock, Category $category): Product
    {
        $product = new Product($name, $slug, new Money(20000), $category);
        $product->setStock($stock);

        return $product;
    }
}
