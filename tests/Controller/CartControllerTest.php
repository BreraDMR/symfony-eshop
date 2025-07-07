<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\ValueObject\Money;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CartControllerTest extends WebTestCase
{
    public function testUpdateReturnsRecomputedTotalsAsJson(): void
    {
        $client = static::createClient();
        [$slug, $id] = $this->createProductInCart($client, 2);

        $token = $this->updateToken($client);
        $client->request(
            'POST',
            '/cart/update/'.$id,
            ['quantity' => 3, '_token' => $token],
            [],
            ['HTTP_ACCEPT' => 'application/json'],
        );

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/json');

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame(3, $data['quantity']);
        self::assertSame(3, $data['itemCount']);
        self::assertFalse($data['empty']);
        // A single-line cart: the line subtotal equals the running total.
        self::assertSame($data['lineSubtotal'], $data['total']);
        self::assertNotEmpty($data['total']);
        self::assertNotSame($slug, $data['total']); // sanity: it's a money string, not the slug
    }

    public function testUpdateClampsQuantityToAvailableStock(): void
    {
        $client = static::createClient();
        [, $id] = $this->createProductInCart($client, 1, stock: 5);

        $token = $this->updateToken($client);
        $client->request(
            'POST',
            '/cart/update/'.$id,
            ['quantity' => 99, '_token' => $token],
            [],
            ['HTTP_ACCEPT' => 'application/json'],
        );

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame(5, $data['quantity']);
    }

    public function testUpdateWithoutJsonStillRedirects(): void
    {
        $client = static::createClient();
        [, $id] = $this->createProductInCart($client, 1);

        $token = $this->updateToken($client);
        $client->request('POST', '/cart/update/'.$id, ['quantity' => 2, '_token' => $token]);

        self::assertResponseRedirects('/cart');
    }

    private function updateToken(object $client): string
    {
        $crawler = $client->request('GET', '/cart');

        return (string) $crawler->filter('form.js-cart-update input[name="_token"]')->attr('value');
    }

    /**
     * Creates an active product and puts the given quantity in the session cart
     * through the real add-to-cart form, so the CSRF tokens match the session.
     *
     * @return array{0: string, 1: int}
     */
    private function createProductInCart(object $client, int $quantity, int $stock = 10): array
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $suffix = uniqid();
        $slug = 'cart-product-'.$suffix;

        $category = new Category('Coffee', 'cart-cat-'.$suffix);
        $product = new Product('Cart Product '.$suffix, $slug, new Money(19900), $category);
        $product->setStock($stock);

        $em->persist($category);
        $em->persist($product);
        $em->flush();

        $client->request('GET', '/product/'.$slug);
        $client->submitForm('Add to cart', ['quantity' => $quantity]);

        return [$slug, (int) $product->getId()];
    }
}
