<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Enum\OrderStatus;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/orders')]
class AdminOrderController extends AbstractController
{
    #[Route('', name: 'app_admin_order_index', methods: ['GET'])]
    public function index(OrderRepository $orders): Response
    {
        return $this->render('admin/order/index.html.twig', [
            'orders' => $orders->findLatest(100),
        ]);
    }

    #[Route('/{id}', name: 'app_admin_order_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Order $order): Response
    {
        return $this->render('admin/order/show.html.twig', [
            'order' => $order,
            'statuses' => OrderStatus::cases(),
        ]);
    }

    #[Route('/{id}/status', name: 'app_admin_order_status', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function updateStatus(Order $order, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('order_status_'.$order->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $status = OrderStatus::tryFrom((string) $request->request->get('status'));

        if ($status === null) {
            $this->addFlash('error', 'Unknown order status.');

            return $this->redirectToRoute('app_admin_order_show', ['id' => $order->getId()]);
        }

        $this->applyStatus($order, $status);
        $em->flush();

        $this->addFlash('success', 'Order status updated.');

        return $this->redirectToRoute('app_admin_order_show', ['id' => $order->getId()]);
    }

    private function applyStatus(Order $order, OrderStatus $status): void
    {
        match ($status) {
            OrderStatus::Paid => $order->markAsPaid($order->getPaymentReference() ?? 'MANUAL'),
            OrderStatus::Failed => $order->markAsFailed(),
            OrderStatus::Cancelled => $order->cancel(),
            OrderStatus::Pending => null,
        };
    }
}
