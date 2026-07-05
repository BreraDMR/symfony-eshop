<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Carries and validates the data entered on the checkout form.
 */
class CheckoutDetails
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 180)]
    public string $customerName = '';

    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public string $customerEmail = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 1000)]
    public string $shippingAddress = '';
}
