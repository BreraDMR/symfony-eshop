<?php

declare(strict_types=1);

namespace App\Search;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;

/**
 * Builds the Elasticsearch client from the configured endpoint. Kept as a
 * factory so the single connection is shared and easy to stub in tests.
 */
final class ElasticClientFactory
{
    public static function create(string $endpoint): Client
    {
        return ClientBuilder::create()
            ->setHosts([$endpoint])
            ->build();
    }
}
