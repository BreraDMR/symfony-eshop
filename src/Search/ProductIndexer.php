<?php

declare(strict_types=1);

namespace App\Search;

use App\Entity\Product;
use Elastic\Elasticsearch\Client;

/**
 * Keeps the "products" Elasticsearch index in sync with the catalog: it can
 * (re)create the index with an explicit mapping and add or remove single
 * product documents.
 */
class ProductIndexer
{
    public const INDEX = 'products';

    public function __construct(private readonly Client $client)
    {
    }

    /**
     * Drops and recreates the index with its mapping. Used by the reindex
     * command before a full rebuild.
     */
    public function resetIndex(): void
    {
        if ($this->client->indices()->exists(['index' => self::INDEX])->asBool()) {
            $this->client->indices()->delete(['index' => self::INDEX]);
        }

        $this->client->indices()->create([
            'index' => self::INDEX,
            'body' => [
                'mappings' => [
                    'properties' => [
                        'name' => ['type' => 'text'],
                        'description' => ['type' => 'text'],
                        'slug' => ['type' => 'keyword'],
                        'category' => ['type' => 'keyword'],
                        'active' => ['type' => 'boolean'],
                    ],
                ],
            ],
        ]);
    }

    public function index(Product $product): void
    {
        $this->client->index([
            'index' => self::INDEX,
            'id' => (string) $product->getId(),
            'body' => $this->toDocument($product),
        ]);
    }

    public function remove(Product $product): void
    {
        $this->removeById((string) $product->getId());
    }

    /**
     * Removes a document by id. Doctrine clears an entity's identifier by the
     * time postRemove runs, so callers there must pass the id they captured
     * earlier rather than reading it off a detached entity.
     */
    public function removeById(string $id): void
    {
        $this->client->delete([
            'index' => self::INDEX,
            'id' => $id,
        ]);
    }

    /**
     * Makes recently indexed documents searchable straight away. Elasticsearch
     * refreshes on its own about once a second, so this is only needed when the
     * result must be visible immediately (reindex command, tests).
     */
    public function refresh(): void
    {
        $this->client->indices()->refresh(['index' => self::INDEX]);
    }

    /**
     * @return array<string, mixed>
     */
    private function toDocument(Product $product): array
    {
        return [
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'slug' => $product->getSlug(),
            'category' => $product->getCategory()->getName(),
            'active' => $product->isActive(),
        ];
    }
}
