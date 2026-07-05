<?php

declare(strict_types=1);

namespace App\Tests\Payment;

use App\Entity\Order;
use App\Payment\FakePaymentGateway;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class FakePaymentGatewayTest extends TestCase
{
    public function testInitiateRedirectsToSimulatedPaymentPage(): void
    {
        $order = new Order('ORD-TEST-1', 'jan@example.com', 'Jan Novak', 'Praha');

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects(self::once())
            ->method('generate')
            ->with('app_checkout_simulate', ['reference' => 'ORD-TEST-1'], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('http://localhost/checkout/ORD-TEST-1/pay');

        $gateway = new FakePaymentGateway($urlGenerator);
        $initiation = $gateway->initiate($order, 'http://localhost/success', 'http://localhost/cancel');

        self::assertSame('http://localhost/checkout/ORD-TEST-1/pay', $initiation->redirectUrl);
        self::assertSame('FAKE-ORD-TEST-1', $initiation->reference);
    }
}
