<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\ProductFormData;
use App\Entity\Product;
use App\Form\ProductType;
use App\Product\ProductManager;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/products')]
class AdminProductController extends AbstractController
{
    #[Route('', name: 'app_admin_product_index', methods: ['GET'])]
    public function index(ProductRepository $products): Response
    {
        return $this->render('admin/product/index.html.twig', [
            'products' => $products->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_admin_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ProductManager $productManager, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ProductType::class, new ProductFormData());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product = $productManager->create($form->getData());
            $em->persist($product);
            $em->flush();

            $this->addFlash('success', 'Product created.');

            return $this->redirectToRoute('app_admin_product_index');
        }

        return $this->render('admin/product/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_product_edit', methods: ['GET', 'POST'])]
    public function edit(Product $product, Request $request, ProductManager $productManager, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ProductType::class, ProductFormData::fromProduct($product));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $productManager->update($product, $form->getData());
            $em->flush();

            $this->addFlash('success', 'Product updated.');

            return $this->redirectToRoute('app_admin_product_index');
        }

        return $this->render('admin/product/edit.html.twig', [
            'form' => $form,
            'product' => $product,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_product_delete', methods: ['POST'])]
    public function delete(Product $product, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('delete_product_'.$product->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $em->remove($product);
        $em->flush();

        $this->addFlash('success', 'Product deleted.');

        return $this->redirectToRoute('app_admin_product_index');
    }
}
