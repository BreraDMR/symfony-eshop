<?php

declare(strict_types=1);

namespace App\Search;

/**
 * Abstraction over the search engine used by {@see ProductSearch}. It exists so
 * the application depends on our own interface instead of the final
 * Elasticsearch client, which keeps the search service unit-testable.
 */
interface ProductSearchGateway
{
    /**
     * Returns ids of products matching the term, most relevant first.
     *
     * @return int[]
     */
    public function matchingIds(string $term): array;
}
