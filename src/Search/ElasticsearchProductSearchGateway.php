<?php

declare(strict_types=1);

namespace App\Search;

use Elastic\Elasticsearch\Client;

/**
 * Elasticsearch-backed {@see ProductSearchGateway}. Ranks active products by a
 * fuzzy full-text match on the name (boosted) and description.
 */
final class ElasticsearchProductSearchGateway implements ProductSearchGateway
{
    private const MAX_RESULTS = 50;

    public function __construct(private readonly Client $client)
    {
    }

    public function matchingIds(string $term): array
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
