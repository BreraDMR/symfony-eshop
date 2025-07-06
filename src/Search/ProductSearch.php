<?php

declare(strict_types=1);

namespace App\Search;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Elastic\Elasticsearch\Client;
use Psr\Log\LoggerInterface;

/**
 * Full-text product search backed by Elasticsearch. Elasticsearch only ranks
 * the matches; the entities themselves are loaded from the database so the
 * storefront renders the same objects as the rest of the catalog. If the
 * search cluster is unreachable it degrades to a database LIKE search.
 */
class ProductSearch
{
    private const MAX_RESULTS = 50;

    public function __construct(
        private readonly Client $client,
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
            return $this->products->findActiveByIds($this->matchingIds($term));
        } catch (\Throwable $e) {
            $this->logger->warning('Product search fell back to the database: '.$e->getMessage());

            return $this->products->searchByName($term);
        }
    }

    /**
     * @return int[]
     */
    private function matchingIds(string $term): array
    {
        $response = $this->client->search([
            'index' => ProductIndexer::INDEX,
            'body' => [
                'size' => self::MAX_RESULTS,
                'query' => [
                    'bool' => [
                        'must' => [
                            ['multi_match' => [
                                'query' => $term,
                                'fields' => ['name^2', 'description'],
                                'fuzziness' => 'AUTO',
                            ]],
                        ],
                        'filter' => [
                            ['term' => ['active' => true]],
                        ],
                    ],
                ],
            ],
        ]);

        return array_map(
            static fn (array $hit): int => (int) $hit['_id'],
            $response['hits']['hits'],
        );
    }
}
