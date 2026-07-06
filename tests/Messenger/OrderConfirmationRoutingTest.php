<?php

declare(strict_types=1);

namespace App\Tests\Messenger;

use App\Message\OrderConfirmationEmail;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

final class OrderConfirmationRoutingTest extends KernelTestCase
{
    public function testConfirmationEmailIsRoutedToTheAsyncTransport(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $container->get(MessageBusInterface::class)->dispatch(new OrderConfirmationEmail(1));

        /** @var InMemoryTransport $transport */
        $transport = $container->get('messenger.transport.async');

        // The message is queued for a worker rather than handled in-process.
        self::assertCount(1, $transport->getSent());
        self::assertInstanceOf(OrderConfirmationEmail::class, $transport->getSent()[0]->getMessage());
    }
}
