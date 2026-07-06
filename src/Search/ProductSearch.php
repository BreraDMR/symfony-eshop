<?php

declare(strict_types=1);

namespace App\Search;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Psr\Log\LoggerInterface;

/**
 * Full-text product search. The gateway (Elasticsearch) only ranks the
 * matches; the entities themselves are loaded from the database so the
 * storefront renders the same objects as the rest of the catalog. If the
 * search engine is unreachable it degrades to a database LIKE search.
 */
class ProductSearch
{
    public function __construct(
        private readonly ProductSearchGateway $gateway,
        private readonly ProductRepository $products,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return Product[]
     */
    public function search(string $term): array
    {
        $term = trim($term);

        if ($term === '') {
            return [];
        }

        try {
            return $this->products->findActiveByIds($this->gateway->matchingIds($term));
        } catch (\Throwable $e) {
            $this->logger->warning('Product search fell back to the database: '.$e->getMessage());

            return $this->products->searchByName($term);
        }
    }
}
