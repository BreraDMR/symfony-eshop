<?php

declare(strict_types=1);

namespace App\Twig;

use App\ValueObject\Money;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MoneyExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('money', $this->format(...)),
        ];
    }

    public function format(Money $money): string
    {
        $formatter = new \NumberFormatter('en_US', \NumberFormatter::CURRENCY);

        return $formatter->formatCurrency($money->toMajorUnits(), $money->currency());
    }
}
