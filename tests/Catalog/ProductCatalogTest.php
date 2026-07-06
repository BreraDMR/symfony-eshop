<?php

declare(strict_types=1);

namespace App\Tests\Catalog;

use App\Catalog\ProductCatalog;
use App\Entity\Category;
use App\Entity\Product;
use App\ValueObject\Money;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ProductCatalogTest extends KernelTestCase
{
    public function testReadsAreCachedUntilInvalidated(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $catalog = $container->get(ProductCatalog::class);
        $em = $container->get(EntityManagerInterface::class);

        $slug = 'cache-test-'.uniqid();
        $category = new Category('Coffee', 'cat-'.uniqid());
        $product = new Product('Original name', $slug, new Money(10000), $category);
        $product->setStock(3);
        $em->persist($category);
        $em->persist($product);
        $em->flush();

        // First read primes the cache.
        self::assertSame('Original name', $catalog->activeProductBySlug($slug)?->getName());

        // Change the product behind the cache's back.
        $product->setName('Updated name');
        $em->flush();

        // The cached copy is still served.
        self::assertSame('Original name', $catalog->activeProductBySlug($slug)?->getName());

        // After invalidation the fresh value comes through.
        $catalog->invalidate();
        self::assertSame('Updated name', $catalog->activeProductBySlug($slug)?->getName());

        $em->remove($product);
        $em->remove($category);
        $em->flush();
    }
}
