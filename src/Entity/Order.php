<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\OrderStatus;
use App\Repository\OrderRepository;
use App\ValueObject\Money;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'orders')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, unique: true)]
    private string $reference;

    #[ORM\Column(length: 180)]
    private string $customerEmail;

    #[ORM\Column(length: 180)]
    private string $customerName;

    #[ORM\Column(type: 'text')]
    private string $shippingAddress;

    #[ORM\Column(enumType: OrderStatus::class)]
    private OrderStatus $status = OrderStatus::Pending;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'total_')]
    private Money $total;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $paymentReference = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    /**
     * @var Collection<int, OrderItem>
     */
    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderItem::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $items;

    public function __construct(string $reference, string $customerEmail, string $customerName, string $shippingAddress)
    {
        $this->reference = $reference;
        $this->customerEmail = $customerEmail;
        $this->customerName = $customerName;
        $this->shippingAddress = $shippingAddress;
        $this->total = new Money(0);
        $this->createdAt = new \DateTimeImmutable();
        $this->items = new ArrayCollection();
    }

    public function addItem(OrderItem $item): void
    {
        $item->setOrder($this);
        $this->items->add($item);
        $this->recalculateTotal();
    }

    public function recalculateTotal(): void
    {
        $total = new Money(0);

        foreach ($this->items as $item) {
            $total = $total->add($item->getSubtotal());
        }

        $this->total = $total;
    }

    public function markAsPaid(string $paymentReference): void
    {
        $this->status = OrderStatus::Paid;
        $this->paymentReference = $paymentReference;
        $this->paidAt = new \DateTimeImmutable();
    }

    public function markAsFailed(): void
    {
        $this->status = OrderStatus::Failed;
    }

    public function cancel(): void
    {
        $this->status = OrderStatus::Cancelled;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function getShippingAddress(): string
    {
        return $this->shippingAddress;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function getTotal(): Money
    {
        return $this->total;
    }

    public function getPaymentReference(): ?string
    {
        return $this->paymentReference;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }
}
