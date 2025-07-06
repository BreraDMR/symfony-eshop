<?php

declare(strict_types=1);

namespace App\Search;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

/**
 * Keeps the search index in step with the catalog by reacting to Doctrine
 * lifecycle events on Product. Indexing failures are logged but never bubble
 * up, so a search outage cannot break catalog writes. Disabled in tests.
 */
#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: Product::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: Product::class)]
#[AsEntityListener(event: Events::preRemove, method: 'preRemove', entity: Product::class)]
#[AsEntityListener(event: Events::postRemove, method: 'postRemove', entity: Product::class)]
final class ProductSearchSubscriber
{
    /**
     * Product ids captured in preRemove, keyed by object id, because Doctrine
     * has already cleared the identifier by the time postRemove runs.
     *
     * @var array<int, int>
     */
    private array $pendingRemovals = [];

    public function __construct(
        private readonly ProductIndexer $indexer,
        private readonly LoggerInterface $logger,
        private readonly bool $enabled,
    ) {
    }

    public function postPersist(Product $product): void
    {
        $this->reindex($product);
    }

    public function postUpdate(Product $product): void
    {
        $this->reindex($product);
    }

    public function preRemove(Product $product): void
    {
        $this->pendingRemovals[spl_object_id($product)] = $product->getId();
    }

    public function postRemove(Product $product): void
    {
        $key = spl_object_id($product);
        $id = $this->pendingRemovals[$key] ?? null;
        unset($this->pendingRemovals[$key]);

        if (!$this->enabled || $id === null) {
            return;
        }

        try {
            $this->indexer->removeById((string) $id);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to remove product from search index: '.$e->getMessage(), [
                'product' => $id,
                'exception' => $e,
            ]);
        }
    }

    private function reindex(Product $product): void
    {
        if (!$this->enabled) {
            return;
        }

        try {
            $this->indexer->index($product);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to index product for search', [
                'product' => $product->getId(),
                'exception' => $e,
            ]);
        }
    }
}
