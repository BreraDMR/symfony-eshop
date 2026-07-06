<?php

declare(strict_types=1);

namespace App\Controller;

use App\Cart\CartService;
use App\Dto\CheckoutDetails;
use App\Entity\Order;
use App\Form\CheckoutType;
use App\Message\OrderConfirmationEmail;
use App\Order\OrderFactory;
use App\Payment\PaymentGatewayInterface;
use App\Payment\StripePaymentGateway;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/checkout')]
class CheckoutController extends AbstractController
{
    #[Route('', name: 'app_checkout_start', methods: ['GET', 'POST'])]
    public function start(
        Request $request,
        CartService $cartService,
        OrderFactory $orderFactory,
        PaymentGatewayInterface $gateway,
        EntityManagerInterface $em,
    ): Response {
        $cart = $cartService->getCart();

        if ($cart->isEmpty()) {
            $this->addFlash('error', 'Your cart is empty.');

            return $this->redirectToRoute('app_cart_show');
        }

        $details = new CheckoutDetails();
        $form = $this->createForm(CheckoutType::class, $details);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $order = $orderFactory->createFromCart($cart, $details);
            $em->persist($order);
            $em->flush();

            $cartService->clear();

            $initiation = $gateway->initiate(
                $order,
                $this->generateUrl('app_checkout_success', ['reference' => $order->getReference()], UrlGeneratorInterface::ABSOLUTE_URL),
                $this->generateUrl('app_checkout_cancel', ['reference' => $order->getReference()], UrlGeneratorInterface::ABSOLUTE_URL),
            );

            return $this->redirect($initiation->redirectUrl);
        }

        return $this->render('checkout/start.html.twig', [
            'cart' => $cart,
            'form' => $form,
        ]);
    }

    /**
     * Stand-in for a hosted payment page, used by the fake gateway.
     */
    #[Route('/{reference}/pay', name: 'app_checkout_simulate', methods: ['GET'])]
    public function simulate(string $reference, OrderRepository $orders): Response
    {
        $order = $this->findOrder($reference, $orders);

        if ($order->getStatus()->isPaid()) {
            return $this->redirectToRoute('app_checkout_success', ['reference' => $reference]);
        }

        return $this->render('checkout/simulate.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{reference}/confirm', name: 'app_checkout_confirm', methods: ['POST'])]
    public function confirm(
        string $reference,
        Request $request,
        OrderRepository $orders,
        EntityManagerInterface $em,
        MessageBusInterface $bus,
    ): Response {
        if (!$this->isCsrfTokenValid('checkout_confirm_'.$reference, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $order = $this->findOrder($reference, $orders);

        if (!$order->getStatus()->isPaid()) {
            $order->markAsPaid('FAKE-'.$reference);
            $em->flush();
            $bus->dispatch(new OrderConfirmationEmail($order->getId()));
        }

        return $this->redirectToRoute('app_checkout_success', ['reference' => $reference]);
    }

    #[Route('/{reference}/success', name: 'app_checkout_success', methods: ['GET'])]
    public function success(
        string $reference,
        Request $request,
        OrderRepository $orders,
        PaymentGatewayInterface $gateway,
        EntityManagerInterface $em,
        MessageBusInterface $bus,
    ): Response {
        $order = $this->findOrder($reference, $orders);

        // When returning from Stripe, reconcile the checkout session.
        $sessionId = $request->query->get('session_id');
        if (!$order->getStatus()->isPaid() && $sessionId && $gateway instanceof StripePaymentGateway) {
            if ($gateway->isSessionPaid($sessionId)) {
                $order->markAsPaid($sessionId);
                $em->flush();
                $bus->dispatch(new OrderConfirmationEmail($order->getId()));
            }
        }

        return $this->render('checkout/success.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{reference}/cancel', name: 'app_checkout_cancel', methods: ['GET'])]
    public function cancel(string $reference, OrderRepository $orders): Response
    {
        $order = $this->findOrder($reference, $orders);

        return $this->render('checkout/cancel.html.twig', [
            'order' => $order,
        ]);
    }

    private function findOrder(string $reference, OrderRepository $orders): Order
    {
        $order = $orders->findOneByReference($reference);

        if ($order === null) {
            throw $this->createNotFoundException('Order not found.');
        }

        return $order;
    }
}
