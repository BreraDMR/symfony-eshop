<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Order;
use App\Message\OrderConfirmationEmail;
use App\Repository\OrderRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
final class SendOrderConfirmationEmail
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly MailerInterface $mailer,
    ) {
    }

    public function __invoke(OrderConfirmationEmail $message): void
    {
        $order = $this->orders->find($message->orderId);

        // The order may have been removed between dispatch and handling.
        if ($order === null) {
            return;
        }

        $email = (new Email())
            ->to($order->getCustomerEmail())
            ->subject(sprintf('Your order %s is confirmed', $order->getReference()))
            ->text($this->body($order));

        $this->mailer->send($email);
    }

    private function body(Order $order): string
    {
        $lines = [
            sprintf('Hi %s,', $order->getCustomerName()),
            '',
            sprintf('Thanks for your order %s. We have received your payment.', $order->getReference()),
            '',
        ];

        foreach ($order->getItems() as $item) {
            $subtotal = $item->getSubtotal();
            $lines[] = sprintf(
                '  %d x %s — %s %s',
                $item->getQuantity(),
                $item->getProductName(),
                number_format($subtotal->toMajorUnits(), 2),
                $subtotal->currency(),
            );
        }

        $total = $order->getTotal();
        $lines[] = '';
        $lines[] = sprintf('Total: %s %s', number_format($total->toMajorUnits(), 2), $total->currency());

        return implode("\n", $lines);
    }
}
