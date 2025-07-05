<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class AdminDashboardController extends AbstractController
{
    #[Route('', name: 'app_admin_dashboard', methods: ['GET'])]
    public function index(ProductRepository $products, OrderRepository $orders): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'productCount' => $products->count([]),
            'orderCount' => $orders->count([]),
            'recentOrders' => $orders->findLatest(5),
        ]);
    }
}
