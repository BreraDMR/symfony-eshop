<?php

declare(strict_types=1);

namespace App\Catalog;

use App\Entity\Category;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Read-through cache over the product catalog. Storefront listings and product
 * pages are served from Redis and refreshed as a group through the "catalog"
 * tag whenever a product changes in the admin.
 */
class ProductCatalog
{
    public const CACHE_TAG = 'catalog';

    public function __construct(
        private readonly ProductRepository $products,
        private readonly TagAwareCacheInterface $catalogCache,
    ) {
    }

    /**
     * @return Product[]
     */
    public function activeProducts(?Category $category = null): array
    {
        $key = 'catalog.products.'.($category?->getSlug() ?? 'all');

        return $this->catalogCache->get($key, function (ItemInterface $item) use ($category): array {
            $item->tag(self::CACHE_TAG);

            return $this->products->findActive($category);
        });
    }

    public function activeProductBySlug(string $slug): ?Product
    {
        $key = 'catalog.product.'.$slug;

        return $this->catalogCache->get($key, function (ItemInterface $item) use ($slug): ?Product {
            $item->tag(self::CACHE_TAG);

            return $this->products->findOneActiveBySlug($slug);
        });
    }

    /**
     * Drops every cached catalog entry. Called after a product is created,
     * updated or deleted so the storefront never serves stale data.
     */
    public function invalidate(): void
    {
        $this->catalogCache->invalidateTags([self::CACHE_TAG]);
    }
}
