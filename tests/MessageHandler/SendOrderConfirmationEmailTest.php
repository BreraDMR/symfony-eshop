<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Entity\Category;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Message\OrderConfirmationEmail;
use App\MessageHandler\SendOrderConfirmationEmail;
use App\ValueObject\Money;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;

final class SendOrderConfirmationEmailTest extends KernelTestCase
{
    use MailerAssertionsTrait;

    public function testItEmailsTheCustomerAboutTheirOrder(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        $category = new Category('Coffee', 'cat-'.uniqid());
        $product = new Product('Espresso Blend', 'prod-'.uniqid(), new Money(24900), $category);
        $reference = 'ORD-'.strtoupper(substr(uniqid(), -8));
        $order = new Order($reference, 'buyer@example.com', 'Jan Novak', 'Prague 1');
        $order->addItem(new OrderItem($product, 2));

        $em->persist($category);
        $em->persist($product);
        $em->persist($order);
        $em->flush();

        $handler = $container->get(SendOrderConfirmationEmail::class);
        $handler(new OrderConfirmationEmail($order->getId()));

        self::assertEmailCount(1);
        $email = self::getMailerMessage();
        self::assertEmailAddressContains($email, 'To', 'buyer@example.com');
        self::assertEmailTextBodyContains($email, 'Espresso Blend');
        self::assertStringContainsString($order->getReference(), $email->getSubject() ?? '');

        $em->remove($order);
        $em->remove($product);
        $em->remove($category);
        $em->flush();
    }

    public function testItIgnoresAMissingOrder(): void
    {
        self::bootKernel();
        $handler = static::getContainer()->get(SendOrderConfirmationEmail::class);

        $handler(new OrderConfirmationEmail(999999));

        self::assertEmailCount(0);
    }
}
