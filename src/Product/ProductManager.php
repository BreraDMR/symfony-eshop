<?php

declare(strict_types=1);

namespace App\Product;

use App\Dto\ProductFormData;
use App\Entity\Product;
use App\ValueObject\Money;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Builds and updates Product entities from the submitted form data, keeping the
 * mapping and slug handling out of the controller.
 */
class ProductManager
{
    public function __construct(private readonly SluggerInterface $slugger)
    {
    }

    public function create(ProductFormData $data): Product
    {
        $product = new Product(
            $data->name,
            $this->resolveSlug($data),
            Money::fromMajorUnits((float) $data->price),
            $data->category,
        );

        $this->applyOptionalFields($product, $data);

        return $product;
    }

    public function update(Product $product, ProductFormData $data): void
    {
        $product->setName($data->name);
        $product->setSlug($this->resolveSlug($data));
        $product->setPrice(Money::fromMajorUnits((float) $data->price));
        $product->setCategory($data->category);

        $this->applyOptionalFields($product, $data);
    }

    private function applyOptionalFields(Product $product, ProductFormData $data): void
    {
        $product->setDescription($data->description);
        $product->setStock($data->stock);
        $product->setActive($data->active);
    }

    private function resolveSlug(ProductFormData $data): string
    {
        $slug = trim($data->slug) !== '' ? $data->slug : $data->name;

        return $this->slugger->slug($slug)->lower()->toString();
    }
}
