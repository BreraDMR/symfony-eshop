<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Enum\OrderStatus;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\ValueObject\Money;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CheckoutControllerTest extends WebTestCase
{
    public function testPaidCheckoutReducesStockAndMarksOrderPaid(): void
    {
        $client = static::createClient();
        [$slug, $reference] = $this->buyProduct($client, quantity: 2, stock: 5);

        // The order is settled and its two units are gone from stock.
        $order = static::getContainer()->get(OrderRepository::class)->findOneByReference($reference);
        self::assertNotNull($order);
        self::assertSame(OrderStatus::Paid, $order->getStatus());

        $product = static::getContainer()->get(ProductRepository::class)->findOneBy(['slug' => $slug]);
        self::assertNotNull($product);
        self::assertSame(3, $product->getStock());
    }

    /**
     * Runs a product through the whole storefront checkout with the fake
     * gateway and returns its slug plus the resulting order reference.
     *
     * @return array{0: string, 1: string}
     */
    private function buyProduct(object $client, int $quantity, int $stock): array
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $suffix = uniqid();
        $slug = 'checkout-product-'.$suffix;

        $category = new Category('Coffee', 'checkout-cat-'.$suffix);
        $product = new Product('Checkout Product '.$suffix, $slug, new Money(19900), $category);
        $product->setStock($stock);

        $em->persist($category);
        $em->persist($product);
        $em->flush();

        // Add to cart through the real form so the session and CSRF line up.
        $client->request('GET', '/product/'.$slug);
        $client->submitForm('Add to cart', ['quantity' => $quantity]);

        // Fill in the checkout details; the fake gateway redirects to its
        // simulated payment page.
        $client->request('GET', '/checkout');
        $client->submitForm('Place order & pay', [
            'checkout[customerName]' => 'Jan Novak',
            'checkout[customerEmail]' => 'jan@example.com',
            'checkout[shippingAddress]' => 'Namesti Miru 1, Praha',
        ]);
        $client->followRedirect();

        // "Pay now" confirms payment and settles the order.
        $client->submitForm('Pay now');

        $reference = static::getContainer()->get(OrderRepository::class)
            ->findOneBy([], ['id' => 'DESC'])
            ->getReference();

        return [$slug, $reference];
    }
}
