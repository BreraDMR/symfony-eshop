<?php

declare(strict_types=1);

namespace App\Controller;

use App\Cart\CartService;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cart')]
class CartController extends AbstractController
{
    #[Route('', name: 'app_cart_show', methods: ['GET'])]
    public function show(CartService $cart): Response
    {
        return $this->render('cart/show.html.twig', [
            'cart' => $cart->getCart(),
        ]);
    }

    #[Route('/add/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(int $id, Request $request, ProductRepository $products, CartService $cart): Response
    {
        if (!$this->isCsrfTokenValid('cart_add_'.$id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $product = $products->find($id);

        if ($product === null || !$product->isActive()) {
            throw $this->createNotFoundException('Product not found.');
        }

        if (!$product->isInStock()) {
            $this->addFlash('error', 'This product is currently out of stock.');

            return $this->redirectToRoute('app_catalog_product', ['slug' => $product->getSlug()]);
        }

        $quantity = max(1, (int) $request->request->get('quantity', 1));
        $cart->add($product->getId(), $quantity);
        $this->addFlash('success', sprintf('"%s" was added to your cart.', $product->getName()));

        return $this->redirectToRoute('app_cart_show');
    }

    #[Route('/update/{id}', name: 'app_cart_update', methods: ['POST'])]
    public function update(int $id, Request $request, CartService $cart): Response
    {
        if (!$this->isCsrfTokenValid('cart_update_'.$id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $cart->setQuantity($id, (int) $request->request->get('quantity', 1));

        return $this->redirectToRoute('app_cart_show');
    }

    #[Route('/remove/{id}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(int $id, Request $request, CartService $cart): Response
    {
        if (!$this->isCsrfTokenValid('cart_remove_'.$id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $cart->remove($id);
        $this->addFlash('success', 'Item removed from your cart.');

        return $this->redirectToRoute('app_cart_show');
    }
}
