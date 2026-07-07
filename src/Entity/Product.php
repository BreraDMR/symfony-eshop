<?php

declare(strict_types=1);

namespace App\Entity;

use App\Inventory\InsufficientStockException;
use App\Repository\ProductRepository;
use App\ValueObject\Money;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'products')]
#[ORM\HasLifecycleCallbacks]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $name;

    #[ORM\Column(length: 200, unique: true)]
    private string $slug;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'price_')]
    private Money $price;

    #[ORM\Column]
    private int $stock = 0;

    #[ORM\Column]
    private bool $active = true;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageFilename = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    private Category $category;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct(string $name, string $slug, Money $price, Category $category)
    {
        $this->name = $name;
        $this->slug = $slug;
        $this->price = $price;
        $this->category = $category;
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getPrice(): Money
    {
        return $this->price;
    }

    public function setPrice(Money $price): void
    {
        $this->price = $price;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): void
    {
        if ($stock < 0) {
            throw new \InvalidArgumentException('Stock cannot be negative.');
        }

        $this->stock = $stock;
    }

    public function isInStock(): bool
    {
        return $this->stock > 0;
    }

    /**
     * Take a quantity out of stock, e.g. when an order is paid.
     *
     * @throws InsufficientStockException when fewer units are available
     */
    public function reduceStock(int $quantity): void
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Quantity to reduce must be at least 1.');
        }

        if ($quantity > $this->stock) {
            throw InsufficientStockException::forProduct($this->name, $this->stock, $quantity);
        }

        $this->stock -= $quantity;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getImageFilename(): ?string
    {
        return $this->imageFilename;
    }

    public function setImageFilename(?string $imageFilename): void
    {
        $this->imageFilename = $imageFilename;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function setCategory(Category $category): void
    {
        $this->category = $category;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
