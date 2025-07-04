<?php

declare(strict_types=1);

namespace App\Cart;

use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Keeps the cart contents in the session as a simple map of
 * productId => quantity, and hydrates it into a Cart read model on demand.
 */
class CartService
{
    private const SESSION_KEY = 'cart';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ProductRepository $products,
    ) {
    }

    public function add(int $productId, int $quantity = 1): void
    {
        $storage = $this->getStorage();
        $current = $storage[$productId] ?? 0;

        $this->setQuantity($productId, $current + $quantity);
    }

    public function setQuantity(int $productId, int $quantity): void
    {
        $storage = $this->getStorage();

        if ($quantity <= 0) {
            unset($storage[$productId]);
        } else {
            $storage[$productId] = $quantity;
        }

        $this->save($storage);
    }

    public function remove(int $productId): void
    {
        $storage = $this->getStorage();
        unset($storage[$productId]);
        $this->save($storage);
    }

    public function clear(): void
    {
        $this->save([]);
    }

    public function getCart(): Cart
    {
        $storage = $this->getStorage();
        $items = [];

        foreach ($storage as $productId => $quantity) {
            $product = $this->products->find($productId);

            // Product may have been removed since it was added to the cart.
            if ($product === null) {
                continue;
            }

            $items[] = new CartItem($product, $quantity);
        }

        return new Cart($items);
    }

    public function getItemCount(): int
    {
        return array_sum($this->getStorage());
    }

    /**
     * @return array<int, int>
     */
    private function getStorage(): array
    {
        return $this->requestStack->getSession()->get(self::SESSION_KEY, []);
    }

    /**
     * @param array<int, int> $storage
     */
    private function save(array $storage): void
    {
        $this->requestStack->getSession()->set(self::SESSION_KEY, $storage);
    }
}
