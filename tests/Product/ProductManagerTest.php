<?php

declare(strict_types=1);

namespace App\Tests\Product;

use App\Dto\ProductFormData;
use App\Entity\Category;
use App\Entity\Product;
use App\Product\ImageUploader;
use App\Product\ProductManager;
use App\ValueObject\Money;
use PHPUnit\Framework\TestCase;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class ProductManagerTest extends TestCase
{
    private function manager(): ProductManager
    {
        $slugger = new AsciiSlugger();

        return new ProductManager($slugger, new ImageUploader(sys_get_temp_dir(), $slugger));
    }

    public function testCreateGeneratesSlugFromNameWhenEmpty(): void
    {
        $manager = $this->manager();
        $product = $manager->create($this->data('Ethiopia Yirgacheffe 250g', ''));

        self::assertSame('ethiopia-yirgacheffe-250g', $product->getSlug());
        self::assertSame(28900, $product->getPrice()->amount());
        self::assertSame(15, $product->getStock());
    }

    public function testCreateKeepsExplicitSlug(): void
    {
        $manager = $this->manager();
        $product = $manager->create($this->data('Some Name', 'custom-slug'));

        self::assertSame('custom-slug', $product->getSlug());
    }

    public function testUpdateAppliesNewValues(): void
    {
        $manager = $this->manager();
        $category = new Category('Coffee', 'coffee');
        $product = new Product('Old', 'old', new Money(100), $category);

        $manager->update($product, $this->data('New Name', '', $category));

        self::assertSame('New Name', $product->getName());
        self::assertSame('new-name', $product->getSlug());
        self::assertSame(28900, $product->getPrice()->amount());
    }

    private function data(string $name, string $slug, ?Category $category = null): ProductFormData
    {
        $data = new ProductFormData();
        $data->name = $name;
        $data->slug = $slug;
        $data->price = 289.0;
        $data->stock = 15;
        $data->category = $category ?? new Category('Coffee', 'coffee');

        return $data;
    }
}
