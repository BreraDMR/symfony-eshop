<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CatalogController extends AbstractController
{
    #[Route('/', name: 'app_catalog_index', methods: ['GET'])]
    public function index(
        Request $request,
        ProductRepository $products,
        CategoryRepository $categories,
    ): Response {
        $categorySlug = $request->query->get('category');
        $activeCategory = $categorySlug ? $categories->findOneBySlug($categorySlug) : null;

        return $this->render('catalog/index.html.twig', [
            'categories' => $categories->findAllOrderedByName(),
            'products' => $products->findActive($activeCategory),
            'activeCategory' => $activeCategory,
        ]);
    }

    #[Route('/product/{slug}', name: 'app_catalog_product', methods: ['GET'])]
    public function show(string $slug, ProductRepository $products): Response
    {
        $product = $products->findOneActiveBySlug($slug);

        if ($product === null) {
            throw $this->createNotFoundException('Product not found.');
        }

        return $this->render('catalog/show.html.twig', [
            'product' => $product,
        ]);
    }
}
