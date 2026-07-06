<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Entity\Category;
use App\Entity\Product;
use App\ValueObject\Money;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ProductApiControllerTest extends WebTestCase
{
    public function testItListsProductsAsJson(): void
    {
        $client = static::createClient();
        [$slug] = $this->createProduct();

        $client->request('GET', '/api/products');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/json');

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $slugs = array_column($data['products'], 'slug');
        self::assertContains($slug, $slugs);
    }

    public function testItReturnsASingleProduct(): void
    {
        $client = static::createClient();
        [$slug, $name] = $this->createProduct();

        $client->request('GET', '/api/products/'.$slug);

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame($name, $data['product']['name']);
        self::assertSame('CZK', $data['product']['price']['currency']);
        self::assertArrayHasKey('formatted', $data['product']['price']);
    }

    public function testItReturns404ForAnUnknownProduct(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/products/does-not-exist');

        self::assertResponseStatusCodeSame(404);
    }

    public function testItListsCategories(): void
    {
        $client = static::createClient();
        $this->createProduct();

        $client->request('GET', '/api/categories');

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertNotEmpty($data['categories']);
        self::assertArrayHasKey('slug', $data['categories'][0]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function createProduct(): array
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $suffix = uniqid();
        $slug = 'api-product-'.$suffix;
        $name = 'API Product '.$suffix;

        $category = new Category('Coffee', 'api-cat-'.$suffix);
        $product = new Product($name, $slug, new Money(19900), $category);
        $product->setStock(5);

        $em->persist($category);
        $em->persist($product);
        $em->flush();

        return [$slug, $name];
    }
}
