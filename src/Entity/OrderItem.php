<?php

declare(strict_types=1);

namespace App\Entity;

use App\ValueObject\Money;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'order_items')]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private Order $order;

    /**
     * Kept as a soft reference: if the product is later deleted we still
     * keep the order line intact thanks to the name/price snapshot below.
     */
    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Product $product;

    #[ORM\Column(length: 180)]
    private string $productName;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'unit_price_')]
    private Money $unitPrice;

    #[ORM\Column]
    private int $quantity;

    public function __construct(Product $product, int $quantity)
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Order item quantity must be at least 1.');
        }

        $this->product = $product;
        $this->productName = $product->getName();
        $this->unitPrice = $product->getPrice();
        $this->quantity = $quantity;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function setOrder(Order $order): void
    {
        $this->order = $order;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getUnitPrice(): Money
    {
        return $this->unitPrice;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getSubtotal(): Money
    {
        return $this->unitPrice->multiply($this->quantity);
    }
}
