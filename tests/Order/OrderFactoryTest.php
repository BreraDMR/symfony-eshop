<?php

declare(strict_types=1);

namespace App\Tests\Order;

use App\Cart\Cart;
use App\Cart\CartItem;
use App\Dto\CheckoutDetails;
use App\Entity\Category;
use App\Entity\Product;
use App\Enum\OrderStatus;
use App\Order\OrderFactory;
use App\Order\OrderReferenceGenerator;
use App\ValueObject\Money;
use PHPUnit\Framework\TestCase;

final class OrderFactoryTest extends TestCase
{
    public function testCreateFromCartSnapshotsItemsAndTotal(): void
    {
        $factory = new OrderFactory(new OrderReferenceGenerator());
        $order = $factory->createFromCart($this->cart(), $this->details());

        self::assertCount(2, $order->getItems());
        self::assertSame(OrderStatus::Pending, $order->getStatus());
        self::assertSame('jan@example.com', $order->getCustomerEmail());
        // 2 x 200.00 + 1 x 150.00 = 550.00
        self::assertSame(55000, $order->getTotal()->amount());
    }

    public function testOrderItemKeepsPriceSnapshot(): void
    {
        $factory = new OrderFactory(new OrderReferenceGenerator());
        $order = $factory->createFromCart($this->cart(), $this->details());

        $firstItem = $order->getItems()->first();
        self::assertSame('A', $firstItem->getProductName());
        self::assertSame(20000, $firstItem->getUnitPrice()->amount());
    }

    public function testEmptyCartThrows(): void
    {
        $this->expectException(\DomainException::class);

        (new OrderFactory(new OrderReferenceGenerator()))
            ->createFromCart(new Cart([]), $this->details());
    }

    private function cart(): Cart
    {
        $category = new Category('Coffee', 'coffee');

        return new Cart([
            new CartItem(new Product('A', 'a', new Money(20000), $category), 2),
            new CartItem(new Product('B', 'b', new Money(15000), $category), 1),
        ]);
    }

    private function details(): CheckoutDetails
    {
        $details = new CheckoutDetails();
        $details->customerName = 'Jan Novak';
        $details->customerEmail = 'jan@example.com';
        $details->shippingAddress = 'Namesti Miru 1, Praha';

        return $details;
    }
}
