<?php

declare(strict_types=1);

namespace App\Api;

use App\Entity\Product;
use App\Twig\MoneyExtension;

/**
 * Turns a Product entity into the plain array shape returned by the JSON API
 * and consumed by the React storefront. Prices are formatted with the same
 * helper the server-rendered pages use, so both frontends agree.
 */
final class ProductApiNormalizer
{
    public function __construct(private readonly MoneyExtension $money)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function normalize(Product $product): array
    {
        $price = $product->getPrice();

        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'slug' => $product->getSlug(),
            'description' => $product->getDescription(),
            'category' => $product->getCategory()->getName(),
            'price' => [
                'amount' => $price->amount(),
                'currency' => $price->currency(),
                'formatted' => $this->money->format($price),
            ],
            'image' => $product->getImageFilename() !== null
                ? '/images/products/'.$product->getImageFilename()
                : null,
            'inStock' => $product->isInStock(),
        ];
    }
}
