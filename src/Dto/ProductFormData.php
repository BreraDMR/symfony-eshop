<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Category;
use App\Entity\Product;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Backs the admin product form. Keeping the form bound to a DTO rather than the
 * entity lets the Product keep a constructor that enforces its invariants, and
 * keeps validation concerns out of the entity.
 */
class ProductFormData
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 180)]
    public string $name = '';

    #[Assert\Length(max: 200)]
    public string $slug = '';

    public ?string $description = null;

    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    public ?float $price = null;

    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    public int $stock = 0;

    public bool $active = true;

    #[Assert\NotNull]
    public ?Category $category = null;

    public static function fromProduct(Product $product): self
    {
        $data = new self();
        $data->name = $product->getName();
        $data->slug = $product->getSlug();
        $data->description = $product->getDescription();
        $data->price = $product->getPrice()->toMajorUnits();
        $data->stock = $product->getStock();
        $data->active = $product->isActive();
        $data->category = $product->getCategory();

        return $data;
    }
}
