<?php

declare(strict_types=1);

namespace App\Tests\Cart;

use App\Cart\Cart;
use App\Cart\CartItem;
use App\Entity\Category;
use App\Entity\Product;
use App\ValueObject\Money;
use PHPUnit\Framework\TestCase;

final class CartTest extends TestCase
{
    public function testEmptyCart(): void
    {
        $cart = new Cart([]);

        self::assertTrue($cart->isEmpty());
        self::assertSame(0, $cart->getTotal()->amount());
        self::assertSame(0, $cart->getTotalQuantity());
    }

    public function testTotalsAcrossItems(): void
    {
        $category = new Category('Coffee', 'coffee');
        $itemA = new CartItem($this->product('A', 20000, $category), 2); // 400.00
        $itemB = new CartItem($this->product('B', 15000, $category), 1); // 150.00

        $cart = new Cart([$itemA, $itemB]);

        self::assertFalse($cart->isEmpty());
        self::assertSame(3, $cart->getTotalQuantity());
        self::assertSame(55000, $cart->getTotal()->amount());
    }

    public function testCartItemRejectsZeroQuantity(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new CartItem($this->product('A', 100, new Category('Coffee', 'coffee')), 0);
    }

    private function product(string $name, int $priceMinor, Category $category): Product
    {
        return new Product($name, strtolower($name), new Money($priceMinor), $category);
    }
}
