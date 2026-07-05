<?php

declare(strict_types=1);

namespace App\Order;

/**
 * Generates a short, human-readable order reference such as ORD-260705-3F9A.
 */
class OrderReferenceGenerator
{
    public function generate(): string
    {
        return sprintf(
            'ORD-%s-%s',
            date('ymd'),
            strtoupper(bin2hex(random_bytes(2))),
        );
    }
}
