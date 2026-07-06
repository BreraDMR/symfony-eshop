<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @return Product[]
     */
    public function findActive(?Category $category = null): array
    {
        $qb = $this->activeQueryBuilder();

        if ($category !== null) {
            $qb->andWhere('p.category = :category')
                ->setParameter('category', $category);
        }

        return $qb->getQuery()->getResult();
    }

    public function findOneActiveBySlug(string $slug): ?Product
    {
        return $this->activeQueryBuilder()
            ->andWhere('p.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Naive database search, later replaced by Elasticsearch on the storefront.
     *
     * @return Product[]
     */
    public function searchByName(string $term): array
    {
        return $this->activeQueryBuilder()
            ->andWhere('LOWER(p.name) LIKE :term')
            ->setParameter('term', '%'.strtolower($term).'%')
            ->getQuery()
            ->getResult();
    }

    private function activeQueryBuilder(): QueryBuilder
    {
        // Fetch the category in the same query: the storefront always renders
        // it, and it keeps the result self-contained when cached in Redis.
        return $this->createQueryBuilder('p')
            ->addSelect('c')
            ->join('p.category', 'c')
            ->andWhere('p.active = true')
            ->orderBy('p.name', 'ASC');
    }
}
