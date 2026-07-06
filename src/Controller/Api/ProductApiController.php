<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Api\ProductApiNormalizer;
use App\Catalog\ProductCatalog;
use App\Repository\CategoryRepository;
use App\Search\ProductSearch;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Read-only JSON API backing the React storefront. It reuses the same catalog,
 * search and normalization services as the server-rendered pages.
 */
#[Route('/api')]
final class ProductApiController extends AbstractController
{
    #[Route('/products', name: 'api_products_list', methods: ['GET'])]
    public function list(
        Request $request,
        ProductCatalog $catalog,
        ProductSearch $search,
        CategoryRepository $categories,
        ProductApiNormalizer $normalizer,
    ): JsonResponse {
        $query = trim((string) $request->query->get('q', ''));

        if ($query !== '') {
            $products = $search->search($query);
        } else {
            $categorySlug = $request->query->get('category');
            $category = $categorySlug ? $categories->findOneBySlug($categorySlug) : null;
            $products = $catalog->activeProducts($category);
        }

        return $this->json([
            'products' => array_map($normalizer->normalize(...), $products),
        ]);
    }

    #[Route('/products/{slug}', name: 'api_products_show', methods: ['GET'])]
    public function show(
        string $slug,
        ProductCatalog $catalog,
        ProductApiNormalizer $normalizer,
    ): JsonResponse {
        $product = $catalog->activeProductBySlug($slug);

        if ($product === null) {
            return $this->json(['error' => 'Product not found.'], JsonResponse::HTTP_NOT_FOUND);
        }

        return $this->json(['product' => $normalizer->normalize($product)]);
    }

    #[Route('/categories', name: 'api_categories_list', methods: ['GET'])]
    public function categories(CategoryRepository $categories): JsonResponse
    {
        $data = array_map(
            static fn ($category) => ['name' => $category->getName(), 'slug' => $category->getSlug()],
            $categories->findAllOrderedByName(),
        );

        return $this->json(['categories' => $data]);
    }
}
