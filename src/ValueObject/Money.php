<?php

declare(strict_types=1);

namespace App\ValueObject;

use Doctrine\ORM\Mapping as ORM;

/**
 * Immutable money value object.
 *
 * The amount is stored in minor units (e.g. haléře for CZK) as an integer,
 * so we never do arithmetic on floats and avoid rounding surprises.
 */
#[ORM\Embeddable]
final class Money
{
    #[ORM\Column(name: 'amount', type: 'integer')]
    private int $amount;

    #[ORM\Column(name: 'currency', type: 'string', length: 3)]
    private string $currency;

    public function __construct(int $amount, string $currency = 'CZK')
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Money amount cannot be negative.');
        }

        if (strlen($currency) !== 3) {
            throw new \InvalidArgumentException(sprintf('Invalid currency code "%s".', $currency));
        }

        $this->amount = $amount;
        $this->currency = strtoupper($currency);
    }

    /**
     * Build from a major-unit amount, e.g. fromMajorUnits(199.90) => 19990.
     */
    public static function fromMajorUnits(float $amount, string $currency = 'CZK'): self
    {
        return new self((int) round($amount * 100), $currency);
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function multiply(int $factor): self
    {
        if ($factor < 0) {
            throw new \InvalidArgumentException('Cannot multiply money by a negative factor.');
        }

        return new self($this->amount * $factor, $this->currency);
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    /**
     * The amount expressed in major units, e.g. 19990 => 199.90.
     */
    public function toMajorUnits(): float
    {
        return $this->amount / 100;
    }

    public function __toString(): string
    {
        return number_format($this->toMajorUnits(), 2, '.', ' ').' '.$this->currency;
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot operate on money with different currencies (%s and %s).',
                $this->currency,
                $other->currency,
            ));
        }
    }
}
